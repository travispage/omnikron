<?php
/**
 * The file that defines the Associated courses shortcode.
 *
 * @link       http://wisdmlabs.com
 * @since      1.0.0
 */

/**
 * The file that defines the Associated Courses shortcodes.
 *
 * @author     WisdmLabs <support@wisdmlabs.com>
 */


namespace NmBridgeWoocommerce{

    use \app\wisdmlabs\edwiserBridge\EbTemplateLoader;
    use \app\wisdmlabs\edwiserBridge\EdwiserBridge;

    class BridgeWooShortcodeAssociatedCourses
    {
        /**
         * Get the shortcode content.
         *
         * @since  1.0.0
         *
         * @param array $atts
         *
         * @return string
         */
        public static function get($atts)
        {
            return BridgeWoocommerceShortcodes::shortcodeWrapper(array(__CLASS__, 'output'), $atts);
        }

        /**
         * Output the shortcode.
         *
         * @since  1.0.0
         *
         * @param array $atts
         */
        public static function output($atts)
        {
            extract(shortcode_atts(array(
                    'product_id' => '',
                ), $atts));
            require_once EB_PLUGIN_DIR.'includes/class-eb.php';
            $edwiser_bridge = new EdwiserBridge();
            require_once EB_PLUGIN_DIR.'public/class-eb-template-loader.php';

            $plugin_tpl_loader = new EbTemplateLoader($edwiser_bridge->getPluginName(), $edwiser_bridge->getVersion());

            if (empty($product_id)) {
                $product_id = '';
            }
            $plugin_tpl_loader->wpGetTemplate(
                'associated-courses-product-page.php',
                array(
                    'product_id' => $product_id,
                ),
                '',
                BRIDGE_WOOCOMMERCE_PLUGIN_DIR.'public/templates/'
            );
        }
    }
}
