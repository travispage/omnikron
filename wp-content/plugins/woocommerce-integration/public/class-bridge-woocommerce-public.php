<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://wisdmlabs.com
 * @since      1.0.0
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @author     WisdmLabs <support@wisdmlabs.com>
 */
namespace NmBridgeWoocommerce{

    class BridgeWoocommercePublic
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

        /**
         * Initialize the class and set its properties.
         *
         * @since    1.0.0
         *
         * @param string $plugin_name The name of the plugin.
         * @param string $version     The version of this plugin.
         */
        public function __construct($plugin_name, $version)
        {
            $this->plugin_name = $plugin_name;
            $this->version = $version;
        }

        /**
         * Register the stylesheets for the public-facing side of the site.
         *
         * @since    1.0.0
         */
        public function enqueueStyles()
        {

            /*
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in BridgeWoocommerceLoader as all of the hooks are defined
         * in that particular class.
         *
         * The BridgeWoocommerceLoader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

            wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__).'css/bridge-woocommerce-public.css', array(), $this->version, 'all');
        }

        /**
         * Register the stylesheets for the public-facing side of the site.
         *
         * @since    1.0.0
         */
        public function enqueueScripts()
        {

            wp_enqueue_script(
                $this->plugin_name,
                plugin_dir_url(__FILE__).'js/bridge-woocommerce-public.js',
                array('jquery'),
                $this->version,
                false
            );

            $setting = get_option('eb_general');
            if (isset($setting['eb_my_courses_page_id'])) {
                $url = get_permalink($setting['eb_my_courses_page_id']);
                if ($url) {
                    wp_localize_script(
                        $this->plugin_name,
                        'wiPublic',
                        array(
                            'myCoursesUrl' => $url,
                            'cancel' => __('Cancel', WOOINT_TD),
                            'resume' => __('Resume', WOOINT_TD),
                        )
                    );
                }
            }
        }

        /*
        * This function is used to add associated courses shortcode on - woocommerce_single_product_summary hook
        *
        * @access public
        * @return void
        * @since 1.0.0
        */

        public function displayProductRelatedCourses()
        {
            global $product;
            //error_log(print_r($product->get_type(), true));
            if (($product->is_type('simple') || $product->is_type('subscription') ) && shortcode_exists('bridge_woo_display_associated_courses')) {
                $product_id = get_the_ID();
                echo esc_html(do_shortcode('[bridge_woo_display_associated_courses product_id='.$product_id.']'));
            } elseif ($product->is_type('variable') || $product->is_type('variable-subscription')) {
                $available_variations = $product->get_available_variations();


                $variation_settings = array();

                if (!empty($available_variations)) {
                    foreach ($available_variations as $single_variation) {
                        $return = '';
                        $variation_id = $single_variation['variation_id'];
                        $product_options = get_post_meta($variation_id, 'product_options', true);

                        //$single_variation_setting = array();

                        if (!empty($product_options)) {
                            if (isset($product_options['moodle_post_course_id']) && is_array($product_options['moodle_post_course_id']) && !empty($product_options['moodle_post_course_id'])) {
                                $return = ' <ul class="bridge-woo-available-courses">';
                                foreach ($product_options['moodle_post_course_id'] as $single_course_id) {
                                    if ('publish' === get_post_status($single_course_id)) {
                                        ob_start();
                                        ?>
                                        <li>
                                            <a href="<?php echo esc_url(get_permalink($single_course_id)); ?>" target="_blank"><?php echo get_the_title($single_course_id); ?></a>
                                        </li>
                                        <?php
                                        $return .= ob_get_clean();
                                    }
                                }
                                $return .= '</ul>';
                            }
                        }

                            $variation_settings[$variation_id] = apply_filters('bridge_woo_single_variation_html', $return, $variation_id);
                    }//foreach ends

                    wp_register_script('bridge_woo_variation_courses', BRIDGE_WOOCOMMERCE_PLUGIN_URL . 'public/js/bridge-woocommerce-variation-courses.js', array('jquery'), $this->version);

                    wp_enqueue_script('bridge_woo_variation_courses');

                    wp_localize_script('bridge_woo_variation_courses', 'bridge_woo_courses', json_encode($variation_settings));

                    ob_start();

                    ?>
                        <div class="bridge-woo-courses" style="display:none;">
                            <h4><?php _e('Available courses', WOOINT_TD); ?></h4>
                        </div>
                    <?php

                    $content = ob_get_clean();

                    echo apply_filters('bridge_woo_variation_associated_courses', $content);
                }
            }
        }

        public function groupedProductDisplayAssociatedCourses($product)
        {
            $product_options = get_post_meta($product->get_id(), 'product_options', true);
            if (isset($product_options['moodle_post_course_id']) && is_array($product_options['moodle_post_course_id']) && ! empty($product_options['moodle_post_course_id'])) {
                ob_start();
                ?>
                <td>
                    <div class="wi-asso-courses-wrapper">
                <h7><?php _e('Courses', WOOINT_TD); ?></h7>
                <ul class="bridge-woo-available-courses">
                    <?php
                    foreach ($product_options['moodle_post_course_id'] as $single_course_id) {
                        if ('publish' === get_post_status($single_course_id)) {
                            ?>
                            <li>
                                <a href="<?php echo esc_url(get_permalink($single_course_id)); ?>" target="_blank"><?php echo get_the_title($single_course_id); ?></a>
                            </li>
                            <?php
                        }
                    }
                    ?>
                </ul>
            </div>
                </td>
                <?php
                echo ob_get_clean();
            }
            // echo '<td>';
            // echo esc_html(do_shortcode('[bridge_woo_display_associated_courses product_id='.$product->get_id().']'));
            // echo '</td>';
        }

        /*
        * This function is used to send associated courses list in WooCommerce Emails
        *
        * @access public
        * @return void
        * @since 1.0.0
        */

        public function sendAssociatedCoursesInEmail($order, $sent_to_admin, $plain_text)
        {
            if (empty($sent_to_admin)) {
                $sent_to_admin = '';
            }
            if (empty($plain_text)) {
                $plain_text = '';
            }

            $allowed_order_status = apply_filters('bridge_woo_email_allowed_order_status', array('wc-processing', 'wc-completed', 'wc-on-hold'));

            if (in_array($order->get_status(), $allowed_order_status)) {
                require_once EB_PLUGIN_DIR.'includes/class-eb.php';
                $edwiser_bridge = new \app\wisdmlabs\edwiserBridge\EdwiserBridge();
                require_once EB_PLUGIN_DIR.'public/class-eb-template-loader.php';

                $plugin_tpl_loader = new \app\wisdmlabs\edwiserBridge\EbTemplateLoader($edwiser_bridge->getPluginName(), $edwiser_bridge->getVersion());

                ob_start();

                $plugin_tpl_loader->wpGetTemplate(
                    'emails/associated-courses-order-email.php',
                    array(
                                      'order' => $order,
                                      ),
                    '',
                    BRIDGE_WOOCOMMERCE_PLUGIN_DIR.'public/templates/'
                );
                $email_content = ob_get_clean();

                echo $email_content;
            }
        }

        /*
        * This function is used to set Enable registration on the "Checkout" page and Disable guest checkout - woocommerce_after_checkout_billing_form hook
        *
        * @access public
        * @return void
        * @since 1.1.3
        */

        public function configureWooCommerceCheckout($checkout)
        {
            // Unnecessary var.
            unset($checkout);

            if (!\WC_Checkout::instance()->enable_signup || \WC_Checkout::instance()->enable_guest_checkout) {
                foreach (WC()->cart->get_cart() as $cart_item_key => $values) {
                    unset($cart_item_key);
                    $_product = $values['data'];

                    $product_id = (isset($_product->variation_id)?$_product->variation_id:$_product->id);

                    $product_options = get_post_meta($product_id, 'product_options', true);

                    if (! empty($product_options) && isset($product_options['moodle_post_course_id']) && ! empty($product_options['moodle_post_course_id'])) {
                            //Add condition to make it work on checkout which have courses in the cart.
                            \WC_Checkout::instance()->enable_signup = true;
                            \WC_Checkout::instance()->enable_guest_checkout=false;
                            break;
                    }
                }
            }
        }

        public function thankYouOrderReceivedText($msg, $order)
        {
            $order_manager = new BridgeWoocommerceOrderManager($this->plugin_name, $this->version);
            $courses = (array) $order_manager->_getMoodleCourseIdsForOrder($order);
            $setting = get_option('eb_general');
            $url = isset($setting['eb_my_courses_page_id']) ? get_permalink($setting['eb_my_courses_page_id']) : null;
            // Get the setting to check if redirection is enabled or not
            $setting_woo_integration = get_option('eb_woo_int_settings');
            if (count($courses) && $url && $setting_woo_integration['wi_enable_redirect'] === 'yes') {
                ob_start();
                ?>
                <br />
                <span id="wi-thanq-wrapper">
                    <span class="msg">
                    <?php
                    printf(
                        __('You will be redirected to %s within next %s seconds.', WOOINT_TD),
                        '<a href="' . esc_url($url) . '">' . __('My Courses Page', WOOINT_TD) . '</a>',
                        '<span id="wi-countdown">10</span>'
                    );
                    ?>
                    </span>
                    <button id="wi-cancel-redirect" data-wi-auto-redirect="on"><?php _e('Cancel', WOOINT_TD); ?></button>
                </span>
                <?php
                $msg .= ob_get_clean();
            }
            return $msg;
        }

        public function productPageAfterAddToCart()
        {
            global $product;
            if ($product->get_type() == 'simple') {
                $args = array( 'product' => $product );
                echo self::getBuyNowButton($args);
            }
        }

        public function shopPageAfterAddToCart()
        {
            global $product;
            if ($product->get_type() == 'simple') {
                $args = array( 'product' => $product );
                echo '<br />' . self::getBuyNowButton($args);
            }
        }

        public static function getBuyNowButton($args)
        {
            $args = wp_parse_args(
                $args,
                array(
                    'product' => null,
                    'class' => 'button',
                )
            );

            extract($args);

            $eb_general = get_option('eb_woo_int_settings');
            if (isset($eb_general['wi_buy_now_text']) && !empty($eb_general['wi_buy_now_text'])) {
                $buy_now_text = $eb_general['wi_buy_now_text'];
            } else {
                $buy_now_text = __('Buy Now', WOOINT_TD);
            }

            $html = '';
        
            if ($product == null || ! $product->is_purchasable()) {
                return;
            }

            //$html .= '<div class="wi_buy_now_wrapper">';
            $link = self::getProductAddToCartLink($product, 1);
            $_id = 'wi_buy_now_'. $product->get_id();
            $_class = 'wi_btn_buy_now button wi_buy_now_'.$product->get_type();
            if (is_product()) {
                $_class .= ' wi_product';
            }
            $_attrs = 'data-product_type="'.$product->get_type().'" data-product_id="'.$product->get_id().'"';
            $html .= '<a href="'.$link.'" id="'.$_id.'" '.$_attrs.'  class="'.$_class.'">';
            $html .= $buy_now_text;
            $html .= '</a>';
            //$html .= '</div>';
            return $html;
        }

        public static function getProductAddToCartLink($product, $qty = 1)
        {
            if ($product->get_type() == 'simple') {
                $link = $product->add_to_cart_url();
                $link = add_query_arg('quantity', $qty, $link);
                $link = add_query_arg('wi_buy_now', true, $link);
                return $link;
            }
        }

        public function buyNowRedirect($url)
        {
            if (isset($_REQUEST['wi_buy_now']) && $_REQUEST['wi_buy_now'] == true) {
                $eb_general = get_option('eb_woo_int_settings');
                if (isset($eb_general['wi_scc_page_id'])) {
                    $scc_url = get_permalink($eb_general['wi_scc_page_id']);
                    if ($scc_url) {
                        $url = $scc_url;
                    }
                }
            }

            return $url;
        }

        public function isSingleCartCheckout($is_scc)
        {
            $eb_general = get_option('eb_woo_int_settings');
            if (isset($eb_general['wi_scc_page_id'])) {
                $scc_page_id = (int) $eb_general['wi_scc_page_id'];
                if (is_page($scc_page_id)) {
                    $is_scc = true;
                }
            }

            return $is_scc;
        }
    }
}
