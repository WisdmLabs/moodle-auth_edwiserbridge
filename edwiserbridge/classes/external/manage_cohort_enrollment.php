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

defined('MOODLE_INTERNAL') || die();

use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/enrol/cohort/locallib.php');
require_once($CFG->dirroot . '/user/externallib.php');
require_once($CFG->dirroot . '/cohort/externallib.php');
require_once($CFG->dirroot . '/enrol/externallib.php');
require_once($CFG->dirroot. '/user/lib.php');
require_once($CFG->dirroot. '/cohort/lib.php');

/**
 * Trait implementing the external function auth_edwiserbridge_manage_cohort_enrollment
 */
trait manage_cohort_enrollment {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function auth_edwiserbridge_manage_cohort_enrollment_parameters() {
        return new external_function_parameters(
            [
                'cohort' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'courseId' => new external_value(
                                PARAM_INT,
                                'Course Id in which cohort wil be enrolled.',
                                VALUE_REQUIRED
                            ),
                            'cohortId' => new external_value(
                                PARAM_INT,
                                'Cohort Id which will be enrolled in the course.',
                                VALUE_REQUIRED
                            ),
                            'unenroll' => new external_value(
                                PARAM_INT,
                                'If true, cohort will be unenrolled from the course.',
                                VALUE_OPTIONAL
                            ),
                        ]
                    )
                ),
            ]
        );
    }

    /**
     * Function responsible for enrolling cohort in course
     * @return string welcome message
     */
    public static function auth_edwiserbridge_manage_cohort_enrollment($cohort) {
        global $USER, $DB;

        // Parameter validation.
        $params = self::validate_parameters(
            self::auth_edwiserbridge_manage_cohort_enrollment_parameters(),
            ['cohort' => $cohort]
        );

        // Context validation.
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        // Capability checking.
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }

        foreach ($params['cohort'] as $cohortdetails) {
            $cohortdetails = (object)$cohortdetails;
            if (isset($cohortdetails->cohortId) && !empty($cohortdetails->cohortId) &&
                    isset($cohortdetails->courseId) && !empty($cohortdetails->courseId)) {
                $courseid = $cohortdetails->courseId;
                $cohortid = $cohortdetails->cohortId;

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
     * Returns description of method result value
     * @return external_description
     */
    public static function auth_edwiserbridge_manage_cohort_enrollment_returns() {
        return new external_value(PARAM_INT, 'Id of the instance');
    }
}
