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

use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use core_completion\progress;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/auth/edwiserbridge/lib.php');

/**
 * Trait implementing the external function auth_edwiserbridge_get_service_info
 */
trait get_service_info {

    /**
     * functionality to link existing services.
     * @param  int $serviceid service id.
     * @return array
     */
    public static function auth_edwiserbridge_get_service_info($serviceid) {

        // Validation for context is needed.
        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        
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
     * paramters defined for get service info function.
     */
    public static function auth_edwiserbridge_get_service_info_parameters() {
        return new external_function_parameters(
            [
                'service_id' => new external_value(PARAM_TEXT, get_string('web_service_id', 'auth_edwiserbridge')),
            ]
        );
    }

    /**
     * paramters which will be returned from get service info function.
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
