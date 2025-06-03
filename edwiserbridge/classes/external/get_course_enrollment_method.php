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
 * Get course enrollment method.
 * Functionality to get course enrollment method.
 *
 * @package    auth_edwiserbridge
 * @category   external
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_edwiserbridge\external;

require_once(__DIR__ . '/../../compat.php');

use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_function_parameters;
use core\context\system as context_system;
use core\exception\moodle_exception as moodle_exception;
use Exception;

/**
 * Trait implementing the external function auth_edwiserbridge_get_course_enrollment_method
 */
trait get_course_enrollment_method {
    /**
     * Returns the description of the parameters for the auth_edwiserbridge_get_course_enrollment_method() external function.
     *
     * @return external_function_parameters The description of the function parameters.
     */
    public static function auth_edwiserbridge_get_course_enrollment_method_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * Get list of active course enrolment methods for current user.
     *
     * This function retrieves the list of active course enrolment methods for the current user. It first validates the system context and checks if the Moodle manual enrolment plugin is enabled. If the plugin is disabled, it throws a moodle_exception. Otherwise, it retrieves the list of active manual enrolment instances from the database and returns an array containing the course IDs and a flag indicating if manual enrolment is enabled for each course.
     *
     * @return array An array of course enrolment methods, where each element is an associative array with the following keys:
     *               - courseid (int): The ID of the course.
     *               - enabled (int): 1 if manual enrolment is enabled for the course, 0 otherwise.
     * @throws moodle_exception If the Moodle manual enrolment plugin is disabled.
     */
    public static function auth_edwiserbridge_get_course_enrollment_method() {
        global $DB, $CFG;

        
        // Validation for context is needed.
        $systemcontext = context_system::instance();
        self::validate_context($systemcontext);
        require_capability('moodle/site:config', $systemcontext);
        
        // Check if Moodle manual enrollment plugin is disabled.
        $enrolplugins = explode(',', $CFG->enrol_plugins_enabled);
        if (! in_array('manual', $enrolplugins)) {
            throw new moodle_exception('plugininactive', 'auth_edwiserbridge');
        }

        $response = [];
        $result = $DB->get_records('enrol', ['status' => 0, 'enrol' => 'manual'], 'sortorder,id');

        foreach ($result as $instance) {

            $response[] = [
                'courseid' => $instance->courseid,
                'enabled'  => 1,
            ];
        }
        if ( empty( $result ) ) {
            throw new Exception('Error');
        }

        return $response;
    }

    /**
     * Returns the external structure for course enrollment methods.
     *
     * @return external_multiple_structure Array of external_single_structure, each containing:
     *                                      - courseid (int): ID of the course.
     *                                      - enabled (int): Returns 1 if manual enrolment is enabled, 0 if disabled.
     */
    public static function auth_edwiserbridge_get_course_enrollment_method_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    'courseid' => new external_value(
                        PARAM_INT,
                        get_string('web_service_courseid', 'auth_edwiserbridge'),
                    ),
                    'enabled'  => new external_value(
                        PARAM_INT,
                        get_string('web_service_manual_enrolment', 'auth_edwiserbridge'),
                    ),
                ]
            )
        );
    }
}
