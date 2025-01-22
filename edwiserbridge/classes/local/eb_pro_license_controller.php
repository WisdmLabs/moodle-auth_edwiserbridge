<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * License controller.
 * Functionality to manage licensing of Edwiser Bridge PRO version.
 *
 * @package    auth_edwiserbridge
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_edwiserbridge\local;

 /**
  * License controller class.
  */
class eb_pro_license_controller {
    /**
     * @var string Slug to be used in url and functions name
     */
    private $pluginslug = '';

    /**
     * @var string stores the current plugin version
     */
    private $pluginversion = '';

    /**
     * @var string store the plugin short name
     */
    private $pluginshortname = '';

    /**
     * @var string Handles the plugin name
     */
    private $pluginname = '';

    /**
     * @var string  Stores the URL of store. Retrieves updates from
     *              this store
     */
    private $storeurl = '';

    /**
     * @var string  Name of the Author
     */
    private $authorname = '';

    /**
     * @var string  Short name of the plugin
     */
    public static $responsedata;

    /**
     * Developer Note: This variable is used everywhere to check license information and verify the data.
     * Change the Name of this variable in this file wherever it appears and also remove this comment
     * After you are done with adding Licensing
     * @var array Stores the data of the plugin
     */
    public $edwiserbridgedata = [
        'plugin_short_name' => 'Edwiser Bridge - Moodle', // Plugins short name appears on the License Menu Page.
        'plugin_slug'       => 'moodle_edwiser_bridge', // Plugin Slug.
        'plugin_version'    => '3.0.0', // Current Version of the plugin.
        'plugin_name'       => 'Edwiser Bridge - Moodle', // Under this Name product should be created on WisdmLabs Site.
        'store_url'         => 'https://edwiser.org/check-update', // Edwiser Store URL.
        'author_name'       => 'WisdmLabs', // Author Name.
    ];

    /**
     * Initializes the plugin data on instance creation.
     * This method sets the values of various properties of the class
     * based on the data stored in the $edwiserbridgedata array.
     */
    public function __construct() {
        $this->authorname      = $this->edwiserbridgedata['author_name'];
        $this->pluginname      = $this->edwiserbridgedata['plugin_name'];
        $this->pluginshortname = $this->edwiserbridgedata['plugin_short_name'];
        $this->pluginslug      = $this->edwiserbridgedata['plugin_slug'];
        $this->pluginversion   = $this->edwiserbridgedata['plugin_version'];
        $this->storeurl        = $this->edwiserbridgedata['store_url'];
    }

    /**
     * Updates the status of the license.
     *
     * @param object $licensedata License data
     * @return string License status
     */
    public function update_status($licensedata) {
        $status = "";
        if ((empty($licensedata->success)) && isset($licensedata->error) && ($licensedata->error == "expired")) {
            $status = 'expired';
            $this->add_notice(get_string('license_expired', 'auth_edwiserbridge'));
        } else if ($licensedata->license == 'invalid' && isset($licensedata->error) && $licensedata->error == "revoked") {
            $status = 'disabled';
            $this->add_notice(get_string('license_revoked', 'auth_edwiserbridge'));
        } else if ($licensedata->license == 'invalid' ||
                (isset($licensedata->activations_left) && $licensedata->activations_left == "0")) {
            $status = 'invalid';
            if (isset($licensedata->activations_left) && $licensedata->activations_left == "0") {
                $this->add_notice(get_string('license_no_activation_left', 'auth_edwiserbridge'));
            } else {
                $this->add_notice(get_string('license_invalid', 'auth_edwiserbridge'));
            }
        } else if ($licensedata->license == 'failed') {
            $status = 'failed';
            $this->add_notice(get_string('license_failed', 'auth_edwiserbridge'));
        } else {
            $status = $licensedata->license;
        }

        // Delete previous license status.
        unset_config('edd_' . $this->pluginslug. '_license_status', 'auth_edwiserbridge');

        // Update license status.
        set_config('edd_' . $this->pluginslug. '_license_status', $status, 'auth_edwiserbridge');

        return $status;
    }

    /**
     * Checks if there is no license data or if the current response code is not in the valid response codes.
     *
     * @param string $licensedata          The license data.
     * @param int    $currentresponsecode  The current response code.
     * @param array  $validresponsecode    The array of valid response codes.
     * @return bool   True if there is no data or the response code is not valid, false otherwise.
     */
    public function check_if_no_data($licensedata, $currentresponsecode, $validresponsecode) {

        if ($licensedata == null || ! in_array($currentresponsecode, $validresponsecode)) {
            // Delete previous record.
            unset_config('edd_' . $this->pluginslug. '_license_trans', 'auth_edwiserbridge');

            // Insert new license trans.
            set_config('edd_' . $this->pluginslug. '_license_trans', json_encode(['server_did_not_respond', time() + (60 * 60 * 24)]), 'auth_edwiserbridge');

            return false;
        }
        return true;
    }

