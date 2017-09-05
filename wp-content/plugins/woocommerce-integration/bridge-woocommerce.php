<?php
/**
 * @link              https://wisdmlabs.com
 * @since             1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce Integration
 * Plugin URI:        https://wisdmlabs.com
 * Description:       Integrates Moodle Courses with WooCommerce product & enrolls User on Product purchase. This plugin is extension of - Edwiser Bridge & WooCommerce.
 * Version:           1.1.4
 * Author:            WisdmLabs
 * Author URI:        https://wisdmlabs.com
 * Text Domain:       woocommerce-integration
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('WOOINT_TD', 'woocommerce-integration');

$wooint_plugin_data = array(
    'pluginShortName' => 'WooCommerce Integration',
    'pluginSlug' => 'woocommerce_integration',
    'pluginVersion' => '1.1.4',
    'pluginName' => 'WooCommerce Integration',
    'storeUrl' => 'https://wisdmlabs.com/check-update',
    'authorName' => 'WisdmLabs',
    'pluginTextDomain'  =>  WOOINT_TD
);

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-bridge-woocommerce-activator.php.
 */
function activate_bridge_woocommerce()
{
    require_once plugin_dir_path(__FILE__).'includes/class-bridge-woocommerce-activator.php';
    NmBridgeWoocommerce\BridgeWoocommerceActivator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-bridge-woocommerce-deactivator.php.
 */
function deactivate_bridge_woocommerce()
{
    require_once plugin_dir_path(__FILE__).'includes/class-bridge-woocommerce-deactivator.php';
    NmBridgeWoocommerce\BridgeWoocommerceDeactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_bridge_woocommerce');
register_deactivation_hook(__FILE__, 'deactivate_bridge_woocommerce');

function bridge_woo_update_check()
{
    global $wooint_plugin_data;

    $get_plugin_version = get_option($wooint_plugin_data['pluginSlug'].'_version', false);

    if (false === $get_plugin_version || $get_plugin_version != $wooint_plugin_data['pluginVersion']) {
        require_once plugin_dir_path(__FILE__).'includes/class-bridge-woocommerce-activator.php';
        NmBridgeWoocommerce\BridgeWoocommerceActivator::activate();

        update_option($wooint_plugin_data['pluginSlug'].'_version', $wooint_plugin_data['pluginVersion']);
    }
}

/*
 * Check for Plugin updatation
 * @since 1.0.4
 */
add_action('plugins_loaded', 'bridge_woo_update_check');

include_once 'includes/class-bridge-woo-add-plugin-data-in-db.php';
new NmBridgeWoocommerce\BridgeWooAddPluginDataInDB($wooint_plugin_data);

/*
 * This code checks if new version is available
*/
if (!class_exists('BridgeWooPluginUpdater')) {
    include 'includes/class-bridge-woo-plugin-updater.php';
}

$l_key = trim(get_option('edd_'.$wooint_plugin_data['pluginSlug'].'_license_key'));

// setup the updater
new NmBridgeWoocommerce\BridgeWooPluginUpdater($wooint_plugin_data['storeUrl'], __FILE__, array(
    'version' => $wooint_plugin_data['pluginVersion'], // current version number
    'license' => $l_key, // license key (used get_option above to retrieve from DB)
    'item_name' => $wooint_plugin_data['pluginName'], // name of this plugin
    'author' => $wooint_plugin_data['authorName'], //author of the plugin
    ));

$l_key = null;

/*
    * Check if WooCommerce is Active & Edwiser - Base plugin active or not
    */

    $array_of_activated_plugins = apply_filters('active_plugins', get_option('active_plugins'));

if (in_array('woocommerce/woocommerce.php', $array_of_activated_plugins) && in_array('edwiser-bridge/edwiser-bridge.php', $array_of_activated_plugins)) {
    include_once plugin_dir_path(__FILE__).'includes/class-bridge-woo-get-plugin-data.php';

    /**
         * The core plugin class that is used to define internationalization,
         * admin-specific hooks, and public-facing site hooks.
         */
    require plugin_dir_path(__FILE__).'includes/class-bridge-woocommerce.php';

    /**
         * Begins execution of the plugin.
         *
         * Since everything within the plugin is registered via hooks,
         * then kicking off the plugin from this point in the file does
         * not affect the page life cycle.
         *
         * @since    1.0.0
         */
    function run_bridge_woocommerce()
    {
        $plugin = new NmBridgeWoocommerce\BridgeWoocommerce();
        $plugin->run();
    }
    run_bridge_woocommerce();
} else {
    // Displaying user plugin not activated message
    add_action('admin_notices', 'base_plugin_inactive_notice');
}

if (!function_exists('base_plugin_inactive_notice')) {
    function base_plugin_inactive_notice()
    {
        if (current_user_can('activate_plugins')) {
            global $wooint_plugin_data;

            $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
            if (!in_array('woocommerce/woocommerce.php', $active_plugins) && !in_array('edwiser-bridge/edwiser-bridge.php', $active_plugins)) {
                ?>
        <div id="message" class="error">
            <p>
                <?php
                printf(
                    __('%s deactivated. Install and activate %s and %s for %s to work.', WOOINT_TD),
                    '<strong>' . $wooint_plugin_data['pluginName'] . '</strong>',
                    '<a href="http://wordpress.org/extend/plugins/woocommerce/">' . __('WooCommerce', WOOINT_TD) . '</a>',
                    '<a href="http://wordpress.org/extend/plugins/edwiser-bridge/">' . __('EdwiserBridge', WOOINT_TD) . '</a>',
                    $wooint_plugin_data['pluginName']
                );
                ?>
            </p>
        </div>
            <?php
            } elseif (!in_array('woocommerce/woocommerce.php', $active_plugins)) {
                ?>
        <div id="message" class="error">
            <p>
                <?php
                printf(
                    __('%s deactivated. Install and activate %s for %s to work.', WOOINT_TD),
                    '<strong>' . $wooint_plugin_data['pluginName'] . '</strong>',
                    '<a href="http://wordpress.org/extend/plugins/woocommerce/">' . __('WooCommerce', WOOINT_TD) . '</a>',
                    $wooint_plugin_data['pluginName']
                );
                ?>
            </p>
        </div>
                <?php
            } elseif (!in_array('edwiser-bridge/edwiser-bridge.php', $active_plugins)) {
                ?>
        <div id="message" class="error">
            <p>
                <?php
                printf(
                    __('%s deactivated. Install and activate %s for %s to work.', WOOINT_TD),
                    '<strong>' . $wooint_plugin_data['pluginName'] . '</strong>',
                    '<a href="http://wordpress.org/extend/plugins/edwiser-bridge/">' . __('EdwiserBridge', WOOINT_TD) . '</a>',
                    $wooint_plugin_data['pluginName']
                );
                ?>
            </p>
        </div>
                <?php
            }
        }
        // Deactivate plugin
        deactivate_plugins(plugin_basename(__FILE__));
        // Removing plugin activated notice on deactivation of plugin
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }
    }
}
