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
 * Get course progress.
 * Functionality to get course progress data.
 *
 * @package    auth_edwiserbridge
 * @category   external
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_edwiserbridge\external;

require_once(__DIR__ . '/../../compat.php');

use completion_info;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_function_parameters;
use core\context\course as context_course;

/**
 * Trait implementing the external function auth_edwiserbridge_get_course_progress
 */
trait get_course_progress {

    /**
     * Functionality to get course progress data for the given user.
     *
     * @param string $userid the user id.
     * @return array an array of course progress data.
     */
    public static function auth_edwiserbridge_get_course_progress($userid) {
        global $DB;

        $params = self::validate_parameters(
            self::auth_edwiserbridge_get_course_progress_parameters(),
        ['user_id' => $userid]
        );
        
        $result = $DB->get_records_sql(
            'SELECT ctx.instanceid course, count(cmc.completionstate) as completed, count(cm.id)
            as  outoff FROM {user} u
			LEFT JOIN {role_assignments} ra ON u.id = ra.userid and u.id = ?
			JOIN {context} ctx ON ra.contextid = ctx.id
			JOIN {course_modules} cm ON ctx.instanceid = cm.course AND cm.completion > 0
			LEFT JOIN {course_modules_completion} cmc ON cm.id = cmc.coursemoduleid AND u.id = cmc.userid AND cmc.completionstate > 0
			GROUP BY ctx.instanceid, u.id
			ORDER BY u.id',
        [$params['user_id']]
        );

        $enrolledcourses  = auth_edwiserbridge_get_array_of_enrolled_courses( $params['user_id'], 1 );
        $processedcourses = $enrolledcourses;
        
        $response = [];
        
        if ( $result && ! empty( $result ) ) {
            foreach ($result as $key => $value) {
                
                // Validation for context is needed.
                $coursecontext = context_course::instance($value->course);
                self::validate_context($coursecontext);
                require_capability('report/progress:view', $coursecontext);
                
                $course     = get_course( $value->course );
                $cinfo      = new completion_info( $course );
                $iscomplete = $cinfo->is_course_complete( $params['user_id'] );
                $progress   = $iscomplete ? 100 : ( $value->completed / $value->outoff ) * 100;
                $response[] = [
                    'course_id'  => $value->course,
                    'completion' => ceil( $progress ),
                ];

                $processedcourses = auth_edwiserbridge_remove_processed_coures( $value->course, $processedcourses );
            }
        }

        if ( ! empty( $processedcourses ) ) {
            foreach ($processedcourses as $value) {
                $course     = get_course( $value );
                $cinfo      = new completion_info( $course );
                $iscomplete = $cinfo->is_course_complete( $params['user_id'] );
                $progress   = $iscomplete ? 100 : 0;
                $response[] = [
                    'course_id'  => $value,
                    'completion' => $progress,
                ];

                $processedcourses = auth_edwiserbridge_remove_processed_coures( $value, $processedcourses );
            }
        }
        return $response;
    }

    /**
     * Defines the parameters for the auth_edwiserbridge_get_course_progress function.
     *
     * @return external_function_parameters The parameters for the function.
     */
    public static function auth_edwiserbridge_get_course_progress_parameters() {
        return new external_function_parameters([
            'user_id' => new external_value(
                PARAM_TEXT,
                get_string('web_service_user_id', 'auth_edwiserbridge'),
                VALUE_REQUIRED,
                null,
                NULL_NOT_ALLOWED
            )
        ]);
    }

    /**
     * Returns the structure of the course progress data.
     *
     * This function defines the structure of the data that will be returned by the
     * auth_edwiserbridge_get_course_progress function. It specifies that the
     * returned data will be an array of objects, where each object has two
     * properties: 'course_id' (a text value) and 'completion' (an integer value).
     *
     * @return external_multiple_structure The structure of the course progress data.
     */
    public static function auth_edwiserbridge_get_course_progress_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    'course_id'  => new external_value( PARAM_TEXT, get_string('web_service_courseid', 'auth_edwiserbridge'), VALUE_REQUIRED ),
                    'completion' => new external_value( PARAM_INT, get_string('web_service_completion_percentage', 'auth_edwiserbridge'), VALUE_REQUIRED ),
                ]
            )
        );
    }
}
