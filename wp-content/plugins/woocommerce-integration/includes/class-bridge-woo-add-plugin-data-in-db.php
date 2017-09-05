<?php

namespace NmBridgeWoocommerce;

if (! class_exists('BridgeWooAddPluginDataInDB')) {

    class BridgeWooAddPluginDataInDB
    {

        /**
         *
         * @var string Short Name for plugin.
         */
        private $pluginShortName = '';

        /**
         *
         * @var string Slug to be used in url and functions name
         */
        private $pluginSlug = '';

        /**
         *
         * @var string stores the current plugin version
         */
        private $pluginVersion = '';

        /**
         *
         * @var string Handles the plugin name
         */
        private $pluginName = '';

        /**
         *
         * @var string  Stores the URL of store. Retrieves updates from
         *              this store
         */
        private $storeUrl = '';

        /**
         *
         * @var string  Name of the Author
         */
        private $authorName = '';

        public function __construct($plugin_data)
        {

            $this->authorName       = $plugin_data[ 'authorName' ];
            $this->pluginName       = $plugin_data[ 'pluginName' ];
            $this->pluginShortName = $plugin_data[ 'pluginShortName' ];
            $this->pluginSlug       = $plugin_data[ 'pluginSlug' ];
            $this->pluginVersion    = $plugin_data[ 'pluginVersion' ];
            $this->storeUrl         = $plugin_data[ 'storeUrl' ];
            $this->pluginTextDomain = $plugin_data[ 'pluginTextDomain' ];

            //add_action('admin_menu', array( $this, 'licenseMenu' ));

            // Licensing info under EB tab.
            add_filter('eb_setting_messages', array($this, 'licenseMessages'), 5, 1);
            add_filter('eb_licensing_information', array($this, 'licenseInformation'), 5, 1);
            add_action('init', array($this, 'addData'), 5);
            add_action('admin_menu', array( $this, 'licenseMenu' ));
        }


        public function licenseInformation($licensing_info)
        {
            $license_key = get_option('edd_' . $this->pluginSlug . '_license_key');

            //Get License Status
            $status = get_option('edd_' . $this->pluginSlug . '_license_status');

            $_sites = BridgeWooGetPluginData::getSiteList($this->pluginSlug);

            $display = "";
            if (!empty($_sites) || $_sites != "") {
                $display = "<ul>" . $_sites . "</ul>";
            }

            $renew_link = get_option('wdm_' . $this->pluginSlug . '_product_site');


            if (($status == "valid" || $status == "expired") && (empty($display) || $display == "")) {
                $license_key_text = '<input id="edd_' . $this->pluginSlug . '_license_key" name="edd_' . $this->pluginSlug . '_license_key" type="text" class="regular-text" value="' . esc_attr($license_key) . '" readonly/>';
            } else {
                $license_key_text = '<input id="edd_' . $this->pluginSlug . '_license_key" name="edd_' . $this->pluginSlug . '_license_key" type="text" class="regular-text" value="' . esc_attr($license_key) . '" />';
            }
            $license_status_text= $this->getLicenseStatusText($status, @$display);

            ob_start();
            wp_nonce_field('edd_' . $this->pluginSlug . '_nonce', 'edd_' . $this->pluginSlug . '_nonce');
            $nonce = ob_get_contents();
            ob_end_clean();

            if ($status !== false && $status == 'valid') {
                $license_action = '<input type = "submit" class = "button-primary" name = "edd_' . $this->pluginSlug . '_license_deactivate" value = "' . __('Deactivate License', WOOINT_TD) . '"/>';
            } elseif ($status == 'expired' && (!empty($display) || $display != "")) {
                $license_action = '<input type="submit" class="button-primary" name="edd_' . $this->pluginSlug . '_license_activate" value="' . __('Activate License', WOOINT_TD) . '"/>';
                $license_action .='<input type="button" class="button-primary" name="edd_' . $this->pluginSlug . '_license_renew" value="' . __('Renew License', WOOINT_TD) . '" onclick="window.open(\'' . $renew_link . '\')"/>';
            } elseif ($status == 'expired') {
                $license_action = '<input type="submit" class="button-primary" name="edd_' . $this->pluginSlug . '_license_deactivate" value="' . __('Deactivate License', WOOINT_TD) . '"/>';
                $license_action .='<input type="button" class="button-primary" name="edd_' . $this->pluginSlug . '_license_renew" value="' . __('Renew License', WOOINT_TD) . '" onclick="window.open(\'' . $renew_link . '\')"/>';
            } else {
                $license_action = '<input type="submit" class="button-primary" name="edd_' . $this->pluginSlug . '_license_activate" value="' . __('Activate License', WOOINT_TD) . '"/>';
            }

            $info = array(
                'plugin_name' => $this->pluginName,
                'plugin_slug' => $this->pluginSlug,
                'license_key' => $license_key_text,
                'license_status' => $license_status_text,
                'activate_license' => $nonce . $license_action
            );

            $licensing_info[] = $info;

            return $licensing_info;
        }

        public function licenseMessages($eb_licensing_messages)
        {
            $status = get_option('edd_' . $this->pluginSlug . '_license_status');

            include_once(plugin_dir_path(__FILE__) . 'class-bridge-woo-get-plugin-data.php');

            if (isset($GLOBALS['wdm_server_null_response']) && $GLOBALS['wdm_server_null_response'] == true) {
                $status = 'server_did_not_respond';
            } elseif (isset($GLOBALS['wdm_license_activation_failed']) && $GLOBALS['wdm_license_activation_failed'] == true) {
                $status = 'license_activation_failed';
            } elseif (isset($_POST['edd_' . $this->pluginSlug .'_license_key']) && empty($_POST['edd_' . $this->pluginSlug .'_license_key'])) {
                $status = 'no_license_key_entered';
            }

            $active_site = BridgeWooGetPluginData::getSiteList($this->pluginSlug);

            $display = "";
            if (!empty($active_site) || $active_site != "") {
                $display = "<ul>" . $active_site . "</ul>";
            }
            if (isset($_POST['edd_' . $this->pluginSlug . '_license_key'])) {
                //Handle Submission of inputs on license page
                if (isset($_POST['edd_' . $this->pluginSlug . '_license_key']) && empty($_POST['edd_' . $this->pluginSlug . '_license_key'])) {
                    //If empty, show error message
                    add_settings_error(
                        'wdm_' . $this->pluginSlug . '_errors',
                        esc_attr('settings_updated'),
                        sprintf(__('Please enter license key for %s', WOOINT_TD), $this->pluginName),
                        'error'
                    );
                } elseif ($status == 'server_did_not_respond') {
                    add_settings_error(
                        'wdm_' . $this->pluginSlug . '_errors',
                        esc_attr('settings_updated'),
                        __('No response from server. Please try again later.', WOOINT_TD),
                        'error'
                    );
                } elseif ($status == 'no_activations_left' && (!empty($display) || $display != "")) { //Invalid license key   and site
                    add_settings_error(
                        'wdm_' . $this->pluginSlug . '_errors',
                        esc_attr('settings_updated'),
                        sprintf(
                            __('License Key for %s is already activated at : %s', WOOINT_TD),
                            $this->pluginName,
                            $display
                        ),
                        'error'
                    );
                } elseif ($status == 'invalid') { //Invalid license key --
                    add_settings_error(
                        'wdm_' . $this->pluginSlug . '_errors',
                        esc_attr('settings_updated'),
                        sprintf(__('Please enter valid license key for %s', WOOINT_TD), $this->pluginName),
                        'error'
                    );
                } elseif ($status == 'site_inactive' && (!empty($display) || $display != "")) { //Invalid license key   and site inactive
                    add_settings_error(
                        'wdm_' . $this->pluginSlug . '_errors',
                        esc_attr('settings_updated'),
                        sprintf(__('License Key for %s is already activated at : %s', WOOINT_TD), $this->pluginName, $display),
                        'error'
                    );
                } elseif ($status == 'site_inactive') { //Site is inactive
                    add_settings_error(
                        'wdm_' . $this->pluginSlug . '_errors',
                        esc_attr('settings_updated'),
                        __('Site inactive(Press Activate license to activate plugin)', WOOINT_TD),
                        'error'
                    );
                } elseif ('deactivated' == $status) { //Site is inactive --

                    add_settings_error(
                        'wdm_' . $this->pluginSlug . '_errors',
                        esc_attr('settings_updated'),
                        sprintf(__('License Key for %s is deactivated', WOOINT_TD), $this->pluginName),
                        'updated'
                    );
                } elseif ($status!==false) {
                    $this->ifStatusFalse($status);
                }
            }

            ob_start();
            settings_errors('wdm_' . $this->pluginSlug . '_errors');
            $bwoo_setting_messages = ob_get_contents();
            ob_end_clean();
            return $eb_licensing_messages . $bwoo_setting_messages;
        }

        public function getLicenseStatusText($status, $display)
        {
            //error_log('@ status: ' . $status);
            if ($status !== false && $status == 'valid') {
                $license_status_text = '<span style="color:green;">' . __('Active', WOOINT_TD) . '</span>';
                return $license_status_text;
            } elseif (get_option('edd_' . $this->pluginSlug . '_license_status') == 'site_inactive') {
                $license_status_text = '<span style="color:red;">' . __('Not Active', WOOINT_TD) . '</span>';
                return $license_status_text;
            } elseif (get_option('edd_' . $this->pluginSlug . '_license_status') == 'expired' && (!empty($display) || $display != "")) {
                $license_status_text = '<span style="color:red;">' . __('Expired', WOOINT_TD) . '</span>';
                return $license_status_text;
            } elseif (get_option('edd_' . $this->pluginSlug . '_license_status') == 'expired') {
                $license_status_text = '<span style="color:red;">' . __('Expired', WOOINT_TD) . '</span>';
                return $license_status_text;
            } elseif (get_option('edd_' . $this->pluginSlug . '_license_status') == 'invalid') {
                $license_status_text = '<span style="color:red;">' . __('Invalid Key', WOOINT_TD) . '</span>';
                return $license_status_text;
            } else {
                $license_status_text = '<span style="color:red;">' . __('Not Active', WOOINT_TD) . '</span>';
                return $license_status_text;
            }
        }

        public function ifStatusFalse($status)
        {
            $_sites = BridgeWooGetPluginData::getSiteList($this->pluginSlug);
            $display = "";
            if (!empty($_sites) || $_sites != "") {
                $display = "<ul>" . $_sites . "</ul>";
            }

            if ($status !== false && $status == 'valid') { //Valid license key
                    add_settings_error(
                        'wdm_' . $this->pluginSlug . '_errors',
                        esc_attr('settings_updated'),
                        sprintf(__('License key for %s is activated.', WOOINT_TD), $this->pluginName),
                        'updated'
                    );
            } elseif ($status !== false && $status == 'expired' && (!empty($display) || $display != "")) { //Expired license key
                    add_settings_error(
                        'wdm_' . $this->pluginSlug . '_errors',
                        esc_attr('settings_updated'),
                        sprintf(__('License key for %s has Expired. Please, Renew it.<br/>Your License Key is already activated at : %s', WOOINT_TD), $this->pluginName, $display),
                        'error'
                    );
            } elseif ($status !== false && $status == 'expired') { //Expired license key
                    add_settings_error(
                        'wdm_' . $this->pluginSlug . '_errors',
                        esc_attr('settings_updated'),
                        sprintf(__('License key for %s has Expired. Please, Renew it.', WOOINT_TD), $this->pluginName),
                        'error'
                    );
            } elseif ($status !== false && $status == 'disabled') { //Disabled license key
                    add_settings_error(
                        'wdm_' . $this->pluginSlug . '_errors',
                        esc_attr('settings_updated'),
                        sprintf(__('License key for %s is Disabled.', WOOINT_TD), $this->pluginName),
                        'error'
                    );
            }
        }












        public function licenseMenu()
        {
            add_plugins_page(
                sprintf(__('%s License', $this->pluginTextDomain), $this->pluginShortName),
                sprintf(__('%s License', $this->pluginTextDomain), $this->pluginShortName),
                apply_filters($this->pluginSlug . '_license_page_capability', 'manage_options'),
                $this->pluginSlug . '-license',
                array($this, 'licensePage')
            );
        }

        public function licensePage()
        {
            include_once trailingslashit(dirname(dirname(__FILE__))) . 'admin/templates/license-page.php';
        }


        /**
         * Updates license status in the database and returns status value.
         *
         * @param object $licenseData License data returned from server
         * @param  string $pluginSlug  Slug of the plugin. Format of the key in options table is 'edd_<$pluginSlug>_license_status'
         *
         * @return string              Returns status of the license
         */
        public static function updateStatus($licenseData, $pluginSlug)
        {
            $status = '';
            if (isset($licenseData->success)) {
                // Check if request was successful
                if ($licenseData->success === false) {
                    if (! isset($licenseData->error) || empty($licenseData->error)) {
                        $licenseData->error = 'invalid';
                    }
                }
                // Is there any licensing related error?
                $status = self::checkLicensingError($licenseData);

                if (!empty($status)) {
                    update_option('edd_'.$pluginSlug.'_license_status', $status);

                    return $status;
                }
                $status = 'invalid';
                //Check license status retrieved from EDD
                $status = self::checkLicenseStatus($licenseData, $pluginSlug);
            }

            $status = (empty($status)) ? 'invalid' : $status;
                    update_option('edd_' . $pluginSlug . '_license_status', $status);

            return $status;
        }


        /**
         * Checks if there is any error in response.
         *
         * @param object $licenseData License Data obtained from server
         *
         * @return string empty if no error or else error
         */
        public static function checkLicensingError($licenseData)
        {
            $status = '';
            if (isset($licenseData->error) && !empty($licenseData->error)) {
                switch ($licenseData->error) {
                    case 'revoked':
                        $status = 'disabled';
                        break;

                    case 'expired':
                        $status = 'expired';
                        break;
                }
            }

                    return $status;
        }


        public static function checkLicenseStatus($licenseData, $pluginSlug)
        {
                $status = 'invalid';
            if (isset($licenseData->license) && !empty($licenseData->license)) {
                switch ($licenseData->license) {
                    case 'invalid':
                        $status = 'invalid';
                        if (isset($licenseData->activations_left) && $licenseData->activations_left == '0') {
                            /********** change **************/
                            include_once plugin_dir_path(__FILE__).'class-bridge-woo-get-plugin-data.php';
                            $activeSite = BridgeWooGetPluginData::getSiteList($pluginSlug);

                            if (!empty($activeSite) || $activeSite != '') {
                                $status = 'no_activations_left';
                            } else {
                                $status = 'valid';
                            }
                        }

                        break;

                    case 'failed':
                        $status = 'failed';
                        $GLOBALS[ 'wdm_license_activation_failed' ] = true;
                        break;

                    default:
                        $status = $licenseData->license;
                }
            }

               return $status;
        }


        /**
         * Checks if any response received from server or not after making an API call. If no response obtained, then sets next api request after 24 hours.
         *
         * @param object $licenseData         License Data obtained from server
         * @param  string   $currentResponseCode    Response code of the API request
         * @param  array    $validResponseCode      Array of acceptable response codes
         *
         * @return bool returns false if no data obtained. Else returns true.
         */
        public function checkIfNoData($licenseData, $currentResponseCode, $validResponseCode)
        {
            if ($licenseData == null || ! in_array($currentResponseCode, $validResponseCode)) {
                $GLOBALS[ 'wdm_server_null_response' ] = true;
                set_transient('wdm_' . $this->pluginSlug . '_license_trans', 'server_did_not_respond', 60 * 60 * 24);

                return false;
            }

            return true;
        }


        /**
         * Activates License.
         */
        public function activateLicense()
        {

            $licenseKey = trim($_POST[ 'edd_' . $this->pluginSlug . '_license_key' ]);

            if ($licenseKey) {
                update_option('edd_' . $this->pluginSlug . '_license_key', $licenseKey);
                $apiParams = array(
                    'edd_action'         => 'activate_license',
                    'license'            => $licenseKey,
                    'item_name'          => urlencode($this->pluginName),
                    'current_version' => $this->pluginVersion,
                );

                $response = wp_remote_get(add_query_arg($apiParams, $this->storeUrl), array(
                    'timeout' => 15, 'sslverify' => false, 'blocking' => true, ));

                if (is_wp_error($response)) {
                    return false;
                }

                $licenseData = json_decode(wp_remote_retrieve_body($response));

                $validResponseCode = array( '200', '301' );

                $currentResponseCode = wp_remote_retrieve_response_code($response);

                $isDataAvailable = $this->checkIfNoData($licenseData, $currentResponseCode, $validResponseCode);
                //cspPrintDebug($licenseData); exit;
                if ($isDataAvailable == false) {
                    return;
                }

                $expirationTime = $this->getExpirationTime($licenseData);
                $currentTime = time();

                if (isset($licenseData->expires) && ($licenseData->expires !== false) && ($licenseData->expires != 'lifetime') && $expirationTime <= $currentTime && $expirationTime != 0 && !isset($licenseData->error)) {
                    $licenseData->error = 'expired';
                }

                if (isset($licenseData->renew_link) && (!empty($licenseData->renew_link) || $licenseData->renew_link != '')) {
                    update_option('wdm_' . $this->pluginSlug . '_product_site', $licenseData->renew_link);
                }

                $this->updateNumberOfSitesUsingLicense($licenseData);

                $licenseStatus = self::updateStatus($licenseData, $this->pluginSlug);

                $this->setTransientOnActivation($licenseStatus);
            }
        }


        public function getExpirationTime($licenseData)
        {
            $expirationTime = 0;
            if (isset($licenseData->expires)) {
                $expirationTime = strtotime($licenseData->expires);
            }

            return $expirationTime;
        }

        public function updateNumberOfSitesUsingLicense($licenseData)
        {
            if (isset($licenseData->sites) && (!empty($licenseData->sites) || $licenseData->sites != '')) {
                update_option('wdm_' . $this->pluginSlug . '_license_key_sites', $licenseData->sites);
                update_option('wdm_' . $this->pluginSlug . '_license_max_site', $licenseData->license_limit);
            } else {
                update_option('wdm_' . $this->pluginSlug . '_license_key_sites', '');
                update_option('wdm_' . $this->pluginSlug . '_license_max_site', '');
            }
        }


        public function setTransientOnActivation($licenseStatus)
        {
            $transVar = get_transient('wdm_' . $this->pluginSlug . '_license_trans');
            if (isset($transVar)) {
                delete_transient('wdm_' . $this->pluginSlug . '_license_trans');
                if (! empty($licenseStatus)) {
                    if ($licenseStatus == 'valid') {
                        $time = 60 * 60 * 24 * 7;
                    } else {
                        $time = 60 * 60 * 24;
                    }
                    set_transient('wdm_' . $this->pluginSlug . '_license_trans', $licenseStatus, $time);
                }
            }
        }

        /**
         * Deactivates License.
         */
        public function deactivateLicense()
        {
            $licenseKey = trim(get_option('edd_' . $this->pluginSlug . '_license_key'));

            if ($licenseKey) {
                $apiParams = array(
                    'edd_action'         => 'deactivate_license',
                    'license'            => $licenseKey,
                    'item_name'          => urlencode($this->pluginName),
                    'current_version' => $this->pluginVersion,
                );

                $response = wp_remote_get(add_query_arg($apiParams, $this->storeUrl), array(
                    'timeout' => 15, 'sslverify' => false, 'blocking' => true, ));

                if (is_wp_error($response)) {
                    return false;
                }

                $licenseData = json_decode(wp_remote_retrieve_body($response));

                $validResponseCode = array( '200', '301' );

                $currentResponseCode = wp_remote_retrieve_response_code($response);

                $isDataAvailable = $this->checkIfNoData($licenseData, $currentResponseCode, $validResponseCode);

                if ($isDataAvailable == false) {
                    return;
                }

                if ($licenseData->license == 'deactivated' || $licenseData->license == 'failed') {
                    update_option('edd_' . $this->pluginSlug . '_license_status', 'deactivated');
                }
                //delete_transient( 'wdm_' . $this->pluginSlug . '_license_trans' );
                delete_transient('wdm_' . $this->pluginSlug . '_license_trans');

                set_transient('wdm_' . $this->pluginSlug . '_license_trans', $licenseData->license, 0);
            }
        }


        public function addData()
        {
            if (isset($_POST[ 'edd_' . $this->pluginSlug . '_license_activate' ])) {
                if (! check_admin_referer('edd_' . $this->pluginSlug . '_nonce', 'edd_' . $this->pluginSlug . '_nonce')) {
                    return;
                }
                $this->activateLicense();
            } elseif (isset($_POST[ 'edd_' . $this->pluginSlug . '_license_deactivate' ])) {
                if (! check_admin_referer('edd_' . $this->pluginSlug . '_nonce', 'edd_' . $this->pluginSlug . '_nonce')) {
                    return;
                }
                $this->deactivateLicense();
            }
        }
    }
}
