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
 * Manage cohort enrollment.
 * Functionality to manage cohort enrollment in course.
 *
 * @package    auth_edwiserbridge
 * @category   external
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_edwiserbridge\external;

require_once(__DIR__ . '/../../compat.php');

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/enrol/cohort/locallib.php');
require_once($CFG->dirroot . '/user/externallib.php');
require_once($CFG->dirroot . '/cohort/externallib.php');
require_once($CFG->dirroot . '/enrol/externallib.php');
require_once($CFG->dirroot. '/user/lib.php');
require_once($CFG->dirroot. '/cohort/lib.php');

use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core\context\system as context_system;
use core\context\user as context_user;
use core\exception\moodle_exception as moodle_exception;
use core\output\progress_trace\null_progress_trace as null_progress_trace;

/**
 * Trait implementing the external function auth_edwiserbridge_manage_cohort_enrollment
 */
trait manage_cohort_enrollment {
    /**
     * Returns the description of the method parameters for the auth_edwiserbridge_manage_cohort_enrollment function.
     *
     * @return external_function_parameters The description of the method parameters.
     */
    public static function auth_edwiserbridge_manage_cohort_enrollment_parameters() {
        return new external_function_parameters(
            [
                'cohort' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'courseid' => new external_value(
                                PARAM_INT,
                                get_string('web_service_cohort_courseid', 'auth_edwiserbridge'),
                                VALUE_REQUIRED
                            ),
                            'cohortid' => new external_value(
                                PARAM_INT,
                                get_string('web_service_course_cohortid', 'auth_edwiserbridge'),
                                VALUE_REQUIRED
                            ),
                            'unenroll' => new external_value(
                                PARAM_INT,
                                get_string('web_service_course_unenroll', 'auth_edwiserbridge'),
                                VALUE_OPTIONAL
                            ),
                        ]
                    )
                ),
            ]
        );
    }

    /**
     * Manages cohort enrollment for courses.
     * 
     * @param array $cohort Details of the cohort and course for enrollment/un-enrollment.
     * @return int|string The instance ID if successful enrollment is added, or "disabled" if cohort enrollment is disabled.
     */
    public static function auth_edwiserbridge_manage_cohort_enrollment($cohort) {
        global $USER, $DB;

        
        // Validation for context is needed.
        $systemcontext = context_system::instance();
        self::validate_context($systemcontext);

        require_capability('moodle/cohort:assign', $systemcontext );
        
        // Parameter validation.
        $params = self::validate_parameters(
            self::auth_edwiserbridge_manage_cohort_enrollment_parameters(),
            ['cohort' => $cohort]
        );

        // Context validation.
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        // Capability checking.
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }

        foreach ($params['cohort'] as $cohortdetails) {
            $cohortdetails = (object)$cohortdetails;
            if (isset($cohortdetails->cohortid) && !empty($cohortdetails->cohortid) &&
                    isset($cohortdetails->courseid) && !empty($cohortdetails->courseid)) {
                $courseid = $cohortdetails->courseid;
                $cohortid = $cohortdetails->cohortid;

                if (isset($cohortdetails->unenroll) && $cohortdetails->unenroll == 1) {
                    $enrol = enrol_get_plugin('cohort');
                    $instances = enrol_get_instances($courseid, false);
                    $instanceid = 0;
                    foreach ($instances as $instance) {
                        if ($instance->enrol === 'cohort' && $instance->customint1 == $cohortid) {
                            $enrol->delete_instance($instance);
                        }
                    }
                } else {
                    if (!enrol_is_enabled('cohort')) {
                        // Not enabled.
                        return "disabled";
                    }
                    $enrol = enrol_get_plugin('cohort');

                    $course = $DB->get_record('course', ['id' => $courseid]);

                    $instances = enrol_get_instances($courseid, false);
                    foreach ($instances as $instance) {
                        if ($instance->enrol === 'cohort' && $instance->customint1 == $cohortid) {
                            // Already enrolled.
                            return $instance->id;
                        }
                    }
                    $instance = [];
                    $instance['name'] = '';
                    $instance['status'] = ENROL_INSTANCE_ENABLED; // Enable it.
                    $instance['customint1'] = $cohortid; // Used to store the cohort id.
                    $instance['roleid'] = 5; // Default role for cohort enrol which is usually student.
                    $instance['customint2'] = 0; // Optional group id.
                    $instanceid = $enrol->add_instance($course, $instance);

                    // Sync the existing cohort members.
                    $trace = new null_progress_trace();
                    enrol_cohort_sync($trace, $course->id);
                    $trace->finished();
                }
            }
        }
        return $instanceid;
    }

    /**
     * Returns the description of the method result value.
     *
     * @return external_value The ID of the instance as an integer.
     */
    public static function auth_edwiserbridge_manage_cohort_enrollment_returns() {
        return new external_value(PARAM_INT, get_string('web_service_instance_id', 'auth_edwiserbridge'));
    }
}
