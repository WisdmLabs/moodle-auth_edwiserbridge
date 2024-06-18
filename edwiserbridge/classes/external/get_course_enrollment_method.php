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

use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use core_completion\progress;

/**
 * Trait implementing the external function auth_edwiserbridge_get_course_enrollment_method
 */
trait get_course_enrollment_method {

    /**
     * Returns description of auth_edwiserbridge_get_course_enrollment_method() parameters
     *
     * @return external_function_parameters
     */
    public static function auth_edwiserbridge_get_course_enrollment_method_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * Get list of active course enrolment methods for current user.
     *
     * @param int $courseid
     * @return array of course enrolment methods
     * @throws moodle_exception
     */
    public static function auth_edwiserbridge_get_course_enrollment_method() {
        global $DB, $CFG;

        // Validation for context is needed.
        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        
        // Check if Moodle manual enrollment plugin is disabled.
        $enrolplugins = explode(',', $CFG->enrol_plugins_enabled);
        if (! in_array('manual', $enrolplugins)) {
            throw new \moodle_exception('plugininactive');
        }

        $response = [];
        $result = $DB->get_records('enrol', ['status' => 0, 'enrol' => 'manual'], 'sortorder,id');

        foreach ($result as $instance) {
            $response[] = [
                'courseid' => $instance->courseid,
                'enabled'  => 1,
            ];
        }

        return $response;
    }

    /**
     * Returns description of auth_edwiserbridge_get_course_enrollment_method() result value
     *
     * @return external_description
     */
    public static function auth_edwiserbridge_get_course_enrollment_method_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    'courseid' => new external_value(
                        PARAM_INT,
                        'id of course'
                    ),
                    'enabled'  => new external_value(
                        PARAM_INT,
                        'Returns 1 if manual enrolment is enabled and 0 if disabled.'
                    ),
                ]
            )
        );
    }
}
