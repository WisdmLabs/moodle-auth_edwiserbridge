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
 * Settings handler.
 * Saves and handle all Moodle settings related functionalities.
 *
 * @package    auth_edwiserbridge
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_edwiserbridge\local;

use Exception;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . "/externallib.php");

/**
 * Saves and handle all Moodle settings related functionalities.
 *
 * @package     auth_edwiserbridge
 * @copyright   2021 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class settings_handler {

    /**
     * Creates an external service with the provided name and user ID.
     *
     * @param string $name   The name of the external service.
     * @param int $userid The user ID associated with the external service.
     * @return array An array containing the response status, message, token, site URL, and service ID.
     */
    public function eb_create_externle_service($name, $userid) {
        global $CFG;
        require_once($CFG->dirroot . '/webservice/lib.php');
        
        // Response initializations.
        $response = [
            'status' => 1,
            'msg' => '',
            'token' => 0,
            'site_url' => $CFG->wwwroot,
            'service_id' => 0
        ];

        // User id validation.
        if (empty($userid)) {
            $response['status'] = 0;
            $response['msg'] = get_string('empty_userid_err', 'auth_edwiserbridge');
            return $response;
        }

        $shortname = $this->eb_generate_service_shortname();
        if (empty($shortname)) {
            $response['status'] = 0;
            $response['msg'] = get_string('create_service_shortname_err', 'auth_edwiserbridge');
            return $response;
        }

        if ($this->eb_check_if_service_name_available($name)) {
            $response['status'] = 0;
            $response['msg'] = get_string('create_service_name_err', 'auth_edwiserbridge');
            return $response;
        }

        $webservicemanager = new \webservice();

        // Service creation default data.
        $servicedata = [
            'name' => $name,
            'shortname' => $shortname,
            'enabled' => 1,
            'restrictedusers' => 1,
            'downloadfiles' => 0,
            'uploadfiles' => 0,
            'requiredcapability' => null,
            'component' => null,
            'timecreated' => time(),
            'timemodified' => null,
        ];

        try {
            $service = $webservicemanager->add_external_service((object) $servicedata);
            
            if ($service) {
                $this->eb_add_auth_user($service, $userid);
                $this->eb_add_default_web_service_functions($service);
                $token = $this->eb_create_token($service, $userid);
                
                $response['service_id'] = $service;
                $response['token'] = $token;
            } else {
                $response['status'] = 0;
                $response['msg']    = get_string('create_service_creation_err', 'auth_edwiserbridge');
                return $response;
            }
        } catch (Exception $e) {
            $response['status'] = 0;
            $response['msg'] = get_string('create_service_creation_err', 'auth_edwiserbridge');
        }
        return $response;
    }

    /**
     * Generates a unique shortname for an external service.
     *
     * This function generates a new shortname for an external service by appending a
     * sequential number to the base 'edwiser' shortname. It checks if the generated
     * shortname is already in use in the 'external_services' table, and continues
     * generating new shortnames until a unique one is found, or a maximum of 100
     * attempts is reached.
     *
     * @return string The new unique shortname, or 0 if a unique shortname could not
     *         be generated after 100 attempts.
     */
    public function eb_generate_service_shortname() {
        global $CFG;
        require_once($CFG->dirroot . '/webservice/lib.php');
        $webservicemanager = new \webservice();
        $shortname = 'edwiser';
        $numtries  = 0;
        do {
            $numtries++;
            $newshortname = $shortname . $numtries;
            if ($numtries > 100) {
                return 0;
            }
        } while ($webservicemanager->get_external_service_by_shortname($newshortname));

        return $newshortname;
    }

    /**
     * Checks if the provided service name is already registered.
     *
     * This function checks if the given service name is already registered in the
     * 'external_services' table. It returns 0 if the service name is already
     * registered, and 1 if the service name is available.
     *
     * @param string $servicename The service name to check.
     * @return int 0 if the service name is already registered, 1 if it is available.
     */
    public function eb_check_if_service_name_available($servicename) {
        global $DB;

        // No method to get service by name only by name. To be replaced in the future when method becomes available.
        $service = $DB->get_record('external_services',
                        array('name' => $servicename), 'id', IGNORE_MISSING);
        return $service;
    }

    /**
     * Adds an authorized user for the external service.
     *
     * This function adds a user as an authorized user for the specified external service.
     * It inserts a new record in the 'external_services_users' table with the provided
     * service ID and user ID.
     *
     * @param int $serviceid The ID of the external service.
     * @param int $userid The ID of the user to be added as an authorized user.
     */
    public function eb_add_auth_user($serviceid, $userid) {
        global $CFG;
        require_once($CFG->dirroot . '/webservice/lib.php');
        $webservicemanager = new \webservice();
        
        $userdata = [
            'externalserviceid' => $serviceid,
            'userid' => $userid,
            'iprestriction' => null,
            'validuntil' => null,
        ];

        $webservicemanager->add_ws_authorised_user((object) $userdata);
    }

    /**
     * Adds the default web service functions registered with the Edwiser Bridge plugin.
     *
     * This function adds a set of default web service functions to the external service
     * identified by the provided $serviceid. The functions added are related to user
     * management, course management, and other Edwiser Bridge specific operations.
     *
     * @param int $serviceid The ID of the external service to add the functions to.
     */
    public function eb_add_default_web_service_functions($serviceid) {
        global $CFG;
        require_once($CFG->dirroot . '/webservice/lib.php');
        $webservicemanager = new \webservice();
        
        $functions = [
            'core_user_create_users',
            'core_user_delete_users',
            'core_user_get_users_by_field',
            'core_user_update_users',
            'core_course_get_courses',
            'core_course_get_courses_by_field',
            'core_course_get_categories',
            'enrol_manual_enrol_users',
            'enrol_manual_unenrol_users',
            'core_enrol_get_users_courses',
            'auth_edwiserbridge_test_connection',
            'auth_edwiserbridge_get_site_data',
            'auth_edwiserbridge_get_course_progress',
            'auth_edwiserbridge_get_edwiser_plugins_info',
            'auth_edwiserbridge_get_course_enrollment_method',
            'auth_edwiserbridge_update_course_enrollment_method',
            'auth_edwiserbridge_get_mandatory_settings',
            'auth_edwiserbridge_enable_plugin_settings',
            'auth_edwiserbridge_validate_token',
        ];

        foreach ($functions as $functionname) {
            if (!$webservicemanager->service_function_exists($functionname, $serviceid)) {
                $webservicemanager->add_external_function_to_service($functionname, $serviceid);
            }
        }

        $this->eb_extensions_web_service_function($serviceid);
    }

    /**
     * This function adds extensions web services which are registered with the edwiser-bridge only.
     *
     * @param int $serviceid The ID of the external service to add the extension functions to.
     */
    public function eb_extensions_web_service_function($serviceid) {
        global $CFG;
        require_once($CFG->dirroot . '/webservice/lib.php');
        $webservicemanager = new \webservice();

        $allfunctions = array_merge(
            ['auth_edwiserbridge_verify_sso_token'], // SSO functions
            [ // Selective sync functions
                'auth_edwiserbridge_get_users',
                'auth_edwiserbridge_get_courses'
            ],
            [ // Bulk purchase functions
                'core_cohort_add_cohort_members',
                'core_cohort_create_cohorts',
                'core_role_assign_roles',
                'core_role_unassign_roles',
                'core_cohort_delete_cohort_members',
                'core_cohort_get_cohorts',
                'auth_edwiserbridge_manage_cohort_enrollment',
                'auth_edwiserbridge_delete_cohort',
                'auth_edwiserbridge_manage_user_cohort_enrollment'
            ]
        );

        foreach ($allfunctions as $functionname) {
            if (!$webservicemanager->service_function_exists($functionname, $serviceid)) {
                $webservicemanager->add_external_function_to_service($functionname, $serviceid);
            }
        }
    }

    /**
     * Links an existing web service to the Edwiser Bridge plugin.
     *
     * This function adds all the missing functions to the web service, but does not add an auth user.
     *
     * @param int $serviceid The ID of the external service to link.
     * @param int $token     The token to use for the web service.
     * @return bool          Returns a success message.
     */
    public function eb_link_exitsing_service($serviceid, $token) {
        global $CFG;

        require_once($CFG->dirroot . '/webservice/lib.php');
        $webservicemanager = new \webservice();

        if ($webservicemanager->get_external_service_by_id($serviceid)) {
            $this->eb_add_default_web_service_functions($serviceid);
            $this->eb_extensions_web_service_function($serviceid);
            
            set_config('ebexistingserviceselect', $serviceid, 'auth_edwiserbridge');
            set_config("edwiser_bridge_last_created_token", $token, 'auth_edwiserbridge');
            
            return 1;
        }
        return 0;
    }

    /**
     * This function creates the token by calling Moodle's inbuilt function.
     *
     * @param int $serviceid The ID of the external service.
     * @param int $userid    The ID of the user.
     * @return string        The generated token.
     */
    public function eb_create_token($serviceid, $userid) {
        global $CFG;

        require_once("$CFG->libdir/externallib.php");
        require_once($CFG->dirroot . '/webservice/lib.php');
        
        $tokendata = [
            'tokentype' => EXTERNAL_TOKEN_PERMANENT,
            'userid' => $userid,
            'contextid' => 1,
            'purpose' => 'Edwiser Bridge Service Token'
        ];
        
        
        $token = \external_generate_token($tokendata['tokentype'], $serviceid, $userid, 1);
        set_config("edwiser_bridge_last_created_token", $token, 'auth_edwiserbridge');
        set_config('ebexistingserviceselect', $serviceid, 'auth_edwiserbridge');
        
        return $token;
    }
}
