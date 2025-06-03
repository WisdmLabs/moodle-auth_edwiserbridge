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
 * Get service info.
 * Functionality to get added webservice functions for a web service.
 *
 * @package    auth_edwiserbridge
 * @category   external
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_edwiserbridge\external;

require_once(__DIR__ . '/../../compat.php');

use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use core\context\system as context_system;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/auth/edwiserbridge/lib.php');

/**
 * Trait implementing the external function auth_edwiserbridge_get_service_info
 */
trait get_service_info {

    /**
     * Functionality to link existing services.
     *
     * @param int $serviceid Service ID.
     * @return array Response array with status and message.
     */
    public static function auth_edwiserbridge_get_service_info($serviceid) {
        
        // Validation for context is needed.
        $systemcontext = context_system::instance();
        self::validate_context($systemcontext);
        
        require_capability('moodle/webservice:managealltokens', $systemcontext);
        
        $response           = [];
        $response['status'] = 1;
        $response['msg']    = '';

        $count = auth_edwiserbridge_get_service_list($serviceid);
        if ($count) {
            $response['status'] = 0;
            $response['msg'] = $count . get_string('eb_service_info_error', 'auth_edwiserbridge');
            return $response;
        }
        return $response;
    }

    /**
     * Defines the parameters for the auth_edwiserbridge_get_service_info external function.
     *
     * @return external_function_parameters The parameters for the external function.
     */
    public static function auth_edwiserbridge_get_service_info_parameters() {
        return new external_function_parameters([
            'service_id' => new external_value(
                PARAM_INT,
                get_string('web_service_id', 'auth_edwiserbridge'),
                VALUE_REQUIRED,
                null,
                NULL_NOT_ALLOWED
            )
        ]);
    }
    /**
     * Returns the parameters that will be returned from the get_service_info function.
     *
     * @return external_single_structure The structure of the returned parameters.
     */
    public static function auth_edwiserbridge_get_service_info_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_INT, get_string('web_service_creation_status', 'auth_edwiserbridge')),
                'msg'    => new external_value(PARAM_TEXT, get_string('web_service_creation_msg', 'auth_edwiserbridge')),
            ]
        );
    }
}
