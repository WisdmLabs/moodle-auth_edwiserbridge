<?php
// This file is part of Moodle - http://moodle.org/
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
 * File contains all required lib functions.
 *
 * @package     auth_edwiserbridge
 * @copyright   2021 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Wisdmlabs
 */
defined('MOODLE_INTERNAL') || die();

function check_edwiser_bridge_pro_dependancy() {
    $clear = true;
    $pluginman   = \core_plugin_manager::instance();
    $localplugin = $pluginman->get_plugins_of_type('local');
    if( isset( $localplugin['edwiserbridge'] ) ) {
        $clear = false;
    }
    if (isset($localplugin['wdmgroupregistration'])) {
        $clear = false;
    }
    $authplugin = $pluginman->get_plugins_of_type('auth');

    if (isset($authplugin['wdmwpmoodle'])) {
        $clear = false;
    }
    return $clear;
}

function edwiser_bridge_pro_dependancy_notice(){
    
}

if( check_edwiser_bridge_pro_dependancy() ) {

    require_once(dirname(__FILE__) . "/classes/class-api-handler.php");
    require_once(dirname(__FILE__) . "/classes/class-settings-handler.php");
    require_once("{$CFG->libdir}/completionlib.php");
    /**
     * Saving test connection form data.
     * Saves forntend form data with all the available data like multiple WP site and token.
     * @param object $formdata formdata
     */
    function save_connection_form_settings($formdata, $mform = false) {
        // Checking if provided data count is correct or not.
        if (count($formdata->wp_url) != count($formdata->wp_token)) {
            return;
        }

        $connectionsettings = array();
        for ($i = 0; $i < count($formdata->wp_url); $i++) {
            if (!empty($formdata->wp_url[$i]) && !empty($formdata->wp_token[$i]) && !empty($formdata->wp_name[$i])) {
                $connectionsettings[$formdata->wp_name[$i]] = array(
                    "wp_url"   => $formdata->wp_url[$i],
                    "wp_token" => $formdata->wp_token[$i],
                    "wp_name"  => $formdata->wp_name[$i]
                );
            }
        }
        set_config("eb_connection_settings", serialize($connectionsettings));
    }

    /**
     * Save the synch settings for the individual site
     *
     * @param object $formdata formdata
     */
    function save_synchronization_form_settings($formdata, $mform = false) {
        global $CFG;
        $synchsettings          = array();
        $connectionsettings     = unserialize($CFG->eb_connection_settings);
        $connectionsettingskeys = array_keys($connectionsettings);

        if (in_array($formdata->wp_site_list, $connectionsettingskeys)) {
            $existingsynchsettings = isset($CFG->eb_synch_settings) ? unserialize($CFG->eb_synch_settings) : array();
            $synchsettings         = $existingsynchsettings;

            $synchsettings[$formdata->wp_site_list] = array(
                "course_enrollment"    => $formdata->course_enrollment,
                "course_un_enrollment" => $formdata->course_un_enrollment,
                "user_creation"        => $formdata->user_creation,
                "user_deletion"        => $formdata->user_deletion,
                "course_creation"      => $formdata->course_creation,
                "course_deletion"      => $formdata->course_deletion,
                "user_updation"        => $formdata->user_updation
            );
        }
        set_config("eb_synch_settings", serialize($synchsettings));
    }
    /**
     * Save the sso settings for the individual site
     *
     * @param object $formdata formdata
     */
    function save_sso_form_settings($formdata, $mform = false) {
        global $CFG;

        set_config('sharedsecret', $formdata->sharedsecret, 'auth_edwiserbridge');
        set_config('wpsiteurl', $formdata->wpsiteurl, 'auth_edwiserbridge');
        set_config('logoutredirecturl', $formdata->logoutredirecturl, 'auth_edwiserbridge');
        set_config('wploginenablebtn', $formdata->wploginenablebtn, 'auth_edwiserbridge');
        set_config('wploginbtntext', $formdata->wploginbtntext, 'auth_edwiserbridge');
    }
    /**
     * Save the general settings for Moodle.
     *
     * @param object $formdata formdata
     */
    function save_settings_form_settings($formdata, $mform = false) {
        global $CFG;

        if (isset($formdata->web_service) && isset($formdata->pass_policy) && isset($formdata->extended_username)) {

            $activewebservices = empty($CFG->webserviceprotocols) ? array() : explode(',', $CFG->webserviceprotocols);

            if ($formdata->rest_protocol) {
                $activewebservices[] = 'rest';
            } else {
                $key = array_search('rest', $activewebservices);
                unset($activewebservices[$key]);
            }

            set_config('webserviceprotocols', implode(',', $activewebservices));
            set_config("enablewebservices", $formdata->web_service);
            set_config("extendedusernamechars", $formdata->extended_username);
            set_config("passwordpolicy", $formdata->pass_policy);
        }
    }

    /**
     * Get required settings fromm DB.
     */
    function get_required_settings() {
        global $CFG;

        $requiredsettings = array();

        $activewebservices = empty($CFG->webserviceprotocols) ? array() : explode(',', $CFG->webserviceprotocols);

        $requiredsettings['rest_protocol'] = 0;
        if (false !== array_search('rest', $activewebservices)) {
            $requiredsettings['rest_protocol'] = 1;
        }

        $requiredsettings['web_service'] = isset($CFG->enablewebservices) ? $CFG->enablewebservices : false;
        $requiredsettings['extended_username'] = isset($CFG->extendedusernamechars) ? $CFG->extendedusernamechars : false;
        $requiredsettings['pass_policy'] = isset($CFG->passwordpolicy) ? $CFG->passwordpolicy : false;

        return $requiredsettings;
    }

    /**
     * Returns connection settings saved in the settings form.
     */
    function get_connection_settings() {
        global $CFG;
        $reponse["eb_connection_settings"] = isset($CFG->eb_connection_settings) ? unserialize($CFG->eb_connection_settings) : false;
        return $reponse;
    }

    /**
     * Returns individual sites data.
     * @param  int $index [description]
     * @return array returns selected sites data.
     */
    function get_synch_settings($index) {
        global $CFG;
        $reponse = isset($CFG->eb_synch_settings) ? unserialize($CFG->eb_synch_settings) : false;

        $data = array(
            "course_enrollment"    => 0,
            "course_enrollment"    => 0,
            "course_un_enrollment" => 0,
            "user_creation"        => 0,
            "user_deletion"        => 0,
            "course_creation"      => 0,
            "course_deletion"      => 0,
            "user_updation"        => 0,
        );

        if (isset($reponse[$index]) && !empty($reponse[$index])) {
            return $reponse[$index];
        }
        return $data;
    }

    /**
     * Returns all the sites created in the edwiser settings.
     * @return array sites list
     */
    function get_site_list() {
        global $CFG;
        $reponse = isset($CFG->eb_connection_settings) ? unserialize($CFG->eb_connection_settings) : false;

        if ($reponse && count($reponse)) {
            foreach ($reponse as $key => $value) {
                $sites[$key] = $value["wp_name"];
            }
        } else {
            $sites = array("" => get_string('eb_no_sites', 'auth_edwiserbridge'));
        }
        return $sites;
    }

    /**
     * Returns the main instance of EDW to prevent the need to use globals.
     *
     * @since  1.0.0
     *
     * @return EDW
     */
    function api_handler_instance() {
        return api_handler::instance();
    }

    /**
     * returns the list of courses in which user is enrolled
     *
     * @param int $userid user id.
     * @return array array of courses.
     */
    function get_array_of_enrolled_courses($userid) {
        $enrolledcourses = enrol_get_users_courses($userid);
        $courses         = array();

        foreach ($enrolledcourses as $value) {
            array_push($courses, $value->id);
        }
        return $courses;
    }

    /**
     * Removes processed coureses from the course whose progress is already provided.
     *
     * @param int $courseid course id.
     * @param array $courses courses array.
     * @return array courses array.
     */
    function remove_processed_coures($courseid, $courses) {
        $key = array_search($courseid, $courses);
        if ($key !== false) {
            unset($courses[$key]);
        }
        return $courses;
    }

    /**
     * Functionality to check if the request is from wordpress and the stop processing the enrollment and unenrollment.
     */
    function check_if_request_is_from_wp() {
        $required    = 0;

        // using this condition because param enrollments and cohort are multi dimensional array and it is not working with optional_param or optional_param_array.
        if(isset($_POST['enrolments']) || isset($_POST['cohort'])){
            $required = 1;
        }

        return $required;
    }

    /*-----------------------------------------------------------
    *   Functions used in Settings page
    *----------------------------------------------------------*/
    /**
     * Functionality to get all available Moodle sites administrator.
     */
    function eb_get_administrators() {
        $admins          = get_admins();
        $settingsarr      = array();
        $settingsarr[''] = get_string('new_service_user_lbl', 'auth_edwiserbridge');

        foreach ($admins as $value) {
            $settingsarr[$value->id] = $value->email;
        }
        return $settingsarr;
    }

    /**
     * Functionality to get all available Moodle sites services.
     */
    function eb_get_existing_services() {
        global $DB;
        $settingsarr           = array();
        $result                = $DB->get_records("external_services", null, '', 'id, name');
        $settingsarr['']       = get_string('existing_service_lbl', 'auth_edwiserbridge');
        $settingsarr['create'] = ' - ' . get_string('new_web_new_service', 'auth_edwiserbridge') . ' - ';

        foreach ($result as $value) {
            $settingsarr[$value->id] = $value->name;
        }

        return $settingsarr;
    }

    /**
     * Functionality to get all available Moodle sites tokens.
     *
     * @param int $serviceid service id.
     * @return array settings array.
     */
    function eb_get_service_tokens($serviceid) {
        global $DB;

        $settingsarr = array();
        $result      = $DB->get_records("external_tokens", null, '', 'token, externalserviceid');

        foreach ($result as $value) {
            $settingsarr[] = array('token' => $value->token, 'id' => $value->externalserviceid);
        }

        return $settingsarr;
    }

    /**
     * Functionality to create token.
     *
     * @param int $serviceid service id.
     * @param int $existingtoken existing token.
     * @return string html content.
     */
    function eb_create_token_field($serviceid, $existingtoken = '') {

        $tokenslist = eb_get_service_tokens($serviceid);

        $html = '<div class="eb_copy_txt_wrap">
                    <div style="width:60%;">
                        <select class="eb_copy" class="custom-select" name="eb_token" id="id_eb_token">
                        <option value="">' . get_string('token_dropdown_lbl', 'auth_edwiserbridge') . '</option>';

        foreach ($tokenslist as $token) {
            $selected = '';
            $display = '';

            if (isset($token['token']) && $token['token'] == $existingtoken) {
                $selected = " selected";
            }

            if (isset($token['id']) && $token['id'] != $serviceid) {
                $display = 'style="display:none"';
            }

            $html .= '<option data-id="' . $token['id'] . '" value="' . $token['token'] . '" '
                . $display . " " . $selected . '>' . $token['token'] . '</option>';
        }

        $html .= '      </select>
                    </div>
                    <div> <button class="btn btn-primary eb_primary_copy_btn">' . get_string('copy', 'auth_edwiserbridge')
            . '</button> </div>
                </div>';

        return $html;
    }

    /**
     * Functionality to get count of not available services which are required for Edwiser-Bridge.
     *
     * @param int $serviceid service id.
     * @return string count of not available services.
     */
    function eb_get_service_list($serviceid) {
        global $DB;
        $functions = array(
            array('externalserviceid' => $serviceid, 'functionname' => 'core_user_create_users'),
            array('externalserviceid' => $serviceid, 'functionname' => 'core_user_get_users_by_field'),
            array('externalserviceid' => $serviceid, 'functionname' => 'core_user_update_users'),
            array('externalserviceid' => $serviceid, 'functionname' => 'core_course_get_courses'),
            array('externalserviceid' => $serviceid, 'functionname' => 'core_course_get_categories'),
            array('externalserviceid' => $serviceid, 'functionname' => 'enrol_manual_enrol_users'),
            array('externalserviceid' => $serviceid, 'functionname' => 'enrol_manual_unenrol_users'),
            array('externalserviceid' => $serviceid, 'functionname' => 'core_enrol_get_users_courses'),
            array('externalserviceid' => $serviceid, 'functionname' => 'eb_test_connection'),
            array('externalserviceid' => $serviceid, 'functionname' => 'eb_get_site_data'),
            array('externalserviceid' => $serviceid, 'functionname' => 'eb_get_course_progress'),
            array('externalserviceid' => $serviceid, 'functionname' => 'eb_get_edwiser_plugins_info'),
            array('externalserviceid' => $serviceid, 'functionname' => 'edwiserbridge_local_get_course_enrollment_method'),
            array('externalserviceid' => $serviceid, 'functionname' => 'edwiserbridge_local_update_course_enrollment_method'),
            array('externalserviceid' => $serviceid, 'functionname' => 'edwiserbridge_local_get_mandatory_settings'),
            array('externalserviceid' => $serviceid, 'functionname' => 'edwiserbridge_local_enable_plugin_settings'),
            array('externalserviceid' => $serviceid, 'functionname' => 'eb_get_users'),
            array('externalserviceid' => $serviceid, 'functionname' => 'eb_get_courses'),
        );

        global $CFG;
        require_once($CFG->dirroot . '/auth/edwiserbridge/classes/class-eb-pro-license_controller.php');
        $license = new \eb_pro_license_controller();
        if($license->get_data_from_db() == 'available'){
            $bulkpurchase = array(
                array('externalserviceid' => $serviceid, 'functionname' => 'core_cohort_add_cohort_members'),
                array('externalserviceid' => $serviceid, 'functionname' => 'core_cohort_create_cohorts'),
                array('externalserviceid' => $serviceid, 'functionname' => 'core_role_assign_roles'),
                array('externalserviceid' => $serviceid, 'functionname' => 'core_role_unassign_roles'),
                array('externalserviceid' => $serviceid, 'functionname' => 'core_cohort_delete_cohort_members'),
                array('externalserviceid' => $serviceid, 'functionname' => 'core_cohort_get_cohorts'),
                array('externalserviceid' => $serviceid, 'functionname' => 'eb_manage_cohort_enrollment'),
                array('externalserviceid' => $serviceid, 'functionname' => 'eb_delete_cohort'),
                array('externalserviceid' => $serviceid, 'functionname' => 'eb_manage_user_cohort_enrollment')
            );
            $ssofunctions = array(
                array('externalserviceid' => $serviceid, 'functionname' => 'wdm_sso_verify_token'),
            );
        }else{
            $bulkpurchase = array();
            $ssofunctions = array();
        }

        $functions = array_merge($functions, $bulkpurchase, $ssofunctions);

        $count = 0;

        foreach ($functions as $function) {
            if (!$DB->record_exists(
                'external_services_functions',
                array(
                    'functionname' => $function['functionname'],
                    'externalserviceid' => $serviceid
                )
            )) {
                $count++;
            }
        }

        // Add extension functions if they are present.
        return $count;
    }

    /**
     * Functionality to get summary status.
     */
    function eb_get_summary_status() {
        global $CFG;

        $settingsarray = array(
            'enablewebservices'     => 1,
            'passwordpolicy'        => 0,
            'extendedusernamechars' => 1,
            'webserviceprotocols'   => 1

        );

        foreach ($settingsarray as $key => $value) {
            if (isset($CFG->$key) && $value != $CFG->$key) {
                if ($key == 'webserviceprotocols') {
                    $activewebservices = empty($CFG->webserviceprotocols) ? array() : explode(',', $CFG->webserviceprotocols);
                    if (!in_array('rest', $activewebservices)) {
                        return 'error';
                    }
                } else {
                    return 'error';
                }
            }
        }

        $servicearray = array(
            'ebexistingserviceselect',
            'edwiser_bridge_last_created_token'
        );

        foreach ($servicearray as $value) {

            if (empty($CFG->$value)) {
                return 'warning';
            }
        }
        return 'sucess';
    }

    /**
     * Serves the files from the auth_edwiserbridge file areas
     *
     * @param stdClass $course the course object
     * @param stdClass $cm the course module object
     * @param stdClass $context the auth_edwiserbridge's context
     * @param string   $filearea the name of the file area
     * @param array    $args extra arguments (itemid, path)
     * @param bool     $forcedownload whether or not force download
     * @param array    $options additional options affecting the file serving
     */
    function auth_edwiserbridge_pluginfile(
        $course,
        $cm,
        $context,
        $filearea,
        array $args,
        $forcedownload = 0,
        array $options = array()
    ) {
        if ( $context->contextlevel != CONTEXT_SYSTEM ) {
            send_file_not_found();
        }
        $itemid       = (int) array_shift( $args );
        $relativepath = implode( '/', $args );
        $fullpath     = "/{$context->id}/auth_edwiserbridge/$filearea/$itemid/$relativepath";
        $fs           = get_file_storage();
        if ( ! ( $file = $fs->get_file_by_hash( sha1( $fullpath ) ) ) ) {
            return false;
        }
        // Download MUST be forced - security!
        send_stored_file( $file, 0, 0, $forcedownload, $options );
    }
} else {
    $msg = get_string('edwiserbridgepropluginrequired', 'auth_edwiserbridge');
    $pluginoverviewurl = new moodle_url('/admin/plugins.php', array('plugin' => 'overview'));
    $msg .= ' <a href="'.$pluginoverviewurl.'">'.get_string('backtopluginoverview', 'auth_edwiserbridge').'</a>';
    
    // abort installation. and redirect to plugin overview page.
    
    // uninstall plugin.
    $plugin_manager  = core_plugin_manager::instance();

    $plugin_manager->cancel_plugin_installation('auth_edwiserbridge');

    $plugin_manager::reset_caches();

    purge_all_caches();

    throw new moodle_exception( $msg );
}

