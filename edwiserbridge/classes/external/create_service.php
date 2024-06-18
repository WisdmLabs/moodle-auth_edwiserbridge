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

use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use core_completion\progress;
use auth_edwiserbridge;

/**
 * Trait implementing the external function auth_edwiserbridge_create_service
 */
trait create_service {

    /**
     * functionality to create new external service
     * @param  string $webservicename
     * @param  int $userid
     * @return boolean
     */
    public static function auth_edwiserbridge_create_service($webservicename, $userid) {

        // Validation for context is needed.
        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        
        $settingshandler = new auth_edwiserbridge\settings_handler();
        $response = $settingshandler->eb_create_externle_service($webservicename, $userid);
        return $response;
    }

    /**
     * Paramters defined for create service function.
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
     * paramters which will be returned from create service function.
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