    /**
     * Activates the license key for the plugin.
     *
     * @param string $licensekey The license key to activate.
     * @return void
     */
    public function activate_license($licensekey) {
        global $CFG;
    
        if ($licensekey) {
            // Delete previous license key.
            unset_config('edd_' . $this->pluginslug. '_license_key', 'auth_edwiserbridge');
    
            // Insert new license key.
            set_config('edd_' . $this->pluginslug. '_license_key', $licensekey, 'auth_edwiserbridge');
    
            // Use Moodle's curl class.
            include_once($CFG->libdir . '/filelib.php');
            $curl = new \curl();
    
            // Set the request data.
            $postdata = [
                'edd_action' => 'activate_license',
                'license' => $licensekey,
                'item_name' => urlencode($this->pluginname),
                'current_version' => $this->pluginversion,
                'url' => urlencode($CFG->wwwroot),
            ];
    
            // Set user agent.
            $useragent = $_SERVER['HTTP_USER_AGENT'] . ' - ' . $CFG->wwwroot;
            $curl->setHeader('User-Agent: ' . $useragent);
    
            // Execute POST request.
            $options = [
                'CURLOPT_RETURNTRANSFER' => true,
                'CURLOPT_TIMEOUT' => 30,
                'CURLOPT_SSL_VERIFYPEER' => false,
            ];
            $resp = $curl->post($this->storeurl, $postdata, $options);
    
            $currentresponsecode = $curl->info['http_code'];
            $licensedata = json_decode($resp);
    
            $validresponsecode = ['200', '301'];
    
            $isdataavailable = $this->check_if_no_data($licensedata, $currentresponsecode, $validresponsecode);
    
            if ($isdataavailable == false) {
                return;
            }
    
            $expirytime = 0;
            if (isset($licensedata->expires)) {
                $expirytime = strtotime($licensedata->expires);
            }
            $currenttime = time();
    
            if (isset($licensedata->expires) && ($licensedata->expires !== false) &&
                    ($licensedata->expires != 'lifetime') && $expirytime <= $currenttime && $expirytime != 0) {
                $licensedata->error = "expired";
            }
    
            if (isset($licensedata->renew_link) && (!empty($licensedata->renew_link) || $licensedata->renew_link != "")) {
                // Delete previous record.
                unset_config('wdm_' . $this->pluginslug . '_product_site', 'auth_edwiserbridge');
    
                // Add renew link.
                set_config('wdm_' . $this->pluginslug . '_product_site', $licensedata->renew_link, 'auth_edwiserbridge');
            }
    
            $licensestatus = $this->update_status($licensedata);
            $this->set_transient_on_activation($licensestatus);
        }
    }    

    /**
     * Sets a transient on plugin activation for frequent license checks.
     *
     * @param string $licensestatus The current license status.
     */
    public function set_transient_on_activation($licensestatus) {
        $transexpired = false;

        // Check license trans.
        $transient = get_config('auth_edwiserbridge', 'wdm_' . $this->pluginslug. '_license_trans');

        if ($transient) {
            $transient = json_decode($transient, true);
            if ( json_last_error() === JSON_ERROR_NONE ) {
                if (is_array($transient) && time() > $transient[1] && $transient[1] > 0) {
    
                    $transexpired = true;
    
                    // Delete previous record.
                    unset_config('wdm_' . $this->pluginslug. '_license_trans', 'auth_edwiserbridge');
                }
            } else {
                $transexpired = true;
                // Delete previous record.
                unset_config('wdm_' . $this->pluginslug. '_license_trans', 'auth_edwiserbridge');
            }
        } else {
            $transexpired = true;
        }

        if ($transexpired == false) {

            // Delete previous license trans.
            unset_config('wdm_' . $this->pluginslug. '_license_trans', 'auth_edwiserbridge');

            if (! empty($licensestatus)) {
                if ($licensestatus == 'valid') {
                    $time = time() + 60 * 60 * 24 * 7;
                } else {
                    $time = time() + 60 * 60 * 24;
                }

                // Insert new license trans.
                set_config('wdm_' . $this->pluginslug. '_license_trans', json_encode([$licensestatus, $time]), 'auth_edwiserbridge');
            }
        }
    }

