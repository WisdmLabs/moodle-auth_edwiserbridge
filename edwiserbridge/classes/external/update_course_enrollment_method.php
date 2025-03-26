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

use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core\context\course as context_course;

/**
 * Trait implementing the external function auth_edwiserbridge_update_course_enrollment_method
 */
trait update_course_enrollment_method {
    /**
     * Update the course enrollment method for the specified course ID.
     *
     * This function is used to update the enrollment method for a course, typically from an external system like WordPress.
     *
     * @param int $courseid The ID of the course to update the enrollment method for.
     * @return array An array containing the course ID and the status of the update operation.
     * @throws moodle_exception If there is an error validating the context or parameters.
     */
    public static function auth_edwiserbridge_update_course_enrollment_method($courseid) {
        global $DB, $CFG;

        
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
                // Validation for context is needed.
                $coursecontext = context_course::instance( $singlecourseid );
                self::validate_context($coursecontext);
        
                require_capability('moodle/course:enrolconfig', $coursecontext);
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
     * Returns the parameters for the auth_edwiserbridge_update_course_enrollment_method() function.
     *
     * This function defines the parameters that can be passed to the
     * auth_edwiserbridge_update_course_enrollment_method() function, which is used to update the
     * course enrollment method.
     *
     * @return external_function_parameters The parameters for the function.
     */
    public static function auth_edwiserbridge_update_course_enrollment_method_parameters() {
        return new external_function_parameters(
            [
                'courseid'   => new external_multiple_structure(
                    new external_value(
                        PARAM_INT,
                        get_string('web_service_courseid', 'auth_edwiserbridge')
                    ),
                    VALUE_OPTIONAL
                ),
            ]
        );
    }

    /**
     * Returns the description of the result value for the auth_edwiserbridge_update_course_enrollment_method() function.
     *
     * The result is a multiple structure containing a single structure with the following fields:
     *
     * - courseid: The ID of the course.
     * - status: Returns 1 if manual enrolment is enabled, and 0 if disabled.
     * - message: An optional message, if applicable.
     *
     * @return external_multiple_structure The description of the result value.
     */
    public static function auth_edwiserbridge_update_course_enrollment_method_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    'courseid' => new external_value(
                        PARAM_INT,
                        get_string('web_service_courseid', 'auth_edwiserbridge')
                    ),
                    'status' => new external_value(
                        PARAM_INT,
                        get_string('web_service_manual_enrolment', 'auth_edwiserbridge'),
                    ),
                    'message' => new external_value(
                        PARAM_TEXT,
                        get_string('web_service_api_msg', 'auth_edwiserbridge'),
                        VALUE_OPTIONAL
                    ),
                ]
            )
        );
    }
}
