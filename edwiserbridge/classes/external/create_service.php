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
 * Create external service.
 * Functionality to create new external service.
 *
 * @package    auth_edwiserbridge
 * @category   external
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_edwiserbridge\external;

use core_external\external_single_structure;
use core_external\external_value;
use auth_edwiserbridge;
use core\context\system as context_system;
use core_external\external_function_parameters;

/**
 * Trait implementing the external function auth_edwiserbridge_create_service
 */
trait create_service {

    /**
     * Functionality to create a new external service.
     *
     * @param string $webservicename The name of the web service to create.
     * @param int $userid The ID of the user to associate with the web service.
     * @return array An array containing the details of the created web service.
     */
    public static function auth_edwiserbridge_create_service($webservicename, $userid) {
        
        // Validation for context is needed.
        $systemcontext = context_system::instance();
        self::validate_context($systemcontext);
        require_capability('moodle/webservice:createtoken', $systemcontext);
        
        $settingshandler = new auth_edwiserbridge\local\settings_handler();
        $response = $settingshandler->eb_create_externle_service($webservicename, $userid);
        return $response;
    }

    /**
     * Defines the parameters for the auth_edwiserbridge_create_service external function.
     *
     * This function returns an external_function_parameters object that defines the
     * parameters required for the auth_edwiserbridge_create_service function.
     *
     * @return external_function_parameters The parameters for the
     *         auth_edwiserbridge_create_service function.
     */
    public static function auth_edwiserbridge_create_service_parameters() {
        return new external_function_parameters(
            [
                'web_service_name' => new external_value(
                    PARAM_TEXT,
                    get_string('web_service_name', 'auth_edwiserbridge')
                ),
                'user_id' => new external_value(
                    PARAM_TEXT,
                    get_string('web_service_auth_user', 'auth_edwiserbridge')
                ),
            ]
        );
    }

    /**
     * Defines the structure of the return value for the auth_edwiserbridge_create_service external function.
     *
     * This function returns an external_single_structure object that defines the
     * structure of the array that will be returned by the auth_edwiserbridge_create_service function.
     *
     * @return external_single_structure The structure of the return value for the
     *         auth_edwiserbridge_create_service function.
     */
    public static function auth_edwiserbridge_create_service_returns() {
        return new external_single_structure(
            [
                'token' => new external_value(
                    PARAM_TEXT,
                    get_string('web_service_token', 'auth_edwiserbridge')
                ),
                'site_url' => new external_value(
                    PARAM_TEXT,
                    get_string('moodle_url', 'auth_edwiserbridge')
                ),
                'service_id' => new external_value(
                    PARAM_INT,
                    get_string('web_service_id', 'auth_edwiserbridge')
                ),
                'status' => new external_value(
                    PARAM_INT,
                    get_string('web_service_creation_status', 'auth_edwiserbridge')
                ),
                'msg' => new external_value(
                    PARAM_TEXT,
                    get_string('web_service_creation_msg', 'auth_edwiserbridge')
                ),
            ]
        );
    }
}