    /**
     * Deactivates the license key for the plugin.
     * This function retrieves the license key from the database, sends a deactivation request to the plugin store,
     * and updates the license status and transaction records in the database accordingly.
     */
    public function deactivate_license() {
        global $CFG;
    
        $licensekey = get_config('auth_edwiserbridge', 'edd_' . $this->pluginslug . '_license_key');
        if (!empty($licensekey)) {
            include_once($CFG->libdir . '/filelib.php');
            $curl = new \curl();
    
            // Set user agent.
            $useragent = $_SERVER['HTTP_USER_AGENT'] . ' - ' . $CFG->wwwroot;
            $curl->setHeader('User-Agent: ' . $useragent);
    
            // Prepare POST data.
            $postdata = [
                'edd_action' => 'deactivate_license',
                'license' => $licensekey,
                'item_name' => urlencode($this->pluginname),
                'current_version' => $this->pluginversion,
                'url' => urlencode($CFG->wwwroot),
            ];
    
            // Set options and execute the POST request.
            $options = [
                'CURLOPT_RETURNTRANSFER' => true,
                'CURLOPT_TIMEOUT' => 30,
                'CURLOPT_SSL_VERIFYPEER' => false,
            ];
            $resp = $curl->post($this->storeurl, $postdata, $options);
    
            $currentresponsecode = $curl->info['http_code'];
            $licensedata = json_decode($resp);
    
            $validresponsecode = ['200', '301'];
    
            $isdataavailable = $this->check_if_no_data($licensedata, $currentresponsecode, $validresponsecode);
    
            if ($isdataavailable == false) {
                return;
            }
    
            if ($licensedata->license == 'deactivated' || $licensedata->license == 'failed') {
                // Delete previous license status record.
                unset_config('edd_' . $this->pluginslug . '_license_status', 'auth_edwiserbridge');
    
                // Insert deactivated license status.
                set_config('edd_' . $this->pluginslug . '_license_status', 'deactivated', 'auth_edwiserbridge');
            }
    
            // Delete previous license transaction record.
            unset_config('wdm_' . $this->pluginslug . '_license_trans', 'auth_edwiserbridge');
    
            // Insert new license transaction record.
            set_config('wdm_' . $this->pluginslug . '_license_trans', json_encode([$licensedata->license, 0]), 'auth_edwiserbridge');
        }
    }

    /**
     * Retrieves the license data from the database and updates the license status.
     *
     * This method checks for the existence of a license transient in the database. If the transient has expired, it fetches the license key from the database, sends a request to the store to check the license status, and updates the license status in the database accordingly.
     *
     * @return string The response status, either 'available', 'unavailable', or 'server_did_not_respond'.
     */
    public function get_data_from_db() {
        global $CFG;

        if (null !== self::$responsedata) {
            return self::$responsedata;
        }

        $transexpired = false;

        $transient = get_config('auth_edwiserbridge', 'wdm_' . $this->pluginslug . '_license_trans');

        if ($transient) {
            $transient = json_decode($transient, true);
            if ( json_last_error() === JSON_ERROR_NONE ) {
                if (is_array($transient) && time() > $transient[1] && $transient[1] > 0) {
                    $transexpired = true;
    
                    // Delete previous license transient.
                    unset_config('wdm_' . $this->pluginslug . '_license_trans', 'auth_edwiserbridge');
                }
            } else {
                $transexpired = true;
                // Delete previous license transient.
                unset_config('wdm_' . $this->pluginslug . '_license_trans', 'auth_edwiserbridge');
            }

        } else {
            $transexpired = true;
        }

        if ($transexpired == true) {
            $licensekey = get_config('auth_edwiserbridge', 'edd_' . $this->pluginslug . '_license_key');

            if ($licensekey) {
                include_once($CFG->libdir . '/filelib.php');
                $curl = new \curl();

                // Set headers.
                $useragent = $_SERVER['HTTP_USER_AGENT'] . ' - ' . $CFG->wwwroot;
                $curl->setHeader('User-Agent: ' . $useragent);

                // Prepare POST data.
                $postdata = [
                    'edd_action' => 'check_license',
                    'license' => $licensekey,
                    'item_name' => urlencode($this->pluginname),
                    'current_version' => $this->pluginversion,
                    'url' => urlencode($CFG->wwwroot),
                ];

                // Execute POST request.
                $options = [
                    'CURLOPT_RETURNTRANSFER' => true,
                    'CURLOPT_TIMEOUT' => 30,
                    'CURLOPT_SSL_VERIFYPEER' => false,
                ];
                $resp = $curl->post($this->storeurl, $postdata, $options);

                $currentresponsecode = $curl->info['http_code'];
                $licensedata = json_decode($resp);

                $validresponsecode = ['200', '301'];

                if ($licensedata == null || !in_array($currentresponsecode, $validresponsecode)) {
                    // If server does not respond, read current license information.
                    $licensestatus = get_config('auth_edwiserbridge', 'edd_' . $this->pluginslug . '_license_status');

                    if (empty($licensedata)) {
                        // Insert new license transient.
                        set_config('wdm_' . $this->pluginslug . '_license_trans', json_encode(['server_did_not_respond', time() + (60 * 60 * 24)]), 'auth_edwiserbridge');
                    }
                } else {
                    $licensestatus = $licensedata->license;
                }

                if (empty($licensestatus)) {
                    return;
                }

                if (isset($licensedata->license) && !empty($licensedata->license)) {
                    // Delete previous record.
                    unset_config('edd_' . $this->pluginslug . '_license_status', 'auth_edwiserbridge');

                    // Insert new license status.
                    set_config('edd_' . $this->pluginslug . '_license_status', $licensestatus, 'auth_edwiserbridge');
                }

                $this->set_response_data($licensestatus, $this->pluginslug, true);
                return self::$responsedata;
            }
        } else {
            $licensestatus = get_config('auth_edwiserbridge', 'edd_' . $this->pluginslug . '_license_status');

            $this->set_response_data($licensestatus, $this->pluginslug);
            return self::$responsedata;
        }
    }

