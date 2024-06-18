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
 * Update course enrollment method.
 * Functionality to update course enrollment method from WordPress.
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
 * Trait implementing the external function auth_edwiserbridge_update_course_enrollment_method
 */
trait update_course_enrollment_method {

    /**
     * Get list of active course enrolment methods for current user.
     *
     * @param int $courseid
     * @return array of course enrolment methods
     * @throws moodle_exception
     */
    public static function auth_edwiserbridge_update_course_enrollment_method($courseid) {
        global $DB, $CFG;

        // Validation for context is needed.
        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        
        $params = self::validate_parameters(
            self::auth_edwiserbridge_update_course_enrollment_method_parameters(),
            [
                'courseid'   => $courseid,
            ]
        );

        // Include manual enrollment file.
        require_once($CFG->dirroot.'/enrol/manual/locallib.php');

        $enrollplugins = enrol_get_plugins(true);
        $response = [];
        if (isset($enrollplugins['manual'])) {
            foreach ($params['courseid'] as $singlecourseid) {
                // Add enrolment instance.
                $enrolinstance = new \enrol_manual_plugin();

                $course = $DB->get_record('course', ['id' => $singlecourseid]);
                $status = $enrolinstance->add_instance($course);

                $instance = enrol_get_instances($course->id, false);
                // Get manual enrolment instance id.
                // Other plugin instances are also available.
                foreach ($instance as $instances) {
                    if ($instances->enrol == 'manual') {
                        $instanceid = $instances->id;
                    }
                }
                $enrolinstance->update_status($instance[$instanceid], ENROL_INSTANCE_ENABLED);

                $response[] = [
                    'courseid' => $singlecourseid,
                    'status' => 1,
                ];
            }
        } else {
            $response[] = [
                'courseid' => 0,
                'status' => 0,
                'message' => 'plugin_not_installed',
            ];
        }
        return $response;
    }



    /**
     * Returns description of auth_edwiserbridge_update_course_enrollment_method() parameters
     *
     * @return external_function_parameters
     */
    public static function auth_edwiserbridge_update_course_enrollment_method_parameters() {
        return new external_function_parameters(
            [
                'courseid'   => new external_multiple_structure(
                    new external_value(
                        PARAM_TEXT,
                        'Course id'
                    ),
                    'List of course id.'
                ),
            ]
        );
    }


    /**
     * Returns description of auth_edwiserbridge_update_course_enrollment_method() result value
     *
     * @return external_description
     */
    public static function auth_edwiserbridge_update_course_enrollment_method_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    'courseid' => new external_value(
                        PARAM_INT,
                        'id of course'
                    ),
                    'status' => new external_value(
                        PARAM_INT,
                        'Returns 1 if manual enrolment is enabled and 0 if disabled.'
                    ),
                    'message' => new external_value(
                        PARAM_TEXT,
                        'message',
                        VALUE_OPTIONAL
                    ),
                ]
            )
        );
    }
}
