<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://wisdmlabs.com
 * @since      1.0.0
 *
 * @package    Bridge_Woocommerce
 * @subpackage Bridge_Woocommerce/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Bridge_Woocommerce
 * @subpackage Bridge_Woocommerce/includes
 * @author     WisdmLabs <support@wisdmlabs.com>
 */
namespace NmBridgeWoocommerce{

    class BridgeWoocommerce
    {

        /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      BridgeWoocommerceLoader    $loader    Maintains and registers all hooks for the plugin.
     */
        protected $loader;

        /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
        protected $plugin_name;

        /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
        protected $version;

        /**
    *
    *
    * @var Bridge_Woocommerce The single instance of the class
    * @since 1.0.0
    */
        protected static $_instance = null;

        /**
    * Main Bridge_Woocommerce Instance
    *
    * Ensures only one instance of Bridge_Woocommerce is loaded or can be loaded.
    *
    * @since 1.0.0
    * @static
    * @see bridge_woocommerce()
    * @return Bridge_Woocommerce - Main instance
    */
        public static function instance()
        {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
        public function __construct()
        {
            global $wooint_plugin_data;

            $this->plugin_name = $wooint_plugin_data['pluginSlug'];
            $this->version = $wooint_plugin_data['pluginVersion'];
            $this->defineConstants();
            $this->loadDependencies();
            $this->setLocale();
            $this->defineAdminHooks();
            $this->definePublicHooks();
        }

        /**
    * Setup plugin constants
    *
    * @access private
    * @since 1.0.0
    * @return void
    */
        private function defineConstants()
        {

            // Plugin version
            if (! defined('BRIDGE_WOOCOMMERCE_VERSION')) {
                define('BRIDGE_WOOCOMMERCE_VERSION', $this->version);
            }

            // Plugin Folder URL
            if (! defined('BRIDGE_WOOCOMMERCE_PLUGIN_URL')) {
                define('BRIDGE_WOOCOMMERCE_PLUGIN_URL', plugin_dir_url(dirname(__FILE__)));
            }

            // Plugin Folder Path
            if (! defined('BRIDGE_WOOCOMMERCE_PLUGIN_DIR')) {
                define('BRIDGE_WOOCOMMERCE_PLUGIN_DIR', plugin_dir_path(dirname(__FILE__)));
            }

            /**
             * WooCommerce Subscriptions version - Useful to support compatibility with legacy version and to check WCS is activated or not.
             *
             * @since 1.1.3
             */
            if (!defined('WOOINT_WCS_VER')) {
                include_once(ABSPATH . 'wp-admin/includes/plugin.php');
                if (is_plugin_active('woocommerce-subscriptions/woocommerce-subscriptions.php')) {
                    include_once(WP_PLUGIN_DIR . '/woocommerce-subscriptions/woocommerce-subscriptions.php');
                    define('WOOINT_WCS_VER', \WC_Subscriptions::$version);
                }
            }
        }

        /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Bridge_Woocommerce_Loader. Orchestrates the hooks of the plugin.
     * - Bridge_Woocommerce_i18n. Defines internationalization functionality.
     * - Bridge_Woocommerce_Admin. Defines all hooks for the admin area.
     * - Bridge_Woocommerce_Public. Defines all hooks for the public side of the site.
     * - Bridge_Woocommerce_Course. Defines all hooks for create/update product on Course create/update
     * - Bridge_Woo_Product_Manager. Defines all hooks for Product meta save & shortcode embed on single product page.
     * - Bridge_Woocommerce_Order_Manager. Defines all hooks for user enrollment as per Order status change
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
        private function loadDependencies()
        {
            if (! is_admin()) {
                $this->frontendDependencies();
            }

            /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
            include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-bridge-woocommerce-loader.php';

            /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
            include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-bridge-woocommerce-i18n.php';

            /**
         * The class responsible for defining all actions that occur in the admin area.
         */
            include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'admin/class-bridge-woocommerce-admin.php';

            /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
            include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'public/class-bridge-woocommerce-public.php';

            $this->loader = new BridgeWoocommerceLoader();

            /*
             *The class responsible for defining all actions that occur in both for
             * course Product syncrhonization
             */
            include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-bridge-woocommerce-course.php';

            /*
             *The class responsible for defining all actions that occur for Product meta fields & other operation
             */

            include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-bridge-woocommerce-product-manager.php';

            /*
             *The class responsible for defining all actions that occur Order completion & other operation
             */

            include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-bridge-woocommerce-order-manager.php';

            /*
             *The class responsible for defining all actions that occur for AJAX
             */

            include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-bridge-woocommerce-ajax.php';
        }

        /**
    * public facing code
    *
    * Include the following files that make up the plugin:
    * - Bridge_Woocommerce_Shortcodes. Defines set of shortcode.
    * - Bridge_Woo_Shortcode_Associated_Courses. Defines output for associated courses.
    *
    * @return void
    * @since    1.0.0
    * @access   private
    */
        private function frontendDependencies()
        {

            /**
        * Tha classes responsible for defining shortcodes & templates
        */
            include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'public/class-bridge-woocommerce-shortcodes.php';
            include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'public/shortcodes/class-bridge-woocommerce-shortcode-associated-courses.php';
            include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'public/shortcodes/class-bridge-woocommerce-shortcode-single-cart-checkout.php';
        }

        /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the BridgeWoocommercei18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
        private function setLocale()
        {
            $plugin_i18n = new BridgeWoocommercei18n();
            $plugin_i18n->setDomain(WOOINT_TD);

            $this->loader->addAction('plugins_loaded', $plugin_i18n, 'loadPluginTextdomain');
        }

        /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
        private function defineAdminHooks()
        {
            include_once('class-bridge-woo-get-plugin-data.php');

            global $wooint_plugin_data;

            $get_data_from_db = BridgeWooGetPluginData::getDataFromDb($wooint_plugin_data);

            if ('available' == $get_data_from_db) {
                $plugin_admin = new BridgeWoocommerceAdmin($this->getPluginName(), $this->getVersion());

                $this->loader->addAction('admin_enqueue_scripts', $plugin_admin, 'enqueueStyles');
                $this->loader->addAction('admin_enqueue_scripts', $plugin_admin, 'enqueueScripts');

                //$this->loader->addFilter('eb_get_settings_general', $plugin_admin, 'generalSettings', 10, 1);
                $this->loader->addFilter('eb_get_settings_pages', $plugin_admin, 'addWooIntTab', 10, 1);

                $this->loader->addFilter('post_row_actions', $plugin_admin, 'addContainsEnrolment', 10, 2);


                $this->loader->addAction('wp_ajax_unenrol_check_status', $plugin_admin, 'unenrolCheckStatus');
                $this->loader->addAction('wp_ajax_unenrol_update_html', $plugin_admin, 'unenrolUpdateHtml');
                $this->loader->addAction('woocommerce_order_item_add_line_buttons', $plugin_admin, 'refundHtmlContent', 10, 1);
                $this->loader->addAction('woocommerce_order_refunded', $plugin_admin, 'orderRefunded', 10, 2);

                //Add Product synchronization setting

                $this->loader->addFilter('eb_getSections_synchronization', $plugin_admin, 'bridgeWooAddProductSynchronizationSection', 10, 1);

                $this->loader->addFilter('eb_get_settings_synchronization', $plugin_admin, 'bridgeWooGetProductSynchronizationSetting', 10, 2);

                //Products Meta fields and other operation

                //$product_manager_woo_plugin = new BridgeWooProductManager($this->plugin_name, $this->version);
                $prod_manager_plugin = new BridgeWooProductManager($this->plugin_name, $this->version);

                // $this->loader->addAction('add_meta_boxes',$prod_manager_plugin,'register_meta_boxes');
                $this->loader->addAction('save_post', $prod_manager_plugin, 'handlePostOptionsSave', 10);
                $this->loader->addAction('before_delete_post', $prod_manager_plugin, 'handlePostOptionsDelete', 10, 1);

                $this->loader->addFilter('woocommerce_product_data_tabs', $prod_manager_plugin, 'bridgeWooAddTab', 10, 1);
                $this->loader->addAction('woocommerce_product_data_panels', $prod_manager_plugin, 'bridgeWooAddDataPanel');
                $this->loader->addAction('woocommerce_product_after_variable_attributes', $prod_manager_plugin, 'bridgeWooAddProductMetaVariation', 10, 3);

                $this->loader->addAction('woocommerce_save_product_variation', $prod_manager_plugin, 'bridgeWooSaveVariationMeta', 10, 2);

                //$this->loader->addAction('woocommerce_process_product_meta_variable',$prod_manager_plugin,'bridge_woo_save_variation_meta',10,2);

                //Enroll User on order status change

                //$order_manager_woo_plugin = new BridgeWoocommerceOrderManager($this->plugin_name, $this->version);
                $order_manager_plugin = new BridgeWoocommerceOrderManager($this->plugin_name, $this->version);

                // $this->loader->addAction('woocommerce_order_status_completed', $order_manager_plugin, 'handleOrderComplete', 10, 1);
                // $this->loader->addAction('woocommerce_order_status_cancelled', $order_manager_plugin, 'handleOrderCancel', 10, 1);
                // $this->loader->addAction('woocommerce_order_status_refunded', $order_manager_plugin, 'handleOrderCancel', 10, 1);

                /**
                 * One hook handles all statues
                 * @since 1.1.3
                 */
                $this->loader->addAction(
                    'woocommerce_order_status_changed',
                    $order_manager_plugin,
                    'wcOrderStatusChanged',
                    10,
                    3
                );

                $this->loader->addFilter('pre_option_woocommerce_enable_guest_checkout', $order_manager_plugin, 'disableGuestCheckout', 10, 1);

                //Create / Link Moodle User

                $this->loader->addAction('woocommerce_checkout_order_processed', $order_manager_plugin, 'createMoodleUserForCreatedCustomer', 10, 2);

                $this->loader->addFilter('eb_filter_moodle_password', $order_manager_plugin, 'addUserSubmittedPassword', 10, 1);

                // WCS is active.
                if (defined('WOOINT_WCS_VER')) {
                    if (version_compare(WOOINT_WCS_VER, '2.0', '>=')) {
                        $this->loader->addAction('woocommerce_subscription_status_updated', $order_manager_plugin, 'wcsStatusUpdated', 111, 2);
                    } else {
                        /**
                         * Legacy hooks.
                         * @deprecated 1.1.3 Use woocommerce_subscription_status_updated
                         */
                        $this->loader->addAction('activated_subscription', $order_manager_plugin, 'handleActivatedSubscription', 10, 2);
                        $this->loader->addAction('cancelled_subscription', $order_manager_plugin, 'handleCancelledSubscription', 10, 2);
                        $this->loader->addAction('subscription_expired', $order_manager_plugin, 'handleCancelledSubscription', 10, 2);
                        $this->loader->addAction('subscription_put_on-hold', $order_manager_plugin, 'handleCancelledSubscription', 10, 2);
                    }
                }
            }
        }

        /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
        private function definePublicHooks()
        {
            include_once('class-bridge-woo-get-plugin-data.php');

            global $wooint_plugin_data;

            $get_data_from_db = BridgeWooGetPluginData::getDataFromDb($wooint_plugin_data);

            if ('available' == $get_data_from_db) {
                if (! is_admin()) {
                    add_action('init', array( 'NmBridgeWoocommerce\BridgeWoocommerceShortcodes', 'init' ));
                }

                $plugin_public = new BridgeWoocommercePublic($this->getPluginName(), $this->getVersion());

                $this->loader->addAction('wp_enqueue_scripts', $plugin_public, 'enqueueStyles');
                $this->loader->addAction('wp_enqueue_scripts', $plugin_public, 'enqueueScripts');

                //Display associated courses on single product page as well as in Order email

                $this->loader->addAction('woocommerce_single_product_summary', $plugin_public, 'displayProductRelatedCourses', 10);
                $this->loader->addAction(
                    'woocommerce_grouped_product_list_before_price',
                    $plugin_public,
                    'groupedProductDisplayAssociatedCourses',
                    10,
                    1
                );
                $this->loader->addAction('woocommerce_email_after_order_table', $plugin_public, 'sendAssociatedCoursesInEmail', 10, 3);

                //To mark Create an account? checkbox as checked and then hide the option for non-logged user who have edwiser products in cart

                $this->loader->addFilter('woocommerce_is_checkout', $plugin_public, 'isSingleCartCheckout', 111, 1);

                $this->loader->addFilter(
                    'woocommerce_thankyou_order_received_text',
                    $plugin_public,
                    'thankYouOrderReceivedText',
                    10,
                    2
                );

                $eb_general = get_option('eb_woo_int_settings');

                $buy_now_enabled = isset($eb_general['wi_enable_buynow']) && $eb_general['wi_enable_buynow'] === 'yes' ? true : false;

                if ($buy_now_enabled) {
                    $this->loader->addAction(
                        'woocommerce_after_add_to_cart_button',
                        $plugin_public,
                        'productPageAfterAddToCart'
                    );

                    $this->loader->addAction(
                        'woocommerce_after_shop_loop_item',
                        $plugin_public,
                        'shopPageAfterAddToCart',
                        11
                    );

                    $this->loader->addFilter(
                        'woocommerce_add_to_cart_redirect',
                        $plugin_public,
                        'buyNowRedirect',
                        10,
                        1
                    );
                }
            }
        }

        /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
        public function run()
        {
            $this->loader->run();
        }

        /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
        public function getPluginName()
        {
            return $this->plugin_name;
        }

        /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    BridgeWoocommerceLoader    Orchestrates the hooks of the plugin.
     */
        public function getLoader()
        {
            return $this->loader;
        }

        /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
        public function getVersion()
        {
            return $this->version;
        }
    }

/**
 * Returns the main instance of Bridge_Woocommerce to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return Bridge_Woocommerce
 */
    function bridgeWoocommerce()
    {
        return BridgeWoocommerce::instance();
    }
}
