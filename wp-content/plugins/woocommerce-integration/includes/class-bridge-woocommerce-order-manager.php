<?php

/**
 * The file that defines woocommerce Order management.
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://wisdmlabs.com
 * @since      1.0.0
 */

/**
 * This is used to define Order processing & Moodle Course Enrollment.
 *
 *
 * @since      1.0.0
 *
 * @author     WisdmLabs <support@wisdmlabs.com>
 */
namespace NmBridgeWoocommerce{

    use \app\wisdmlabs\edwiserBridge\EdwiserBridge;

    class BridgeWoocommerceOrderManager
    {
        /**
         * The ID of this plugin.
         *
         * @since    1.0.0
         *
         * @var string The ID of this plugin.
         */
        private $plugin_name;

        /**
         * The version of this plugin.
         *
         * @since    1.0.0
         *
         * @var string The current version of this plugin.
         */
        private $version;
        private $edwiser_bridge;
        public function __construct($plugin_name, $version)
        {

            $this->plugin_name = $plugin_name;
            $this->version = $version;
            require_once EB_PLUGIN_DIR.'includes/class-eb.php';
            $this->edwiser_bridge = new EdwiserBridge();
        }

        /*
         * This function checks, if order contains products associated with courses
         * Enroll customer in corresponding course
         *
         * @param integer $order_id     The order ID
         * @access public
         * @return void
         * @since 1.0.0
         */
        public function handleOrderComplete($order_id)
        {
            //error_log('@ fun handleOrderComplete');
            if (! empty($order_id)) {
                //global $wpdb;

                $is_processed = get_post_meta($order_id, '_is_processed', true);

                if (! empty($is_processed)) {
                    $this->edwiser_bridge->logger()->add('user', 'Order id '.$order_id.' is already processed');
                    return 0;
                }

                $order = wc_get_order($order_id); //Get Order details

                // WCS is active.
                if (defined('WOOINT_WCS_VER')) {
                    if (version_compare(WOOINT_WCS_VER, '2.0', '>=') && \wcs_order_contains_subscription($order)) {
                        //error_log('@ WCS major update version - wcs_order_contains_subscription');
                        return;
                    } elseif (version_compare(WOOINT_WCS_VER, '2.0', '<') && \WC_Subscriptions_Order::order_contains_subscription($order)) {
                        //error_log('@ WCS legacy version - order_contains_subscription');
                        return;
                    }
                }

                $user_id = get_post_meta($order_id, '_customer_user', true);

                $list_of_course_ids = self::_getMoodleCourseIdsForOrder($order);
                // error_log(print_r($list_of_course_ids, true));
                if (! empty($list_of_course_ids)) {
                    $course_enrolled = self::_enrollUserInCourses($user_id, $list_of_course_ids);

                    if (1 === $course_enrolled) {
                        update_post_meta($order_id, '_is_processed', true);
                    }
                }
            }
        }

        /*
         * This function checks, if order is already processed,
         * It finds associated product courses and
         * suspend customer enrollment in corresponding course
         *
         * @param integer $order_id     The order ID
         * @access public
         * @return void
         * @since 1.0.0
         */