/**
 * Check active webservices and update functions for auth_edwiserbridge plugin.
 */
function check_and_update_webservice_functions_edwiserbridge() {

    global $DB, $CFG;
    // get connection settings.
    $connections = isset($CFG->eb_connection_settings) ? unserialize($CFG->eb_connection_settings) : array();
    
    foreach ( $connections as $connection ) {
        $data = $DB->get_record('external_tokens', array('token' => $connection['wp_token']), 'externalserviceid');
        $serviceid = isset($data->externalserviceid) ? $data->externalserviceid : '';

        if( empty($serviceid) ) {
            continue;
        }

        $ssofunctions = array(
            array('externalserviceid' => $serviceid, 'functionname' => 'wdm_sso_verify_token'),
        );

        $bulkpurchasefunctions = array(
            array('externalserviceid' => $serviceid, 'functionname' => 'core_cohort_add_cohort_members'),
            array('externalserviceid' => $serviceid, 'functionname' => 'core_cohort_create_cohorts'),
            array('externalserviceid' => $serviceid, 'functionname' => 'core_role_assign_roles'),
            array('externalserviceid' => $serviceid, 'functionname' => 'core_role_unassign_roles'),
            array('externalserviceid' => $serviceid, 'functionname' => 'core_cohort_delete_cohort_members'),
            array('externalserviceid' => $serviceid, 'functionname' => 'core_cohort_get_cohorts'),
            array('externalserviceid' => $serviceid, 'functionname' => 'eb_manage_cohort_enrollment'),
            array('externalserviceid' => $serviceid, 'functionname' => 'eb_delete_cohort'),
            array('externalserviceid' => $serviceid, 'functionname' => 'wdm_manage_cohort_enrollment'),
            array('externalserviceid' => $serviceid, 'functionname' => 'eb_manage_user_cohort_enrollment')
        );

        $webservicefunctions = array_merge($ssofunctions, $bulkpurchasefunctions);

        foreach ( $ssofunctions as $function ) {

            // adding function without check because services.php runs after install.php 
            // and at this time there are no functions from this plugin.
            // check if function already exists.
            if( !$DB->record_exists('external_services_functions', array('externalserviceid' => $function['externalserviceid'], 'functionname' => $function['functionname'])) ){
                $DB->insert_record( 'external_services_functions', $function );
            }
        }
    }
}
/**
 * Enable the plugin in the default authentication method.
 */
