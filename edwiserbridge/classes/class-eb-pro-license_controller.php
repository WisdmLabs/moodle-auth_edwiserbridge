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
     * Initialize data on instance creation.
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
     * Update status of the license
     *
     * @param  object $licensedata License data
     * @return string             License status
     */
    public function update_status($licensedata) {

        global $DB;

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
            $GLOBALS['wdm_license_activation_failed'] = true;
            $this->add_notice(get_string('license_failed', 'auth_edwiserbridge'));
        } else {
            $status = $licensedata->license;
        }

        // Delete previous license status.
        $DB->delete_records_select(
            'config_plugins',
            'name = :name',
            ['name' => 'edd_' . $this->pluginslug. '_license_status']
        );

        $dataobject = new stdClass();
        $dataobject->plugin         = 'auth_edwiserbridge';
        $dataobject->name = 'edd_' . $this->pluginslug. '_license_status';
        $dataobject->value = $status;

        $DB->insert_record('config_plugins', $dataobject);

        return $status;
    }

    /**
     * Check if there no data
     * @param  string $licensedata          License data
     * @param  int    $currentresponsecode Current response code
     * @param  array  $validresponsecode   Valid response code
     * @return bool                          Boolean
     */
    public function check_if_no_data($licensedata, $currentresponsecode, $validresponsecode) {
        global $DB;

        if ($licensedata == null || ! in_array($currentresponsecode, $validresponsecode)) {
            $GLOBALS['wdm_server_null_response'] = true;

            // Delete previous record.
            $DB->delete_records_select(
                'config_plugins',
                'name = :name',
                ['name' => 'wdm_' . $this->pluginslug. '_license_trans']
            );

            // Insert new license trans.
            $dataobject = new stdClass();
            $dataobject->plugin         = 'auth_edwiserbridge';
            $dataobject->name = 'wdm_' . $this->pluginslug. '_license_trans';
            $dataobject->value = serialize(['server_did_not_respond', time() + (60 * 60 * 24)]);
            $DB->insert_record('config_plugins', $dataobject);

            return false;
        }
        return true;
    }

    /**
     * Activate license key
     */
    public function activate_license($licensekey) {
        global $DB, $CFG;
        if ($licensekey) {
            // Delete previous license key.
            $DB->delete_records_select(
                'config_plugins',
                'name = :name',
                ['name' => 'edd_' . $this->pluginslug. '_license_key']
            );

            // Insert new license key.
            $dataobject = new stdClass();
            $dataobject->plugin         = 'auth_edwiserbridge';
            $dataobject->name = 'edd_' . $this->pluginslug. '_license_key';
            $dataobject->value = $licensekey;
            $DB->insert_record('config_plugins', $dataobject);

            // Get cURL resource.
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $this->storeurl,
                CURLOPT_POST => 1,
                CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'].' - '.$CFG->wwwroot,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_POSTFIELDS => [
                    'edd_action' => 'activate_license',
                    'license' => $licensekey,
                    'item_name' => urlencode($this->pluginname),
                    'current_version' => $this->pluginversion,
                    'url' => urlencode($CFG->wwwroot),
                ],
            ]);

            // Send the request & save response to $resp.
            $resp = curl_exec($curl);

            $currentresponsecode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            // Close request to clear up some resources.
            curl_close($curl);

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

            if (isset($licensedata->renew_link) && ( ! empty($licensedata->renew_link) || $licensedata->renew_link != "")) {

                // Delete previous record.
                $DB->delete_records_select(
                    'config_plugins',
                    'name = :name',
                    ['name' => 'wdm_' . $this->pluginslug. '_product_site']
                );

                // Add renew link.
                $dataobject = new stdClass();
                $dataobject->plugin         = 'auth_edwiserbridge';
                $dataobject->name = 'wdm_' . $this->pluginslug. '_product_site';
                $dataobject->value = $licensedata->renew_link;

                $DB->insert_record('config_plugins', $dataobject);
            }

            $licensestatus = $this->update_status($licensedata);
            $this->set_transient_on_activation($licensestatus);
        }
    }

    /**
     * Set transient on activation for frequent license check
     * @param string $licensestatus License status
     */
    public function set_transient_on_activation($licensestatus) {

        global $DB;

        $transexpired = false;

        // Check license trans.
        $transient = $DB->get_field_select(
            'config_plugins',
            'value',
            'name = :name',
            ['name' => 'wdm_' . $this->pluginslug. '_license_trans'],
            IGNORE_MISSING
        );

        if ($transient) {
            $transient = unserialize($transient);

            if (is_array($transient) && time() > $transient[1] && $transient[1] > 0) {

                $transexpired = true;

                // Delete previous record.
                $DB->delete_records_select(
                    'config_plugins',
                    'name = :name',
                    ['name' => 'wdm_' . $this->pluginslug. '_license_trans']
                );
            }
        } else {
            $transexpired = true;
        }

        if ($transexpired == false) {

            // Delete previous license trans.
            $DB->delete_records_select(
                'config_plugins',
                'name = :name',
                ['name' => 'wdm_' . $this->pluginslug. '_license_trans']
            );

            if (! empty($licensestatus)) {
                if ($licensestatus == 'valid') {
                    $time = time() + 60 * 60 * 24 * 7;
                } else {
                    $time = time() + 60 * 60 * 24;
                }

                // Insert new license trans.
                $dataobject = new stdClass();
                $dataobject->plugin         = 'auth_edwiserbridge';
                $dataobject->name = 'wdm_' . $this->pluginslug. '_license_trans';
                $dataobject->value = serialize([$licensestatus, $time]);
                $DB->insert_record('config_plugins', $dataobject);
            }
        }
    }

    /**
     * Deactivate license key
     */
    public function deactivate_license() {
        global $DB, $CFG;

        $licensekey = $DB->get_field_select(
            'config_plugins',
            'value', 'name = :name',
            ['name' => 'edd_' . $this->pluginslug. '_license_key'],
            IGNORE_MISSING
        );

        if (!empty($licensekey)) {

            // Get cURL resource.
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $this->storeurl,
                CURLOPT_POST => 1,
                CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'].' - '.$CFG->wwwroot,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_POSTFIELDS => [
                    'edd_action' => 'deactivate_license',
                    'license' => $licensekey,
                    'item_name' => urlencode($this->pluginname),
                    'current_version' => $this->pluginversion,
                    'url' => urlencode($CFG->wwwroot),
                ],
            ]);

            // Send the request & save response to $resp.
            $resp = curl_exec($curl);

            $currentresponsecode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            // Close request to clear up some resources.
            curl_close($curl);

            $licensedata = json_decode($resp);

            $validresponsecode = ['200', '301'];

            $isdataavailable = $this->check_if_no_data($licensedata, $currentresponsecode, $validresponsecode);

            if ($isdataavailable == false) {
                return;
            }

            if ($licensedata->license == 'deactivated' || $licensedata->license == 'failed') {

                // Delete previous record.
                $DB->delete_records_select(
                    'config_plugins',
                    'name = :name',
                    ['name' => 'edd_' . $this->pluginslug. '_license_status']
                );

                $dataobject = new stdClass();
                $dataobject->plugin         = 'auth_edwiserbridge';
                $dataobject->name = 'edd_' . $this->pluginslug. '_license_status';
                $dataobject->value = 'deactivated';
                $DB->insert_record('config_plugins', $dataobject);
            }

            // Delete previous license trans.
            $DB->delete_records_select(
                'config_plugins',
                'name = :name',
                ['name' => 'wdm_' . $this->pluginslug. '_license_trans']
            );

            $dataobject = new stdClass();
            $dataobject->plugin         = 'auth_edwiserbridge';
            $dataobject->name = 'wdm_' . $this->pluginslug. '_license_trans';
            $dataobject->value = serialize([$licensedata->license, 0]);
            $DB->insert_record('config_plugins', $dataobject);
        }
    }

    /**
     * Get data from database
     * @return string Response status
     */
    public function get_data_from_db() {
        global $DB, $CFG;

        if (null !== self::$responsedata) {
            return self::$responsedata;
        }

        $transexpired = false;

        $transient = $DB->get_field_select(
            'config_plugins',
            'value',
            'name = :name',
            ['name' => 'wdm_' . $this->pluginslug. '_license_trans'],
            IGNORE_MISSING
        );

        if ($transient) {
            $transient = unserialize($transient);

            if (is_array($transient) && time() > $transient[1] && $transient[1] > 0) {

                $transexpired = true;
                // Delete previous license trans.
                $DB->delete_records_select(
                    'config_plugins',
                    'name = :name',
                    ['name' => 'wdm_' . $this->pluginslug. '_license_trans']
                );
            }
        } else {
            $transexpired = true;
        }

        if ($transexpired == true) {

            $licensekey = $DB->get_field_select(
                'config_plugins',
                'value',
                'name = :name',
                ['name' => 'edd_' . $this->pluginslug. '_license_key'],
                IGNORE_MISSING
            );

            if ($licensekey) {

                // Get cURL resource.
                $curl = curl_init();

                curl_setopt_array($curl, [
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_URL => $this->storeurl,
                    CURLOPT_POST => 1,
                    CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'].' - '.$CFG->wwwroot,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_POSTFIELDS => [
                        'edd_action' => 'check_license',
                        'license' => $licensekey,
                        'item_name' => urlencode($this->pluginname),
                        'current_version' => $this->pluginversion,
                        'url' => urlencode($CFG->wwwroot),
                    ],
                ]);
                // Send the request & save response to $resp.
                $resp = curl_exec($curl);

                $currentresponsecode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

                // Close request to clear up some resources.
                curl_close($curl);

                $licensedata = json_decode($resp);

                $validresponsecode = ['200', '301'];

                if ($licensedata == null || ! in_array($currentresponsecode, $validresponsecode)) {
                    // If server does not respond, read current license information.
                    $licensestatus = $DB->get_field_select(
                        'config_plugins',
                        'value', 'name = :name',
                        ['name' => 'edd_' . $this->pluginslug. '_license_status'],
                        IGNORE_MISSING
                    );

                    if (empty($licensedata)) {
                        // Insert new license transient.
                        $dataobject = new stdClass();
                        $dataobject->plugin         = 'auth_edwiserbridge';
                        $dataobject->name = 'wdm_' . $this->pluginslug. '_license_trans';
                        $dataobject->value = serialize(['server_did_not_respond', time() + (60 * 60 * 24)]);
                        $DB->insert_record('config_plugins', $dataobject);
                    }
                } else {
                    $licensestatus = $licensedata->license;
                }

                if (empty($licensestatus)) {
                    return;
                }

                if (isset($licensedata->license) && ! empty($licensedata->license)) {

                    // Delete previous record.
                    $DB->delete_records_select(
                        'config_plugins',
                        'name = :name',
                        ['name' => 'edd_' . $this->pluginslug. '_license_status']
                    );

                    $dataobject = new stdClass();
                    $dataobject->plugin         = 'auth_edwiserbridge';
                    $dataobject->name = 'edd_' . $this->pluginslug. '_license_status';
                    $dataobject->value = $licensestatus;
                    $DB->insert_record('config_plugins', $dataobject);
                }

                $this->set_response_data($licensestatus, $this->pluginslug, true);
                return self::$responsedata;
            }
        } else {

            $licensestatus = $DB->get_field_select(
                'config_plugins',
                'value',
                'name = :name',
                ['name' => 'edd_' . $this->pluginslug. '_license_status'],
                IGNORE_MISSING
            );

            $this->set_response_data($licensestatus, $this->pluginslug);
            return self::$responsedata;
        }
    }

    /**
     * Set response data in static properties
     * @param string  $licensestatus License status
     * @param string  $pluginslug    Plugin slug
     * @param boolean $settransient  Transient
     */
    public function set_response_data($licensestatus, $pluginslug, $settransient = false) {
        global $DB;

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
            $DB->delete_records_select(
                'config_plugins',
                'name = :name',
                ['name' => 'wdm_' . $pluginslug . '_license_trans']
            );

            // Insert new license transient.
            $dataobject = new stdClass();
            $dataobject->plugin         = 'auth_edwiserbridge';
            $dataobject->name = 'wdm_' . $pluginslug . '_license_trans';
            $dataobject->value = serialize([$licensestatus, time() + (60 * 60 * 24)]);
            $DB->insert_record('config_plugins', $dataobject);
        }
    }

    /**
     * This function is used to get list of sites where license key is already acvtivated.
     *
     * @param type $pluginslug current plugin's slug
     * @return string  list of site
     *
     *
     */
    public function get_site_data() {

        global $DB, $CFG;

        $sites = $DB->get_field_select(
            'config_plugins',
            'value', 'name = :name',
            ['name' => 'wdm_' . $this->pluginslug. '_license_key_sites'],
            IGNORE_MISSING
        );

        $max = $DB->get_field_select(
            'config_plugins',
            'value',
            'name = :name',
            ['name' => 'wdm_' . $this->pluginslug. '_license_max_site'],
            IGNORE_MISSING
        );

        $sites = unserialize($sites);

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
     * add notice in case of license key activation failure
     */
    public function add_notice($msg) {
        \core\notification::add($msg, \core\output\notification::NOTIFY_ERROR);
    }
}
