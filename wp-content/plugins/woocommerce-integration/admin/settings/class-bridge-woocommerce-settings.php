<?php

namespace app\wisdmlabs\edwiserBridge;

/*
 * EDW General Settings
 *
 * @link       https://edwiser.org
 * @since      1.0.0
 *
 * @package    Edwiser Bridge
 * @subpackage Edwiser Bridge/admin
 * @author     WisdmLabs <support@wisdmlabs.com>
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('WooIntSettings')) :

    /**
     * WooIntSettings.
     */
    class WooIntSettings extends EBSettingsPage
    {

        /**
         * Constructor.
         */
        public function __construct()
        {
            $this->_id = 'woo_int_settings';
            $this->label = __('Woo Integration', WOOINT_TD);

            add_filter('eb_settings_tabs_array', array($this, 'addSettingsPage'), 20);
            add_action('eb_settings_'.$this->_id, array($this, 'output'));
            add_action('eb_settings_save_'.$this->_id, array($this, 'save'));
        }

        /**
         * Get settings array.
         *
         * @since  1.0.0
         *
         * @return array
         */
        public function getSettings()
        {
            $settings = apply_filters(
                'wooint_settings_fields',
                array(
                    array(
                        'title' => __('WooCommerce Integration Options', WOOINT_TD),
                        'type' => 'title',
                        'desc' => '',
                        'id' => 'wooint_options',
                    ),
                    // Adding Enable Redirection Option On Checkout Page
                    array(
                        'title' => __('Enable Redirection', WOOINT_TD),
                        'desc' => __('This enables user to redirect to <strong>My Courses</strong> page after order completion.', WOOINT_TD),
                        'id' => 'wi_enable_redirect',
                        'default' => 'yes',
                        'type' => 'checkbox',
                        'autoload' => false,
                    ),
                    array(
                        'title' => __('One Click Checkout', WOOINT_TD),
                        'desc' => __('This enables <strong>Buy Now</strong> button for simple products. Using this, users will be directly redirected to <strong>Single Cart Checkout</strong> page and the product will be added to their cart.', WOOINT_TD),
                        'id' => 'wi_enable_buynow',
                        'default' => 'no',
                        'type' => 'checkbox',
                        'autoload' => false,
                    ),
                    array(
                        'title' => __('Buy Now Button Text', WOOINT_TD),
                        'desc' => '<br />' .__('This text will be shown on <strong>Buy Now</strong> button. Default will be <strong>Buy Now</strong>.', WOOINT_TD),
                        'id' => 'wi_buy_now_text',
                        'default' => __('Buy Now', WOOINT_TD),
                        'type' => 'text',
                        'css' => 'min-width:300px;'
                    ),
                    array(
                        'title' => __('Single Cart Checkout Page', WOOINT_TD),
                        'desc' => '<br/>'.__('Add shortcode <code>[bridge_woo_single_cart_checkout]</code> in the selected page.', WOOINT_TD),
                        'id' => 'wi_scc_page_id',
                        'type' => 'single_select_page',
                        'default' => '',
                        'css' => 'min-width:300px;',
                        'args' => array(
                            'show_option_none' =>__('- Select a page -', WOOINT_TD),
                            'option_none_value' => '',
                        )
                    ),
                    array('type' => 'sectionend', 'id' => 'wooint_options'),
                    )
            );
            
            return apply_filters('eb_get_settings_'.$this->_id, $settings);
        }

        /**
         * Save settings.
         *
         * @since  1.0.0
         */
        public function save()
        {
            $settings = $this->getSettings();

            EbAdminSettings::saveFields($settings);
        }
    }

endif;

return new WooIntSettings();