function enable_edwiserbridge_plugin() {
    global $DB, $CFG;

    $auth = 'edwiserbridge';
    get_enabled_auth_plugins(true); // fix the list of enabled auths
    if (empty($CFG->auth)) {
        $authsenabled = array();
    } else {
        $authsenabled = explode(',', $CFG->auth);
    }
    if (!empty($auth) and !exists_auth_plugin($auth)) {
        return false;
    }
    if (!in_array($auth, $authsenabled)) {
        $authsenabled[] = $auth;
        $authsenabled = array_unique($authsenabled);
        set_config('auth', implode(',', $authsenabled));
    }
    \core\session\manager::gc(); // Remove stale sessions.
    core_plugin_manager::reset_caches();
}

/**
 * Check plugin update.
 */
function auth_edwiserbridge_check_plugin_update(){
    if (!function_exists('curl_version')) {
        return false;
    }

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => "https://edwiser.org/edwiserdemoimporter/bridge-free-plugin-info.json",
        CURLOPT_TIMEOUT => 100,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
    ));
    //construct a user agent string
    global $CFG;
    $useragent = 'Moodle/' . $CFG->version . ' (' . $CFG->wwwroot . ') Edwiser Bridge Update Checker';

    curl_setopt($curl, CURLOPT_USERAGENT, $useragent);
    $output = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if (200 === $httpcode) {
        $data = array(
            'time' => time() + (60 * 60 * 24),
            'data' => $output,
        );
        set_config('edwiserbridge_plugins_versions', json_encode($data), 'auth_edwiserbridge');
    }
    $output = json_decode($output);

    $pluginsdata = array();
    $pluginman   = \core_plugin_manager::instance();

    $authplugin                 = $pluginman->get_plugins_of_type('auth');
    $pluginsdata['edwiserbridge'] = get_string('mdl_edwiser_bridge_txt_not_avbl', 'auth_edwiserbridge');
    if (isset($authplugin['edwiserbridge'])) {
        $pluginsdata['edwiserbridge'] = $authplugin['edwiserbridge']->release;
    }

    if (false !== $output && isset($remotedata->moodle_edwiser_bridge->version) && version_compare($pluginsdata['edwiserbridge'], $remotedata->moodle_edwiser_bridge->version, "<")) {
        auth_edwiserbridge_prepare_plugin_update_notification($remotedata->moodle_edwiser_bridge);
    }
}

