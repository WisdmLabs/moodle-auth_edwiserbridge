<?php
require_once(__DIR__ . '/compat.php');
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
 * Plugin lib file
 * All the general functions used by the plugin are defined here.
 *
 * @package    auth_edwiserbridge
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("{$CFG->libdir}/completionlib.php");
require_once($CFG->dirroot . '/webservice/lib.php');
// use core\plugin_manager as core_plugin_manager;
use core\exception\moodle_exception as moodle_exception;

/**
 * Checks if the older Edwiser Bridge plugin is installed.
 *
 * @return bool true if the older Edwiser Bridge plugin is not installed, false if it is installed.
 */
function auth_edwiserbridge_check_pro_dependancy() {
    $clear       = true;
    $pluginman   = core_plugin_manager::instance();
    $localplugin = $pluginman->get_plugins_of_type('local');
    if (isset($localplugin['edwiserbridge'])) {
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

// Dependancy check for older edwiser bridge plugin.
if (!auth_edwiserbridge_check_pro_dependancy()) {
    $pluginoverviewurl = new moodle_url('/admin/plugins.php', ['plugin' => 'overview']);
    $msg = get_string('edwiserbridgepropluginrequired', 'auth_edwiserbridge');
    $msg .= ' <a href="' . $pluginoverviewurl . '">' . get_string('backtopluginoverview', 'auth_edwiserbridge') . '</a>';

    // Abort installation. and redirect to plugin overview page.

    // Uninstall plugin.
    $pluginmanager = core_plugin_manager::instance();

    $pluginmanager->cancel_plugin_installation('auth_edwiserbridge');

    $pluginmanager::reset_caches();

    purge_all_caches();

    throw new moodle_exception($msg);
}

/**
 * Saves the connection form settings for the Edwiser Bridge plugin.
 *
 * @param object $formdata The form data containing the connection settings.
 * @param bool $mform Whether the form is being saved from a Moodle form.
 */
function auth_edwiserbridge_save_connection_form_settings($formdata, $mform = false) {
    // Checking if provided data count is correct or not.
    if (count($formdata->wp_url) != count($formdata->wp_token)) {
        return;
    }

    $connectionsettings = [];
    for ($i = 0; $i < count($formdata->wp_url); $i++) {
        if (! empty($formdata->wp_url[$i]) && ! empty($formdata->wp_token[$i]) && ! empty($formdata->wp_name[$i])) {
            $connectionsettings[$formdata->wp_name[$i]] = [
                'wp_url'   => $formdata->wp_url[$i],
                'wp_token' => $formdata->wp_token[$i],
                'wp_name'  => $formdata->wp_name[$i],
            ];
        }
    }
    set_config('eb_connection_settings', json_encode($connectionsettings), 'auth_edwiserbridge');
}

/**
 * Saves the synchronization settings for the individual site.
 *
 * @param object $formdata The form data containing the synchronization settings.
 * @param bool $mform Whether the form is being saved from a Moodle form.
 */
function auth_edwiserbridge_save_synchronization_form_settings($formdata, $mform = false) {
    $synchsettings          = [];
    $connection_db          = get_config('auth_edwiserbridge', 'eb_connection_settings');
    $connectionsettings     = json_decode($connection_db, true);
    $connectionsettingskeys = array_keys($connectionsettings);
    
    if (in_array($formdata->wp_site_list, $connectionsettingskeys)) {
        $sync_db               = get_config('auth_edwiserbridge', 'eb_synch_settings');
        $existingsynchsettings = !empty($sync_db) ? json_decode($sync_db, true) : [];
        $synchsettings         = $existingsynchsettings;

        $synchsettings[$formdata->wp_site_list] = [
            'course_enrollment'    => $formdata->course_enrollment,
            'course_un_enrollment' => $formdata->course_un_enrollment,
            'user_creation'        => $formdata->user_creation,
            'user_deletion'        => $formdata->user_deletion,
            'course_creation'      => $formdata->course_creation,
            'course_deletion'      => $formdata->course_deletion,
            'user_updation'        => $formdata->user_updation,
        ];
    }
    set_config('eb_synch_settings', json_encode($synchsettings), 'auth_edwiserbridge');
}

/**
 * Saves the SSO settings for the individual site.
 *
 * @param object $formdata The form data containing the SSO settings.
 * @param bool $mform Whether the form is being saved from a Moodle form.
 */
function auth_edwiserbridge_save_sso_form_settings($formdata, $mform = false) {
    set_config('sharedsecret', $formdata->sharedsecret, 'auth_edwiserbridge');
    set_config('wpsiteurl', $formdata->wpsiteurl, 'auth_edwiserbridge');
    set_config('logoutredirecturl', $formdata->logoutredirecturl, 'auth_edwiserbridge');
    set_config('wploginenablebtn', $formdata->wploginenablebtn, 'auth_edwiserbridge');
    set_config('wploginbtntext', $formdata->wploginbtntext, 'auth_edwiserbridge');
}

/**
 * Saves the general settings for Moodle.
 *
 * @param object $formdata The form data containing the general settings.
 * @param bool $mform Whether the form is being saved from a Moodle form.
 */
function auth_edwiserbridge_save_settings_form_settings($formdata, $mform = false) {
    global $CFG;

    if (isset($formdata->web_service) && isset($formdata->pass_policy) && isset($formdata->extended_username)) {

        $activewebservices = empty($CFG->webserviceprotocols) ? [] : explode(',', $CFG->webserviceprotocols);

        if ($formdata->rest_protocol) {
            $activewebservices[] = 'rest';
        } else {
            $key = array_search('rest', $activewebservices);
            unset($activewebservices[$key]);
        }

        set_config('webserviceprotocols', implode(',', $activewebservices));
        set_config('enablewebservices', $formdata->web_service);
        set_config('extendedusernamechars', $formdata->extended_username);
        set_config('passwordpolicy', $formdata->pass_policy);
        set_config('enable_auto_update_check', $formdata->enable_auto_update_check);
    }
}

/**
 * Retrieves the required settings for the Edwiser Bridge plugin from the Moodle configuration.
 *
 * This function retrieves the values of various Moodle settings that are required for the Edwiser Bridge plugin to function properly.
 * The settings include the status of the web services, whether extended username characters are enabled, the password policy, and whether automatic update checks are enabled.
 *
 * @return array An associative array containing the required settings.
 */
function auth_edwiserbridge_get_required_settings() {
    global $CFG;

    $requiredsettings = [];

    $activewebservices = empty($CFG->webserviceprotocols) ? [] : explode(',', $CFG->webserviceprotocols);

    $requiredsettings['rest_protocol'] = 0;
    if (false !== array_search('rest', $activewebservices)) {
        $requiredsettings['rest_protocol'] = 1;
    }

    $requiredsettings['web_service']              = isset($CFG->enablewebservices) ? $CFG->enablewebservices : false;
    $requiredsettings['extended_username']        = isset($CFG->extendedusernamechars) ? $CFG->extendedusernamechars : false;
    $requiredsettings['pass_policy']              = isset($CFG->passwordpolicy) ? $CFG->passwordpolicy : false;
    $requiredsettings['enable_auto_update_check'] = isset($CFG->enable_auto_update_check) ? $CFG->enable_auto_update_check : 1;

    return $requiredsettings;
}

/**
 * Returns connection settings saved in the settings form.
 *
 * This function retrieves the connection settings for the Edwiser Bridge plugin that have been saved in the Moodle configuration.
 * The settings are stored in the $CFG->eb_connection_settings variable, which is decodes and returned as an associative array.
 *
 * @return array An associative array containing the connection settings, or false if the settings are not found.
 */
function auth_edwiserbridge_get_connection_settings() {
    $eb_connection_settings = get_config('auth_edwiserbridge', 'eb_connection_settings');
    $reponse['eb_connection_settings'] = !empty($eb_connection_settings) ? json_decode($eb_connection_settings, true) : false;
    return $reponse;
}

/**
 * Returns the synchronization settings for the given index.
 *
 * This function retrieves the synchronization settings for the Edwiser Bridge plugin based on the provided index.
 * The settings are stored in the $CFG->eb_synch_settings variable, which is decoded and returned as an associative array.
 * If the settings are not found, a default array is returned.
 *
 * @param int $index The index of the synchronization settings to retrieve.
 * @return array The synchronization settings for the given index, or a default array if the settings are not found.
 */
function auth_edwiserbridge_get_synch_settings($index) {
    $eb_synch_settings = get_config('auth_edwiserbridge', 'eb_synch_settings');
    $reponse = !empty($eb_synch_settings) ? json_decode($eb_synch_settings, true) : false;

    $data = [
        'course_enrollment'    => 0,
        'course_un_enrollment' => 0,
        'user_creation'        => 0,
        'user_deletion'        => 0,
        'course_creation'      => 0,
        'course_deletion'      => 0,
        'user_updation'        => 0,
    ];

    if (isset($reponse[$index]) && ! empty($reponse[$index])) {
        return $reponse[$index];
    }
    return $data;
}

/**
 * Returns a list of all the sites created in the Edwiser settings.
 *
 * This function retrieves the list of sites that have been configured in the Edwiser Bridge plugin settings. It checks if the
 * $CFG->eb_connection_settings variable is set and decodes it to get the site information. If the variable is not set or
 * empty, it returns a single-element array with a default message.
 *
 * @return array An associative array of site keys and names, or a single-element array with a default message if no sites are found.
 */
function auth_edwiserbridge_get_site_list() {
    $eb_connection_settings = get_config('auth_edwiserbridge', 'eb_connection_settings');
    $reponse = !empty($eb_connection_settings) ? json_decode($eb_connection_settings, true) : false;

    if ($reponse && count($reponse)) {
        foreach ($reponse as $key => $value) {
            $sites[$key] = $value['wp_name'];
        }
    } else {
        $sites = ['' => get_string('eb_no_sites', 'auth_edwiserbridge')];
    }
    return $sites;
}

/**
 * Returns the main instance of EDW to prevent the need to use globals.
 *
 * @since  1.0.0
 *
 * @return \auth_edwiserbridge\local\api_handler The main instance of the EDW API handler.
 */
function auth_edwiserbridge_api_handler_instance() {
    return auth_edwiserbridge\local\api_handler::instance();
}

/**
 * Returns an array of course IDs that the specified user is enrolled in.
 *
 * @param int $userid The ID of the user to get the enrolled courses for.
 * @return array An array of course IDs that the user is enrolled in.
 */
function auth_edwiserbridge_get_array_of_enrolled_courses($userid) {
    $enrolledcourses = enrol_get_users_courses($userid);
    $courses         = [];

    foreach ($enrolledcourses as $value) {
        array_push($courses, $value->id);
    }
    return $courses;
}

/**
 * Removes a specific course ID from the provided array of course IDs. 
 * Removes processed coureses from the course whose progress is already provided.
 *
 * @param int   $courseid The ID of the course to remove from the array.
 * @param array $courses  The array of course IDs to remove the specified course from.
 * @return array The updated array of course IDs with the specified course removed.
 */
function auth_edwiserbridge_remove_processed_coures($courseid, $courses) {
    $key = array_search($courseid, $courses);
    if ($key !== false) {
        unset($courses[$key]);
    }
    return $courses;
}

/**
 * Checks if the current request is from WordPress and stops processing the enrollment and unenrollment.
 *
 * This function checks if the current request contains the 'enrolments' or 'cohort' POST parameters,
 * which are used for enrollment and unenrollment processing. If either of these parameters is present,
 * the function returns 1 to indicate that the request is from WordPress and the processing should be stopped.
 *
 * @return int 1 if the request is from WordPress, 0 otherwise.
 */
function auth_edwiserbridge_check_if_request_is_from_wp() {
    $required = 0;

    // Using this condition because param enrollments and cohort are multi dimensional array
    // and it is not working with optional_param or optional_param_array.
    if (isset($_POST['enrolments']) || isset($_POST['cohort'])) {
        $required = 1;
    }

    // check the wsfunction param to check if the request is from WordPress (for user deletion). as there are no other unique params to check.
    if ( isset( $_GET['wsfunction'] ) && 'core_user_delete_users' === $_GET['wsfunction'] ) {
        $required = 1;
    }

    return $required;
}

/*
-----------------------------------------------------------
*   Functions used in Settings page
*----------------------------------------------------------*/

/**
 * Retrieves a list of Moodle site administrators and their email addresses.
 *
 * This function fetches the list of Moodle site administrators using the `get_admins()` function,
 * and then creates an associative array where the keys are the administrator IDs and the values
 * are their email addresses. An empty string key is also included with the value of a localized
 * string for the "new service user" label.
 *
 * @return array An associative array of administrator IDs and their email addresses.
 */
function auth_edwiserbridge_get_administrators() {
    $admins          = get_admins();
    $settingsarr     = [];
    $settingsarr[''] = get_string('new_service_user_lbl', 'auth_edwiserbridge');

    foreach ($admins as $value) {
        $settingsarr[$value->id] = $value->email;
    }
    return $settingsarr;
}

/**
 * Retrieves a list of available Moodle site services.
 *
 * This function fetches the list of external services from the Moodle database and
 * creates an associative array where the keys are the service IDs and the values
 * are the service names. It also includes a special entry with an empty key and
 * the value of a localized string for the "existing service" label, as well as
 * a "create" entry for creating a new service.
 *
 * @return array An associative array of available Moodle site services.
 */
function auth_edwiserbridge_get_existing_services() {
    global $DB;

    $settingsarr = [];
    // No method to fetch all the enabled webservices in the Moodle Webservice class so fetching directly from DB.
    $services = $DB->get_records('external_services', ['enabled' => 1], 'id ASC', 'id,name');

    // Maintain original return format
    $settingsarr[''] = get_string('existing_service_lbl', 'auth_edwiserbridge');
    $settingsarr['create'] = ' - ' . get_string('new_web_new_service', 'auth_edwiserbridge') . ' - ';

    foreach ($services as $service) {
        $settingsarr[$service->id] = $service->name;
    }

    return $settingsarr;
}

/**
 * Gets the list of service tokens for the given service ID. 
 *
 * This function fetches the list of external tokens from the Moodle database and
 * creates an associative array where the keys are the token values and the values
 * are the associated external service IDs.
 *
 * @param int $serviceid The ID of the external service.
 * @return array An array of tokens and their associated service IDs.
 */
function auth_edwiserbridge_get_service_tokens($serviceid) {
    global $DB;

    $settingsarr = [];
    // No method to fetch all the tokens in the Moodle's webservice class so fetching directly from DB.
    // To be replaced in the future if available.
    $result      = $DB->get_records('external_tokens', null, '', 'token, externalserviceid');

    foreach ($result as $value) {
        $settingsarr[] = [
            'token' => $value->token,
            'id'    => $value->externalserviceid,
        ];
    }

    return $settingsarr;
}

/**
 * Generates an HTML field for creating a token.
 *
 * This function generates an HTML field that allows the user to create a token
 * for the specified external service. It retrieves the list of existing tokens
 * for the service and populates the field with the options. The function also
 * provides a "Copy" button to allow the user to easily copy the selected token.
 *
 * @param int $serviceid The ID of the external service.
 * @param string $existingtoken The existing token, if any.
 * @return string The HTML content for the token creation field.
 */
function auth_edwiserbridge_create_token_field($serviceid, $existingtoken = '') {
    global $PAGE;
    $tokenslist = auth_edwiserbridge_get_service_tokens($serviceid);

    $tokens = [];
    foreach ($tokenslist as $token) {
        $tokens[] = [
            'id' => $token['id'] ?? '',
            'token' => $token['token'] ?? '',
            'display' => (isset($token['id']) && $token['id'] != $serviceid) ? 'style="display:none"' : '',
            'selected' => (isset($token['token']) && $token['token'] == $existingtoken) ? 'selected' : ''
        ];
    }

    $data = [
        'token_dropdown_lbl' => get_string('token_dropdown_lbl', 'auth_edwiserbridge'),
        'tokens' => $tokens,
        'copy_btn_text' => get_string('copy', 'auth_edwiserbridge')
    ];

    $output = $PAGE->get_renderer('core');
    $html = $output->render_from_template('auth_edwiserbridge/create_token_field', $data);

    return $html;
}

/**
 * Gets the list of service tokens for the given service ID. 
 * Functionality to get count of not available services which are required for Edwiser-Bridge.
 *
 * @param int $serviceid The ID of the external service.
 * @return array An array of service tokens, with the token and ID for each.
 */
function auth_edwiserbridge_get_service_list($serviceid) {
    $webservicemanager = new \webservice();
    $service = $webservicemanager->get_external_service_by_id($serviceid);
    
    if (!$service) {
        return 0;
    }

    $requiredfunctions = [
        'core_user_create_users',
        'core_user_delete_users',
        'core_user_get_users_by_field',
        'core_user_update_users',
        'core_course_get_courses',
        'core_course_get_categories',
        'enrol_manual_enrol_users',
        'enrol_manual_unenrol_users',
        'core_enrol_get_users_courses',
        'auth_edwiserbridge_test_connection',
        'auth_edwiserbridge_validate_token',
        'auth_edwiserbridge_get_site_data',
        'auth_edwiserbridge_get_course_progress',
        'auth_edwiserbridge_get_edwiser_plugins_info',
        'auth_edwiserbridge_get_course_enrollment_method',
        'auth_edwiserbridge_update_course_enrollment_method',
        'auth_edwiserbridge_get_mandatory_settings',
        'auth_edwiserbridge_enable_plugin_settings',
        'auth_edwiserbridge_get_users',
        'auth_edwiserbridge_get_courses',
    ];

    $license = new \auth_edwiserbridge\local\eb_pro_license_controller();

    if ($license->get_data_from_db() == 'available') {
        $bulkpurchase = [
            'core_cohort_add_cohort_members',
            'core_cohort_create_cohorts',
            'core_role_assign_roles',
            'core_role_unassign_roles',
            'core_cohort_delete_cohort_members',
            'core_cohort_get_cohorts',
            'auth_edwiserbridge_manage_cohort_enrollment',
            'auth_edwiserbridge_delete_cohort',
            'auth_edwiserbridge_manage_user_cohort_enrollment',
        ];
        $ssofunctions = [
            'auth_edwiserbridge_verify_sso_token',
        ];
    } else {
        $bulkpurchase = [];
        $ssofunctions = [];
    }

    $requiredfunctions = array_merge($requiredfunctions, $bulkpurchase, $ssofunctions);

    $missingcount = 0;
    foreach ($requiredfunctions as $function) {
        if (!$webservicemanager->service_function_exists($function, $serviceid)) {
            $missingcount++;
        }
    }

    return $missingcount;
}

/**
 * Checks the status of various Edwiser Bridge settings and returns a summary status.
 *
 * This function checks the values of various Edwiser Bridge settings, such as
 * 'enablewebservices', 'passwordpolicy', 'extendedusernamechars', and
 * 'webserviceprotocols'. It also checks for the existence of certain configuration
 * variables, such as 'ebexistingserviceselect' and 'edwiser_bridge_last_created_token'.
 * Based on the results of these checks, the function returns one of three possible
 * status values: 'error', 'warning', or 'success'.
 *
 * @return string The summary status of the Edwiser Bridge settings.
 */
function auth_edwiserbridge_get_summary_status() {
    global $CFG;

    $settingsarray = [
        'enablewebservices'     => 1,
        'passwordpolicy'        => 0,
        'extendedusernamechars' => 1,
        'webserviceprotocols'   => 1,
    ];

    foreach ($settingsarray as $key => $value) {
        if (isset($CFG->$key) && $value != $CFG->$key) {
            if ($key == 'webserviceprotocols') {
                $activewebservices = empty($CFG->webserviceprotocols) ? [] : explode(',', $CFG->webserviceprotocols);
                if (! in_array('rest', $activewebservices)) {
                    return 'error';
                }
            } else {
                return 'error';
            }
        }
    }

    $servicearray = [
        'ebexistingserviceselect',
        'edwiser_bridge_last_created_token',
    ];

    foreach ($servicearray as $value) {
        if (empty(get_config('auth_edwiserbridge', $value))) {
            return 'warning';
        }
    }
    return 'sucess';
}

/**
 * Serves the files from the auth_edwiserbridge file areas.
 *
 * This function is responsible for serving files from the auth_edwiserbridge plugin's file areas.
 * It checks the context level, retrieves the file based on the provided arguments, and sends the
 * stored file to the client, forcing the download.
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
    array $options = []
) {
    if ($context->contextlevel != CONTEXT_SYSTEM) {
        send_file_not_found();
    }
    $itemid       = (int) array_shift($args);
    $relativepath = implode('/', $args);
    $fullpath     = "/{$context->id}/auth_edwiserbridge/$filearea/$itemid/$relativepath";
    $fs           = get_file_storage();
    if (! ($file = $fs->get_file_by_hash(sha1($fullpath)))) {
        return false;
    }
    // Download MUST be forced - security!
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Checks and updates the web service functions for the auth_edwiserbridge plugin.
 *
 * This function retrieves the connection settings for the Edwiser Bridge plugin,
 * and then checks and updates the web service functions associated with the
 * external service ID. It adds any missing functions to the
 * external_services_functions table.
 */
function auth_edwiserbridge_check_and_update_webservice_functions() {
    $webservicemanager = new \webservice();
    $eb_connection_settings = get_config('auth_edwiserbridge', 'eb_connection_settings');
    $connections = !empty($eb_connection_settings) ? json_decode($eb_connection_settings, true) : [];
    if (!empty($connections)) {
        foreach ($connections as $connection) {
            $token = $webservicemanager->get_user_ws_token($connection['wp_token']);
            $serviceid = $token ? $token->externalserviceid : '';
    
            if (empty($serviceid)) {
                continue;
            }
    
            $basefunctions = [
                'core_user_create_users',
                'core_user_delete_users',
                'core_user_get_users_by_field',
                'core_user_update_users',
                'core_course_get_courses',
                'core_course_get_categories',
                'enrol_manual_enrol_users',
                'enrol_manual_unenrol_users',
                'core_enrol_get_users_courses',
                'auth_edwiserbridge_test_connection',
                'auth_edwiserbridge_validate_token',
                'auth_edwiserbridge_get_site_data',
                'auth_edwiserbridge_get_course_progress',
                'auth_edwiserbridge_get_edwiser_plugins_info',
                'auth_edwiserbridge_get_course_enrollment_method',
                'auth_edwiserbridge_update_course_enrollment_method',
                'auth_edwiserbridge_get_mandatory_settings',
                'auth_edwiserbridge_enable_plugin_settings',
                'auth_edwiserbridge_get_users',
                'auth_edwiserbridge_get_courses'
            ];
            // Define required functions
            $ssofunctions = ['auth_edwiserbridge_verify_sso_token'];
            $bulkpurchasefunctions = [
                'core_cohort_add_cohort_members',
                'core_cohort_create_cohorts',
                'core_role_assign_roles',
                'core_role_unassign_roles',
                'core_cohort_delete_cohort_members',
                'core_cohort_get_cohorts',
                'auth_edwiserbridge_manage_cohort_enrollment',
                'auth_edwiserbridge_delete_cohort',
                'auth_edwiserbridge_manage_user_cohort_enrollment'
            ];
    
            $webservicefunctions = array_merge($ssofunctions, $basefunctions, $bulkpurchasefunctions);
    
            foreach ($webservicefunctions as $functionname) {
                if (!$webservicemanager->service_function_exists($functionname, $serviceid)) {
                    $webservicemanager->add_external_function_to_service($functionname, $serviceid);
                }
            }
        }
    }
}

/**
 * Enables the Edwiser Bridge authentication plugin in the default authentication method.
 *
 * This function checks if the Edwiser Bridge authentication plugin is enabled, and if not, adds it to the list of
 * enabled authentication plugins. It then removes any stale sessions and resets the plugin caches.
 */
function auth_edwiserbridge_enable_plugin() {
    global $CFG;

    $auth = 'edwiserbridge';
    get_enabled_auth_plugins(true); // Fix the list of enabled auths.
    if (empty($CFG->auth)) {
        $authsenabled = [];
    } else {
        $authsenabled = explode(',', $CFG->auth);
    }
    if (! empty($auth) && ! exists_auth_plugin($auth)) {
        return false;
    }
    if (! in_array($auth, $authsenabled)) {
        $authsenabled[] = $auth;
        $authsenabled   = array_unique($authsenabled);
        set_config('auth', implode(',', $authsenabled));
    }
    \core\session\manager::gc(); // Remove stale sessions.
    core_plugin_manager::reset_caches();
}

/**
 * Checks for updates to the Edwiser Bridge plugin and prepares a notification if an update is available.
 *
 * This function retrieves the latest version information for the Edwiser Bridge plugin from a remote server,
 * compares it to the currently installed version, and if an update is available, it prepares a notification
 * to be displayed to the user.
 *
 * The notification includes information about the new version, a changelog URL, and links to download and
 * update the plugin.
 */
function auth_edwiserbridge_check_plugin_update() {
    // Construct a user agent string.
    global $CFG;

    include_once($CFG->libdir . '/filelib.php'); // Include Moodle's filelib for the `curl` class.
    
    $useragent = 'Moodle/' . $CFG->version . ' (' . $CFG->wwwroot . ') Edwiser Bridge Update Checker';

    // Set up Moodle's curl instance.
    $curl = new \curl([
        'timeout' => 100,
        'sslverifyhost' => false,
        'sslverifypeer' => false,
    ]);

    $url = 'https://edwiser.org/edwiserdemoimporter/bridge-free-plugin-info.json';
    $options = [
        'CURLOPT_USERAGENT' => $useragent,
    ];

    $output = $curl->get($url, null, $options);

    // Check the HTTP response code.
    $httpcode = $curl->info['http_code'];

    if (200 === $httpcode) {
        $data = [
            'time' => time() + (60 * 60 * 24),
            'data' => $output,
        ];
        set_config('edwiserbridge_plugins_versions', json_encode($data), 'auth_edwiserbridge');
    }
    $output = json_decode($output);

    $pluginsdata = [];
    $pluginman   = core_plugin_manager::instance();

    $authplugin                   = $pluginman->get_plugins_of_type('auth');
    $pluginsdata['edwiserbridge'] = get_string('mdl_edwiser_bridge_txt_not_avbl', 'auth_edwiserbridge');
    if (isset($authplugin['edwiserbridge'])) {
        $pluginsdata['edwiserbridge'] = $authplugin['edwiserbridge']->release;
        // $pluginsdata['edwiserbridge'] = '3.0.0';
    }

    if (
        false !== $output &&
        isset($output->moodle_edwiser_bridge->version) &&
        version_compare($pluginsdata['edwiserbridge'], $output->moodle_edwiser_bridge->version, '<')
    ) {
        auth_edwiserbridge_show_plugin_update_notification();
        auth_edwiserbridge_prepare_plugin_update_notification($output->moodle_edwiser_bridge);
    }
}

/**
 * Prepare the plugin update notification.
 *
 * This function is responsible for preparing the plugin update notification
 * that will be displayed to the user when a new version of the Edwiser Bridge
 * plugin is available.
 *
 * @param object $updatedata The update data for the Edwiser Bridge plugin.
 */
function auth_edwiserbridge_prepare_plugin_update_notification($updatedata) {
    global $CFG, $PAGE;

    $renderer = $PAGE->get_renderer('core');

    if (isset($CFG->enable_auto_update_check) && $CFG->enable_auto_update_check == true) {
        // Mustache rendering data
        $templatecontext = [
            'pluginupdatenotificationtitle' => get_string('plugin_update_notification_title', 'auth_edwiserbridge'),
            'pluginupdatenotificationbody' => get_string('plugin_update_notification_body', 'auth_edwiserbridge'),
            'pluginupdatenotificationchangelog' => get_string('plugin_update_notification_changelog', 'auth_edwiserbridge'),
            'changelogurl' => 'https://wordpress.org/plugins/edwiser-bridge/#developers', // Replace with actual changelog URL
            'downloadurl' => $updatedata->url,
            'plugindownloadhelptext' => get_string('mdl_edwiser_bridge_txt_download_help', 'auth_edwiserbridge'),
            'plugindownload' => get_string('plugin_download', 'auth_edwiserbridge'),
            'updateurl' => 'UPDATE_URL', // Replace with actual update URL
            'pluginupdatehelptext' => get_string('plugin_update_help_text', 'auth_edwiserbridge'),
            'pluginupdate' => get_string('plugin_update', 'auth_edwiserbridge'),
            'dismissurl' => 'DISMISS_URL', // Replace with actual dismiss URL
        ];

        // Rendering Mustache template with data
        $msg = $renderer->render_from_template('auth_edwiserbridge/plugin_update_notification', $templatecontext);

        // Set configurations
        set_config('edwiserbridge_update_msg', $msg, 'auth_edwiserbridge');
        set_config('edwiserbridge_update_available', 1, 'auth_edwiserbridge');
        set_config('edwiserbridge_dismiss_update_notification', 0, 'auth_edwiserbridge');
        set_config('edwiserbridge_update_data', json_encode($updatedata), 'auth_edwiserbridge');
    }
}

/**
 * Shows the plugin update notification if an update is available and the user has not dismissed the notification.
 * This function checks the configuration settings, retrieves the update message and URLs, and adds the notification to the Moodle interface.
 *
 * @return void
 */
function auth_edwiserbridge_show_plugin_update_notification() {
    global $PAGE, $ME, $CFG;

    if (isset($CFG->enable_auto_update_check) && $CFG->enable_auto_update_check == true) {

        // To resolve duplicate notification issue.
        global $ebnotice;
        if (isset($ebnotice) && $ebnotice) {
            return;
        }
        $ebnotice = true;

        if (isset($PAGE) && $PAGE->pagelayout == 'admin' && strpos($ME, 'installaddon/index.php') == false && strpos($ME, 'setup_wizard.php') == false ) {
            $updateavailable = get_config('auth_edwiserbridge', 'edwiserbridge_update_available');
            $dismiss = get_config('auth_edwiserbridge', 'edwiserbridge_dismiss_update_notification', 0);
            if ($updateavailable && ! $dismiss) {
                $updatemsg = get_config('auth_edwiserbridge', 'edwiserbridge_update_msg');

                global $CFG;
                $updateurl = new moodle_url(
                    $CFG->wwwroot . '/auth/edwiserbridge/install_update.php',
                    [
                        'installupdate' => 'auth_edwiserbridge',
                        'sesskey'       => sesskey(),
                    ]
                );

                $dismissurl = new moodle_url(
                    $CFG->wwwroot . '/auth/edwiserbridge/install_update.php',
                    [
                        'installupdate' => 'auth_edwiserbridge',
                        'sesskey'       => sesskey(),
                        'dismiss'       => 1,
                    ]
                );

                $updatemsg = str_replace('UPDATE_URL', $updateurl->out(), $updatemsg);

                $updatemsg = str_replace('DISMISS_URL', $dismissurl->out(), $updatemsg);

                // Add notification.
                \core\notification::add($updatemsg, \core\output\notification::NOTIFY_INFO);
            }
        }
    }
}

/**
 * Get the shared secret key for SSO authentication.
 * If the secret key is not set, redirect the user to the WordPress site with an error parameter.
 *
 * @return string The shared secret key, or an empty string if the key is not set and the user is redirected.
 */
function auth_edwiserbridge_get_sso_secret_key() {
    global $CFG;
    $secretkey = get_config('auth_edwiserbridge', 'sharedsecret');
    $tempurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
    if ($tempurl == null) {
        $tempurl = get_config('auth_edwiserbridge', 'wpsiteurl');
    }

    if ($tempurl == '') {
        $tempurl = $CFG->wwwroot;
    }

    if (empty($secretkey)) {
        $wordpressurl = str_replace('wp-login.php', '', $tempurl);
        if (strpos($wordpressurl, '?') !== false) {
            $wordpressurl .= '&wdm_moodle_error=wdm_moodle_error';
        } else {
            $wordpressurl .= '?wdm_moodle_error=wdm_moodle_error';
        }
        redirect($wordpressurl);
        return;
    }
    return $secretkey;
}

/**
 * Decrypts a base64-encoded string using the provided key.
 *
 * This function is used to decrypt incoming data that has been specially encoded in base64 format, where the
 * encoded data contains a string of key=value pairs.
 *
 * @param string $base64 The base64-encoded string to decrypt.
 * @param string $key The key to use for decryption.
 * @return string The decrypted string, or an empty string if the input is invalid.
 */
function auth_edwiserbridge_decrypt_string($base64, $key) {
    if (!$base64) {
        return '';
    }
    $data = str_replace(['-', '_'], ['+', '/'], $base64); // Convert URL-safe Base64 back to standard Base64

    // Base64 length must be evenly divisible by 4, so we pad if necessary
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    // Decode the Base64 data
    $crypttext = base64_decode($data);

    // AES-256-ECB does not use an IV, so we don't need to split the data
    // Directly decrypt the data
    $encmethod = 'AES-256-ECB'; // Use AES-256-ECB encryption method
    $enckey = openssl_digest( $key, 'SHA256', true); // Hash the key to 256 bits using SHA-256
    // Decrypt the token with AES-256-ECB (no IV required)
    $decryptedtoken = openssl_decrypt($crypttext, $encmethod, $enckey, 0);
    // Return the decrypted value, trimmed of any extra spaces or characters
    return trim($decryptedtoken);
}

/**
 * Query string helper, returns the value of a key in a string formatted in key=value&key=value&key=value pairs,
 * e.g. saved querystrings.
 *
 * @param string $string The string containing the key-value pairs.
 * @param string $key The key to search for in the string.
 * @return string The value of the specified key, or an empty string if the key is not found.
 * @package auth_edwiserbridge
 */
function auth_edwiserbridge_get_key_value($string, $key) {
    $list = explode('&', str_replace('&amp;', '&', $string));
    foreach ($list as $pair) {
        $item = explode('=', $pair);
        if (strtolower($key) == strtolower($item[0])) {
            // Not for use in $_GET etc, which is already decoded,
            // however our encoder uses http_build_query() before encrypting.
            return urldecode($item[1]);
        }
    }
    return '';
}

/**
 * Get user session data.
 *
 * @param int $userid user id.
 * @param string $sessionkey session key.
 * @return object session data.
 * @package auth_edwiserbridge
 */
function auth_edwiserbridge_get_user_session($userid, $sessionkey) {
    $record = get_user_preferences($sessionkey, '', $userid);
    return $record;
}

/**
 * Set user session data.
 *
 * @param int $userid user id.
 * @param string $sessionkey session key.
 * @param string $wdmdata session data.
 * @package auth_edwiserbridge
 */
function auth_edwiserbridge_set_user_session($userid, $sessionkey, $wdmdata) {
    set_user_preference($sessionkey, $wdmdata, $userid);
}

/**
 * Remove user session data.
 *
 * @param int $userid user id.
 * @package auth_edwiserbridge
 */
function auth_edwiserbridge_remove_user_session($userid) {
    unset_user_preference('eb_sso_user_session_id', $userid);
}

/**
 * Redirect the user to the root URL of the Moodle site.
 *
 * This function is used to redirect the user to the root URL of the Moodle site, which is stored in the $CFG->wwwroot global variable.
 * The current URL that the user wants to access is stored in the $SESSION->wantsurl global variable, and this function uses the redirect() function to redirect the user to the root URL.
 */
function auth_edwiserbridge_redirect_to_root() {
    global $CFG, $SESSION;
    $SESSION->wantsurl = $CFG->wwwroot;
    redirect($SESSION->wantsurl);
}