        public function handleOrderCancel($order_id)
        {
            //error_log('@ fun handleOrderCancel');
            if (! empty($order_id)) {
                //global $wpdb;

                $order = wc_get_order($order_id); //Get Order details

                // WCS is active.
                if (defined('WOOINT_WCS_VER')) {
                    if (version_compare(WOOINT_WCS_VER, '2.0', '>=') && \wcs_order_contains_subscription($order)) {
                        //error_log('@ WCS major update version - wcs_order_contains_subscription');
                        return 0;
                    } elseif (version_compare(WOOINT_WCS_VER, '2.0', '<') && \WC_Subscriptions_Order::order_contains_subscription($order)) {
                        //error_log('@ WCS legacy version - order_contains_subscription');
                        return 0;
                    }
                }

                // $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
                // if (in_array('woocommerce-subscriptions/woocommerce-subscriptions.php', $active_plugins)) :
                //     if (\WC_Subscriptions_Order::order_contains_subscription($order)) {
                //         $this->edwiser_bridge->logger()->add('user', 'Order id '.$order_id.' contains subscription product...');
                //         return 0;
                //     }
                // endif;

                $is_processed = get_post_meta($order_id, '_is_processed', true);

                $this->edwiser_bridge->logger()->add('user', 'Check if User enrolled for Order ID - '.$order_id);

                if (empty($is_processed)) {
                    $this->edwiser_bridge->logger()->add('user', 'No User enrollment for Order ID - '.$order_id);
                    return 0;
                }

                $user_id = get_post_meta($order_id, '_customer_user', true);

                $list_of_course_ids = self::_getMoodleCourseIdsForOrder($order);

                if (! empty($list_of_course_ids)) {
                    $course_enrolled = self::_enrollUserInCourses($user_id, $list_of_course_ids, 1);

                    if (1 === $course_enrolled) {
                        update_post_meta($order_id, '_is_processed', '');
                    }
                }
            }
        }

        /*
         * This function is used to create Moodle user if, new Customer is created on wordpress
         * This event is executed when new Order is created,
         *
         * @param interger $order_id
         * @param array $posted_data
         * @access public
         * @return void
         * @since 1.0.0
         */
        public function createMoodleUserForCreatedCustomer($order_id, $posted_data)
        {
            if (empty($posted_data)) {
                $posted_data = '';
            }
            //global $wpdb;

            $product_exist = false;

            if (! empty($order_id)) {
                $order = wc_get_order($order_id); //Get Order details
                $items = $order->get_items(); //Get Item details

                foreach ($items as $single_item) {
                    $product_id = isset($single_item['product_id']) ? $single_item['product_id'] : '';

                    if (! empty($product_id)) {
                        $product_options = get_post_meta($product_id, 'product_options', true);

                        if (! empty($product_options)) {
                            $product_exist = true;
                            break;
                        }
                    }
                }

                if (true === $product_exist) {
                    $user_id = get_post_meta($order_id, '_customer_user', true);

                    $this->edwiser_bridge->logger()->add('user', 'Link Moodle User for User ID  '.$user_id);  // add User log

                    $user = get_userdata(intval($user_id));

                    $user->user_login = strtolower($user->user_login);

                    $this->edwiser_bridge->logger()->add('user', 'Log from WooIntegration');

                    $this->edwiser_bridge->logger()->add('user', 'User Object JSON Encoded : '.json_encode($user));

                    $this->edwiser_bridge->userManager()->linkMoodleUser($user);
                }//if ends - Need to process for Moodle User creation
            }//if ends - Order id present
        }//function ends - create_moodle_user_for_created_customer

        /*
         * This function used to change generated password with User entered password during checkout
         *
         * @param string $password      This contains wordpress generated password
         * @return string $password
         * @access public
         * @since 1.0.0
         */
        public function addUserSubmittedPassword($password)
        {

            if (isset($_POST['account_password'])) {
                return esc_attr($_POST['account_password']);
            }

            return $password;
        }

        /*
         * This function is used to enroll user into courses, if subscription is activated.
         *
         * @param integer $user_id     The id of the user whose subscription is to be activated.
         * @param string $subscription_key  The key representing the given subscription
         * @access public
         * @return void
         */

        public function handleActivatedSubscription($user_id, $subscription_key)
        {

            self::_changeEnrollmentPerSubscriptionStatus($user_id, $subscription_key, 0);
        }

        /*
        * This function is used to suspend enrollment of user for courses, if subscription is cancelled/expired/put on hold.
        *
        * @param integer $user_id     The id of the user whose subscription is to be activated.
        * @param string $subscription_key  The key representing the given subscription
        * @access public
        * @return void
        */

        public function handleCancelledSubscription($user_id, $subscription_key)
        {

            self::_changeEnrollmentPerSubscriptionStatus($user_id, $subscription_key, 1);
        }