/**
 * Prepare Plugin update notification 
 */
function auth_edwiserbridge_prepare_plugin_update_notification($update_data){
    global $CFG;
    ob_start();
    ?>
    <div class="eb-plugin-update-notice">
        <p class="eb-plugin-update-notice-heading"><?php echo get_string('plugin_update_notification_title', 'auth_edwiserbridge'); ?></p>
        <p class="eb-plugin-update-notice-body"><?php echo get_string('plugin_update_notification_body', 'auth_edwiserbridge'); ?> <a class="eb-plugin-update-changelog" target="_blank" href="https://wordpress.org/plugins/edwiser-bridge/#developers"><?php echo get_string('plugin_update_notification_changelog', 'auth_edwiserbridge'); ?></a></p>
        <p class="eb-plugin-update-action">
            <a href="<?php echo $update_data->url ?>" title="<?php echo get_string('mdl_edwiser_bridge_txt_download_help', 'auth_edwiserbridge'); ?>"><?php echo get_string('plugin_download', 'auth_edwiserbridge'); ?></a>
            <span> | </span>
            <a href="UPDATE_URL" title="<?php echo get_string('plugin_update_help_text', 'auth_edwiserbridge'); ?>"><?php echo get_string('plugin_update', 'auth_edwiserbridge'); ?></a>
        </p>
        <a class="eb-plugin-update-dismiss" href="DISMISS_URL">x</a>
    </div>
    <?php
    $msg = ob_get_clean();
    set_config('edwiserbridge_update_msg', $msg, 'auth_edwiserbridge');
    set_config('edwiserbridge_update_available', 1, 'auth_edwiserbridge');
    set_config('edwiserbridge_dismiss_update_notification', 0, 'auth_edwiserbridge');
    set_config('edwiserbridge_update_data', json_encode($update_data), 'auth_edwiserbridge');
}