    /**
     * Sets the response data in static properties.
     *
     * @param string  $licensestatus License status
     * @param string  $pluginslug    Plugin slug
     * @param boolean $settransient  Whether to set a transient
     */
    public function set_response_data($licensestatus, $pluginslug, $settransient = false) {
        if ($licensestatus == 'valid') {
            self::$responsedata = 'available';
        } else if ($licensestatus == 'expired') {
            self::$responsedata = 'available';
        } else {
            self::$responsedata  = 'unavailable';
        }

        if ($settransient) {
            if ($licensestatus == 'valid') {
                $time = 60 * 60 * 24 * 7;
            } else {
                $time = 60 * 60 * 24;
            }

            // Delete previous record.
            unset_config('wdm_' . $pluginslug . '_license_trans', 'auth_edwiserbridge');

            // Insert new license transient.
            set_config('wdm_' . $pluginslug . '_license_trans', json_encode([$licensestatus, time() + (60 * 60 * 24)]), 'auth_edwiserbridge');
        }
    }

    /**
     * This function is used to get a list of sites where the license key is already activated.
     *
     * @return string A list of sites where the license key is activated, or an empty string if the number of activated sites is less than the maximum allowed.
     */
    public function get_site_data() {
        global $CFG;

        $sites = get_config('auth_edwiserbridge', 'wdm_' . $this->pluginslug . '_license_key_sites');

        $max = get_config('auth_edwiserbridge', 'wdm_' . $this->pluginslug . '_license_max_sites');

        $sites2 = json_decode($sites, true);
        if ( JSON_ERROR_NONE !== json_last_error() ) {
            $sites = unserialize($sites2);// For legacy data or data received from licensing server.
            // Delete previous record.
            unset_config('wdm_' . $this->pluginslug . '_license_key_sites', 'auth_edwiserbridge');
            // Add Json encoded data instead of serialized data.
            set_config('wdm_' . $this->pluginslug . '_license_key_sites', json_encode($sites), 'auth_edwiserbridge');
        } else {
            $sites = $sites2;
        }

        $currentsite    = $CFG->wwwroot;
        $currentsite    = preg_replace('#^https?://#', '', $currentsite);

        $sitecount  = 0;
        $activesite = "";

        if (!empty($sites) || $sites != "") {
            foreach ($sites as $key) {
                foreach ($key as $value) {
                    $value = rtrim($value, "/");
                    if (strcasecmp($value, $currentsite) != 0) {
                        $activesite .= "<li>" . $value . "</li>";
                        $sitecount ++;
                    }
                }
            }
        }

        if ($sitecount >= $max) {
            return $activesite;
        } else {
            return "";
        }
    }

    /**
     * Adds a notification message to the system.
     *
     * @param string $msg The message to be displayed.
     */
    public function add_notice($msg) {
        \core\notification::add($msg, \core\output\notification::NOTIFY_ERROR);
    }
}
