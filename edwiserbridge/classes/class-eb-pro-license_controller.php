<?php
// This file is part of Edwiserform Moodle Local Plugin.
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * License controller functionality
 * @package   auth_edwiserbridge
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class eb_pro_license_controller {
    /**
     *
     * @var string Slug to be used in url and functions name
     */
    private $plugin_slug = '';

    /**
     *
     * @var string stores the current plugin version
     */
    private $plugin_version = '';

    /**
     *
     * @var string Handles the plugin name
     */
    private $plugin_name = '';

    /**
     *
     * @var string  Stores the URL of store. Retrieves updates from
     *              this store
     */
    private $store_url = '';

    /**
     *
     * @var string  Name of the Author
     */
    private $author_name = '';

    public static $responseData;

    /**
     * Developer Note: This variable is used everywhere to check license information and verify the data.
     * Change the Name of this variable in this file wherever it appears and also remove this comment
     * After you are done with adding Licensing
     */
    public $wdm_edwiserform_data = array (
        'plugin_short_name' => 'Edwiser Bridge - Moodle', //Plugins short name appears on the License Menu Page
        'plugin_slug'       => 'moodle_edwiser_bridge', //this slug is used to store the data in db. License is checked using two options viz edd_<slug>_license_key and edd_<slug>_license_status
        'plugin_version'    => '3.0.0', //Current Version of the plugin. This should be similar to Version tag mentioned in Plugin headers
        'plugin_name'       => 'Edwiser Bridge - Moodle', //Under this Name product should be created on WisdmLabs Site
        'store_url'         => 'https://edwiser.org/check-update', //Url where program pings to check if update is available and license validity
        'author_name'       => 'WisdmLabs', //Author Name
    );

    /**
     * Initialize data on instance creation.
     */
    public function __construct() {
        $this->author_name       = $this->wdm_edwiserform_data[ 'author_name' ];
        $this->plugin_name       = $this->wdm_edwiserform_data[ 'plugin_name' ];
        $this->plugin_short_name = $this->wdm_edwiserform_data[ 'plugin_short_name' ];
        $this->plugin_slug       = $this->wdm_edwiserform_data[ 'plugin_slug' ];
        $this->plugin_version    = $this->wdm_edwiserform_data[ 'plugin_version' ];
        $this->store_url         = $this->wdm_edwiserform_data[ 'store_url' ];
    }

    public function update_status($license_data) {

        global $DB;

        $status = "";
        if ((empty($license_data->success)) && isset($license_data->error) && ($license_data->error == "expired")) {
            $status = 'expired';
            $this->add_notice(get_string('license_expired', 'auth_edwiserbridge'));
        } elseif ($license_data->license == 'invalid' && isset($license_data->error) && $license_data->error == "revoked") {
            $status = 'disabled';
            $this->add_notice(get_string('license_revoked', 'auth_edwiserbridge'));
        } elseif ($license_data->license == 'invalid' || (isset($license_data->activations_left) && $license_data->activations_left == "0")) {
            $status = 'invalid';
            if(isset($license_data->activations_left) && $license_data->activations_left == "0") {
                $this->add_notice(get_string('license_no_activation_left', 'auth_edwiserbridge'));
            } else {
                $this->add_notice(get_string('license_invalid', 'auth_edwiserbridge'));
            }
        } elseif ($license_data->license == 'failed') {
            $status = 'failed';
            $GLOBALS[ 'wdm_license_activation_failed' ] = true;
            $this->add_notice(get_string('license_failed', 'auth_edwiserbridge'));
        } else {
            $status = $license_data->license;
        }

        // delete previous license status
        try {
            $DB->delete_records_select('config_plugins', 'name = :name', array('name' => 'edd_' . $this->plugin_slug . '_license_status'));
        } catch (dml_exception $e) {
            // keep catch empty if no record found
        }

        $dataobject = new stdClass();
        $dataobject->plugin         = 'auth_edwiserbridge';
        $dataobject->name = 'edd_' . $this->plugin_slug . '_license_status';
        $dataobject->value = $status;

        $DB->insert_record('config_plugins', $dataobject);

        return $status;
    }

    /**
     * Check if there no data
     * @param  string $license_data          License data
     * @param  int    $current_response_code Current response code
     * @param  array  $valid_response_code   Valid response code
     * @return bool                          Boolean
     */
    public function check_if_no_data($license_data, $current_response_code, $valid_response_code) {
    	global $DB;

        if ($license_data == null || ! in_array($current_response_code, $valid_response_code)) {
            $GLOBALS[ 'wdm_server_null_response' ] = true;

            // delete previous record
            try {
                $DB->delete_records_select('config_plugins', 'name = :name', array('name' => 'wdm_' . $this->plugin_slug . '_license_trans'));
            } catch (dml_exception $e) {
                // keep catch empty if no record found
            }

            // insert new license trans
            $dataobject = new stdClass();
            $dataobject->plugin         = 'auth_edwiserbridge';
            $dataobject->name = 'wdm_' . $this->plugin_slug . '_license_trans';
            $dataobject->value = serialize(array('server_did_not_respond', time() + (60 * 60 * 24)));
            $DB->insert_record('config_plugins', $dataobject);


            return false;
        }
        return true;
    }

    /**
     * Activate license key
     */
    public function activate_license($license_key) {
        global $DB, $CFG;
        if ($license_key) {
            // delete previous license key
            try {
                $DB->delete_records_select('config_plugins', 'name = :name', array('name' => 'edd_' . $this->plugin_slug . '_license_key'));
            } catch (dml_exception $e) {
                // keep catch empty if no record found
            }

            // insert new license key
            $dataobject = new stdClass();
            $dataobject->plugin         = 'auth_edwiserbridge';
            $dataobject->name = 'edd_' . $this->plugin_slug . '_license_key';
            $dataobject->value = $license_key;
            $DB->insert_record('config_plugins', $dataobject);

            // Get cURL resource
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $this->store_url,
                CURLOPT_POST => 1,
                CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'].' - '.$CFG->wwwroot,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_POSTFIELDS => array(
                    'edd_action' => 'activate_license',
                    'license' => $license_key,
                    'item_name' => urlencode($this->plugin_name),
                    'current_version' => $this->plugin_version,
                    'url' => urlencode($CFG->wwwroot),
                )
            ));

            // Send the request & save response to $resp
            $resp = curl_exec($curl);

            $current_response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            // Close request to clear up some resources
            curl_close($curl);

            $license_data = json_decode($resp);

            $valid_response_code = array( '200', '301' );

            $isDataAvailable = $this->check_if_no_data($license_data, $current_response_code, $valid_response_code);

            if ($isDataAvailable == false) {
                return;
            }

            $exp_time = 0;
            if (isset($license_data->expires)) {
                $exp_time = strtotime($license_data->expires);
            }
            $cur_time = time();

            if (isset($license_data->expires) && ($license_data->expires !== false) && ($license_data->expires != 'lifetime') && $exp_time <= $cur_time && $exp_time != 0) {
                $license_data->error = "expired";
            }

            if (isset($license_data->renew_link) && ( ! empty($license_data->renew_link) || $license_data->renew_link != "")) {

                // delete previous record
                try {
                    $DB->delete_records_select('config_plugins', 'name = :name', array('name' => 'wdm_' . $this->plugin_slug . '_product_site'));
                } catch (dml_exception $e) {
                    // keep catch empty if no record found
                }

                // add renew link
                $dataobject = new stdClass();
                $dataobject->plugin         = 'auth_edwiserbridge';
                $dataobject->name = 'wdm_' . $this->plugin_slug . '_product_site';
                $dataobject->value = $license_data->renew_link;

                $DB->insert_record('config_plugins', $dataobject);
            }

            $license_status = $this->update_status($license_data);
            $this->set_transient_on_activation($license_status);
        }
    }

    /**
     * Set transient on activation for frequent license check
     * @param string $license_status License status
     */
    public function set_transient_on_activation($license_status) {

        global $DB;

        $trans_expired = false;

        // check license trans
        $trans_var = $DB->get_field_select('config_plugins', 'value', 'name = :name', array('name' => 'wdm_' . $this->plugin_slug . '_license_trans'), IGNORE_MISSING);

        if($trans_var) {
            $trans_var = unserialize($trans_var);

            if(is_array($trans_var) && time() > $trans_var[1] && $trans_var[1] > 0) {

                $trans_expired = true;

                // delete previous record
                try {
                    $DB->delete_records_select('config_plugins', 'name = :name', array('name' => 'wdm_' . $this->plugin_slug . '_license_trans'));
                } catch (dml_exception $e) {
                    // keep catch empty if no record found
                }
            }
        } else {
            $trans_expired = true;
        }

        if ($trans_expired == false) {

            // delete previous license trans
            try {
                $DB->delete_records_select('config_plugins', 'name = :name', array('name' => 'wdm_' . $this->plugin_slug . '_license_trans'));
            } catch (dml_exception $e) {
                // keep catch empty if no record found
            }

            if (! empty($license_status)) {
                if ($license_status == 'valid') {
                    $time = time() + 60 * 60 * 24 * 7;
                } else {
                    $time = time() + 60 * 60 * 24;
                }

                // insert new license trans
                $dataobject = new stdClass();
                $dataobject->plugin         = 'auth_edwiserbridge';
                $dataobject->name = 'wdm_' . $this->plugin_slug . '_license_trans';
                $dataobject->value = serialize(array($license_status, $time));
                $DB->insert_record('config_plugins', $dataobject);
            }
        }
    }

    /**
     * Deactivate license key
     */
    public function deactivate_license() {
        global $DB, $CFG;

        $wpep_license_key = $DB->get_field_select('config_plugins', 'value', 'name = :name', array('name' => 'edd_' . $this->plugin_slug . '_license_key'), IGNORE_MISSING);

        if (!empty($wpep_license_key)) {

            // Get cURL resource
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $this->store_url,
                CURLOPT_POST => 1,
                CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'].' - '.$CFG->wwwroot,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_POSTFIELDS => array(
                    'edd_action' => 'deactivate_license',
                    'license' => $wpep_license_key,
                    'item_name' => urlencode($this->plugin_name),
                    'current_version' => $this->plugin_version,
                    'url' => urlencode($CFG->wwwroot),
                )
            ));

            // Send the request & save response to $resp
            $resp = curl_exec($curl);

            $current_response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            // Close request to clear up some resources
            curl_close($curl);

            $license_data = json_decode($resp);

            $valid_response_code = array( '200', '301' );

            $isDataAvailable = $this->check_if_no_data($license_data, $current_response_code, $valid_response_code);

            if ($isDataAvailable == false) {
                return;
            }

            if ($license_data->license == 'deactivated' || $license_data->license == 'failed') {

                // delete previous record
                try {
                    $DB->delete_records_select('config_plugins', 'name = :name', array('name' => 'edd_' . $this->plugin_slug . '_license_status'));
                } catch (dml_exception $e) {
                    // keep catch empty if no record found
                }

                $dataobject = new stdClass();
                $dataobject->plugin         = 'auth_edwiserbridge';
                $dataobject->name = 'edd_' . $this->plugin_slug . '_license_status';
                $dataobject->value = 'deactivated';
                $DB->insert_record('config_plugins', $dataobject);
            }

            // delete previous license trans
            try {
                $DB->delete_records_select('config_plugins', 'name = :name', array('name' => 'wdm_' . $this->plugin_slug . '_license_trans'));
            } catch (dml_exception $e) {
                // keep catch empty if no record found
            }

            $dataobject = new stdClass();
            $dataobject->plugin         = 'auth_edwiserbridge';
            $dataobject->name = 'wdm_' . $this->plugin_slug . '_license_trans';
            $dataobject->value = serialize(array($license_data->license, 0));
            $DB->insert_record('config_plugins', $dataobject);
        }
    }

    /**
     * Common method for license operation
     * @return mixed Activation/Deactivation status
     */
    public function add_data() {
        if (is_siteadmin()) {

            // return if did not come from license page
            if(!isset($_POST['onEdwiserFormLicensePage']) || $_POST['onEdwiserFormLicensePage'] == 0) {
                return;
            }
            $_POST['onEdwiserFormLicensePage'] = false;

            if(empty(@$_POST['edd_' . $this->plugin_slug .'_license_key'])) {
                    $lk = 'empty';
            } else {
                $lk = @$_POST['edd_' . $this->plugin_slug .'_license_key'];
            }
            if (isset($_POST[ 'edd_' . $this->plugin_slug . '_license_activate' ])) {

               // jugad to tackle the page redirect after save license
               set_config('licensekey', $lk, 'auth_edwiserbridge');
               set_config('licensekeyactivate', @$_POST['edd_' . $this->plugin_slug . '_license_activate'], 'auth_edwiserbridge');

               return $this->activate_license();
            } elseif (isset($_POST[ 'edd_' . $this->plugin_slug . '_license_deactivate' ])) {

               // jugad to tackle the page redirect after save license
               set_config('licensekey', $lk, 'auth_edwiserbridge');
               set_config('licensekeydeactivate', @$_POST['edd_' . $this->plugin_slug . '_license_deactivate'], 'auth_edwiserbridge');
               return $this->deactivate_license();
            }
        }
    }

    /**
     * Get data from database
     * @return string Response status
     */
    public function get_data_from_db() {
        global $DB, $CFG;

        if (null !== self::$responseData) {
            return self::$responseData;
        }

        $trans_expired = false;

        $get_trans = $DB->get_field_select('config_plugins', 'value', 'name = :name', array('name' => 'wdm_' . $this->plugin_slug . '_license_trans'), IGNORE_MISSING);

        if($get_trans) {
            $get_trans = unserialize($get_trans);

            if(is_array($get_trans) && time() > $get_trans[1] && $get_trans[1] > 0) {

                $trans_expired = true;
                // delete previous license trans
                try {
                    $DB->delete_records_select('config_plugins', 'name = :name', array('name' => 'wdm_' . $this->plugin_slug . '_license_trans'));
                } catch (dml_exception $e) {
                    // keep catch empty if no record found
                }
            }
        } else {
            $trans_expired = true;
        }

        if ($trans_expired == true) {

            $license_key = $DB->get_field_select('config_plugins', 'value', 'name = :name', array('name' => 'edd_' . $this->plugin_slug . '_license_key'), IGNORE_MISSING);

            if ($license_key) {

                // Get cURL resource
                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_URL => $this->store_url,
                    CURLOPT_POST => 1,
                    CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'].' - '.$CFG->wwwroot,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_POSTFIELDS => array(
                        'edd_action' => 'check_license',
                        'license' => $license_key,
                        'item_name' => urlencode($this->plugin_name),
                        'current_version' => $this->plugin_version,
                        'url' => urlencode($CFG->wwwroot),
                    )
                ));
                // Send the request & save response to $resp
                $resp = curl_exec($curl);

                $current_response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

                // Close request to clear up some resources
                curl_close($curl);

                $license_data = json_decode($resp);

                $valid_response_code = array( '200', '301' );

                if ($license_data == null || ! in_array($current_response_code, $valid_response_code)) {
                    //if server does not respond, read current license information
                    $license_status = $DB->get_field_select('config_plugins', 'value', 'name = :name', array('name' => 'edd_' . $this->plugin_slug . '_license_status'), IGNORE_MISSING);

                    if (empty($license_data)) {
                        // insert new license transient
                        $dataobject = new stdClass();
                        $dataobject->plugin         = 'auth_edwiserbridge';
                        $dataobject->name = 'wdm_' . $this->plugin_slug . '_license_trans';
                        $dataobject->value = serialize(array('server_did_not_respond', time() + (60 * 60 * 24)));
                        $DB->insert_record('config_plugins', $dataobject);
                    }
                } else {
                    $license_status = $license_data->license;
                }

                if (empty($license_status)) {
                    return;
                }

                if (isset($license_data->license) && ! empty($license_data->license)) {

                    // delete previous record
                    try {
                        $DB->delete_records_select('config_plugins', 'name = :name', array('name' => 'edd_' . $this->plugin_slug . '_license_status'));
                    } catch (dml_exception $e) {
                        // keep catch empty if no record found
                    }

                    $dataobject = new stdClass();
                    $dataobject->plugin         = 'auth_edwiserbridge';
                    $dataobject->name = 'edd_' . $this->plugin_slug . '_license_status';
                    $dataobject->value = $license_status;
                    $DB->insert_record('config_plugins', $dataobject);
                }

                $this->set_response_data($license_status, $this->plugin_slug, true);
                return self::$responseData;
            }
        } else {

            $license_status = $DB->get_field_select('config_plugins', 'value', 'name = :name', array('name' => 'edd_' . $this->plugin_slug . '_license_status'), IGNORE_MISSING);

            $this->set_response_data($license_status, $this->plugin_slug);
            return self::$responseData;
        }
    }

    /**
     * Set response data in static properties
     * @param string  $license_status License status
     * @param string  $plugin_slug    Plugin slug
     * @param boolean $set_transient  Transient
     */
    public function set_response_data($license_status, $plugin_slug, $set_transient = false) {
        global $DB;

        if ($license_status == 'valid') {
            self::$responseData = 'available';
        } elseif ($license_status == 'expired') {
            self::$responseData = 'available';
        } else {
            self::$responseData  = 'unavailable';
        }

        if ($set_transient) {
            if ($license_status == 'valid') {
                $time = 60 * 60 * 24 * 7;
            } else {
                $time = 60 * 60 * 24;
            }

            // delete previous record
            try {
                $DB->delete_records_select('config_plugins', 'name = :name', array('name' => 'wdm_' . $plugin_slug . '_license_trans'));
            } catch (dml_exception $e) {
                // keep catch empty if no record found
            }

            // insert new license transient
            $dataobject = new stdClass();
            $dataobject->plugin         = 'auth_edwiserbridge';
            $dataobject->name = 'wdm_' . $plugin_slug . '_license_trans';
            $dataobject->value = serialize(array($license_status, time() + (60 * 60 * 24)));
            $DB->insert_record('config_plugins', $dataobject);
        }
    }

    /**
     * This function is used to get list of sites where license key is already acvtivated.
     *
     * @param type $plugin_slug current plugin's slug
     * @return string  list of site
     *
     *
     */
    public function get_site_data() {

        global $DB, $CFG;

        $sites = $DB->get_field_select('config_plugins', 'value', 'name = :name', array('name' => 'wdm_' . $this->plugin_slug . '_license_key_sites'), IGNORE_MISSING);

        $max = $DB->get_field_select('config_plugins', 'value', 'name = :name', array('name' => 'wdm_' . $this->plugin_slug . '_license_max_site'), IGNORE_MISSING);

        $sites = unserialize($sites);

        $cur_site    = $CFG->wwwroot;
        $cur_site    = preg_replace('#^https?://#', '', $cur_site);

        $site_count  = 0;
        $active_site = "";

        if (!empty($sites) || $sites != "") {
            foreach ($sites as $key) {
                foreach ($key as $value) {
                    $value = rtrim($value, "/");

                    if (strcasecmp($value, $cur_site) != 0) {
                        $active_site.= "<li>" . $value . "</li>";
                        $site_count ++;
                    }
                }
            }
        }

        if ($site_count >= $max) {
            return $active_site;
        } else {
            return "";
        }
    }

    /**
     * add notice in case of license key activation failure
     */
    public function add_notice($msg){
        \core\notification::add($msg, \core\output\notification::NOTIFY_ERROR);
    }
}
