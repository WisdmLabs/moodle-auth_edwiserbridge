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
 * Link service.
 * Functionality to link existing services.
 *
 * @package    auth_edwiserbridge
 * @category   external
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_edwiserbridge\external;

require_once(__DIR__ . '/../../compat.php');

use auth_edwiserbridge;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use core\context\system as context_system;

/**
 * Trait implementing the external function auth_edwiserbridge_link_service
 */
trait link_service {

    /**
     * Functionality to link existing services.
     *
     * @param string $serviceid The ID of the service to link.
     * @param int $token The token associated with the service.
     * @return array An array containing the status and message of the linking operation.
     */
    public static function auth_edwiserbridge_link_service($serviceid, $token) {
        
        // Validation for context is needed.
        $systemcontext = context_system::instance();
        self::validate_context($systemcontext);
        require_capability('moodle/webservice:managealltokens', $systemcontext);
        
        $response           = [];
        $response['status'] = 0;
        $response['msg']    = get_string('eb_link_err', 'auth_edwiserbridge');

        $settingshandler = new auth_edwiserbridge\local\settings_handler();
        $result           = $settingshandler->eb_link_exitsing_service($serviceid, $token);
        if ($result) {
            $response['status'] = 1;
            $response['msg'] = get_string('eb_link_success', 'auth_edwiserbridge');
            return $response;
        }
        return $response;
    }

    /**
     * Defines the parameters for the auth_edwiserbridge_link_service external function.
     *
     * @return external_function_parameters The parameters for the link service function.
     */
    public static function auth_edwiserbridge_link_service_parameters() {
        return new external_function_parameters(
            [
                'service_id' => new external_value(PARAM_TEXT, get_string('web_service_id', 'auth_edwiserbridge')),
                'token'      => new external_value(PARAM_TEXT, get_string('web_service_token', 'auth_edwiserbridge')),
            ]
        );
    }

    /**
     * Defines the return structure for the auth_edwiserbridge_link_service external function.
     *
     * @return external_single_structure The return structure for the link service function.
     */
    public static function auth_edwiserbridge_link_service_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_INT, get_string('web_service_creation_status', 'auth_edwiserbridge'), VALUE_REQUIRED),
                'msg'    => new external_value(PARAM_TEXT, get_string('web_service_creation_msg', 'auth_edwiserbridge'), VALUE_REQUIRED),
            ]
        );
    }
}