/**
 * Show plugin update notification
 */
function auth_edwiserbridge_show_plugin_update_notification(){
    global $PAGE, $ME;

    // to resolve duplicate notification issue.
    global $eb_notice;
    if( isset($eb_notice) && $eb_notice ){
        return;
    }
    $eb_notice = true;

    if ( isset($PAGE) && $PAGE->pagelayout == 'admin' && strpos($ME, 'installaddon/index.php') == false ){
        $update_available = get_config('auth_edwiserbridge', 'edwiserbridge_update_available');
        $dismiss = get_config('auth_edwiserbridge', 'edwiserbridge_dismiss_update_notification', 0);
        if ($update_available && !$dismiss) {
            $update_msg = get_config('auth_edwiserbridge', 'edwiserbridge_update_msg');

            global $CFG;
            $update_url = new moodle_url(
                $CFG->wwwroot . '/auth/edwiserbridge/install_update.php',
                array( 'installupdate' => 'auth_edwiserbridge', 'sesskey' => sesskey() )
            );

            $dismiss_url = new moodle_url(
                $CFG->wwwroot . '/auth/edwiserbridge/install_update.php',
                array( 'installupdate' => 'auth_edwiserbridge', 'sesskey' => sesskey(), 'dismiss' => 1 )
            );

            $update_msg = str_replace( 'UPDATE_URL', $update_url, $update_msg );

            $update_msg = str_replace( 'DISMISS_URL', $dismiss_url, $update_msg );

            // add notification.
            \core\notification::add($update_msg, \core\output\notification::NOTIFY_INFO);
        }
    }
}