        /*
         * This function is called internally to enroll user into set of courses.
         * This calls, 'update_user_course_enrollment()' for User enrollment
         *
         * @param integer $user_id     The id of the user whose subscription is to be activated.
         * @param array $course_id_list     List of Moodle post course ids
         * @param integer $suspend      The suspend status for courses
         * @param integer $unenroll  The unenroll status for courses
         *
         * @return integer $course_enrolled    return status of course enrollment 1 - successfull 0 - problem in enrollment status change
         * @access private
         */
        public function _enrollUserInCourses($user_id, $course_id_list, $suspend = 0, $unenroll = 0)
        {

            $args = array(
                'user_id' => $user_id,
                'courses' => $course_id_list,
                'unenroll' => $unenroll,
                'suspend' => $suspend,
            );

            $course_enrolled = $this->edwiser_bridge->enrollmentManager()->updateUserCourseEnrollment($args); // enroll user to course

            if (1 === $course_enrolled) {
                if (1 === $suspend) {
                    $this->edwiser_bridge->logger()->add('user', 'User enrollment suspended for courses - '.serialize($course_id_list));
                } else {
                    $this->edwiser_bridge->logger()->add('user', 'User enrolled for courses - '.serialize($course_id_list));
                }
            } else {
                $this->edwiser_bridge->logger()->add('user', 'Enrollment response '.$course_enrolled);
            }

            return $course_enrolled;
        }

        /*
         * This function is used to change enrollment status as per subscription status
         * It internally calls, self::_enroll_user_in_courses() to change enrollment status of course
         *
         * @param integer $user_id     The id of the user whose subscription is to be activated.
         * @param string $subscription_key  The key representing the given subscription
         * @param integer $suspend_status  The status for enrollment
         *
         * @access private
         * @return void
         */
        private function _changeEnrollmentPerSubscriptionStatus($user_id, $subscription_key, $suspend_status)
        {
            $item = \WC_Subscriptions_Order::get_item_by_subscription_key($subscription_key);
            //error_log(print_r($item, true));
            if (! empty($item)) {
                //$order_id = isset($item['order_id'])? $item['order_id'] : '';
                //$product_id = isset($item['product_id']) ? $item['product_id'] : '';
                $product_id = '';
                if (isset($item['variation_id']) && is_numeric($item['variation_id']) && $item['variation_id'] > 0) {
                    $product_id = $item['variation_id'];
                } elseif (isset($item['product_id']) && is_numeric($item['product_id'])) {
                    $product_id = $item['product_id'];
                }
                //error_log('@ product_id: ' . $product_id);
                if (! empty($product_id)) {
                    $product_options = get_post_meta($product_id, 'product_options', true);
                    //error_log(print_r($product_options, true));
                    if (! empty($product_options) && isset($product_options['moodle_post_course_id']) && ! empty($product_options['moodle_post_course_id'])) {
                        self::_enrollUserInCourses($user_id, $product_options['moodle_post_course_id'], $suspend_status);

                        if (1 === $suspend_status) {
                            $this->edwiser_bridge->logger()->add('user', 'Subscription suspended for User '.$user_id);
                        } else {
                            $this->edwiser_bridge->logger()->add('user', 'Subscription activated for User '.$user_id);
                        }
                    }
                }
            }
        }

        /*
         * This function is used to fetch list of Moodle courses associated with product items of specified order
         *
         * @param object $order     This is $order object
         *
         * @return array $list_of_course_ids    This returns array of Moodle course post ids
         * @access private
         */

        public function _getMoodleCourseIdsForOrder($order)
        {

            $list_of_course_ids = array();

            $order_id = trim(str_replace('#', '', $order->get_order_number()));
            $this->edwiser_bridge->logger()->add('user', 'Check Line Items for Order ID - '.$order_id);

            $items = $order->get_items(); //Get Item details
            
            foreach ($items as $single_item) {
                //$product_id = isset($single_item['product_id']) ? $single_item['product_id'] : '';
                $product_id = '';
                if (isset($single_item['product_id'])) {
                    $_product = wc_get_product($single_item['product_id']);

                    if ($_product && $_product->is_type('variable') && isset($single_item['variation_id'])) {
                        //The line item is a variable product, so consider its variation.
                        $product_id = $single_item['variation_id'];
                    } else {
                        $product_id = $single_item['product_id'];
                    }
                }

                if (is_numeric($product_id)) {
                    $product_options = get_post_meta($product_id, 'product_options', true);
                    $group_purchase = 'off';
                    if ('off' == apply_filters('check_group_purchase', $group_purchase, $product_id)) {
                        if (! empty($product_options) && isset($product_options['moodle_post_course_id']) && ! empty($product_options['moodle_post_course_id'])) {
                            $line_item_course_ids = $product_options['moodle_post_course_id'];

                            if (! empty($list_of_course_ids)) {
                                $list_of_course_ids = array_unique(array_merge($list_of_course_ids, $line_item_course_ids), SORT_REGULAR);
                            } else {
                                $list_of_course_ids = $line_item_course_ids;
                            }
                        }
                    }
                }
            }//foreach ends

            $this->edwiser_bridge->logger()->add('user', 'Courses IDs from Line Items  '.serialize($list_of_course_ids));  // add User log

            return $list_of_course_ids;
        }

        /**
         * Function to update course access if subscription status updates.
         * @since 1.1.3
         */
        public function wcsStatusUpdated($subscription, $new_status)
        {
            if (get_class($subscription) !== 'WC_Subscription') {
                return;
            }

            //Suspend or not w.r.t. subscription status.
            $statuses = array(
                'pending'        => true,
                'pending-cancel' => true,
                'completed'      => true,
                'active'         => false, //do not suspend if subscription is active.
                'failed'         => true,
                'on-hold'        => true,
                'cancelled'      => true,
                'switched'       => true,
                'expired'        => true,
            );

            $suspend = isset($statuses[$new_status]) && !$statuses[$new_status] ? 0 : 1;
            if (!is_a($subscription->order, 'WC_Order')) {
                return;
            }
            $items = $subscription->order->get_items();
            foreach ($items as $item) {
                $product_id = $item['product_id'];
                $product_variation_id = $item['variation_id'];

                $product = \wc_get_product($product_id);

                if ($product->is_type('variable')) {
                    $product_options = get_post_meta($product_variation_id, 'product_options', true);
                } else {
                    $product_options = get_post_meta($product_id, 'product_options', true);
                }
                
                if (isset($product_options['moodle_post_course_id'])) {
                    self::_enrollUserInCourses(
                        $subscription->order->user_id,
                        $product_options['moodle_post_course_id'],
                        $suspend
                    );
                }
            }
        }

        /**
         * Function to update enrollment/unenrollment when order status changes.
         * @since 1.1.3
         */
        public function wcOrderStatusChanged($order_id, $old_status, $new_status)
        {
            //enrol w.r.t. order status?
            $statuses = array(
                'completed'  => true,
                'processing' => false,
                'on-hold'    => false,
                'cancelled'  => false,
                'failed'     => false,
                'refunded'   => false,
            );

            if (isset($statuses[$new_status]) && $statuses[$new_status] === true) {
                // Enrol.
                $this->handleOrderComplete($order_id);
            } else {
                // Unenrol.
                $this->handleOrderCancel($order_id);
            }

            do_action('wooint_after_order_status_changed', $order_id, $old_status, $new_status);
        }

        /**
        * This function will disable guest checkout option if cart contains course associated products
        * @param $value
        * @return $value (yes to enable guest checkout , no to disable guest checkout)
        */
        public function disableGuestCheckout($value)
        {
            $value = "yes";
            if (WC()->cart) {
                $cart = WC()->cart->get_cart();
                foreach ($cart as $item) {
                    $_product = $item['data'];
                    $_product_id = $_product->get_id();

                    $product_options = get_post_meta($_product_id, 'product_options', true);
                    if (! empty($product_options) && isset($product_options['moodle_post_course_id']) && ! empty($product_options['moodle_post_course_id'])) {
                        $value = "no";
                        break;
                    }
                }
            }
            return $value;
        }
    }
}
