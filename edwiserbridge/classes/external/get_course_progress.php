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

use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use completion_info;
use core_completion\progress;

/**
 * Trait implementing the external function auth_edwiserbridge_get_course_progress
 */
trait get_course_progress {


    /**
     * Functionality to get course progress.
     *
     * @param  string $userid the user id.
     * @return array of the course progress.
     */
    public static function auth_edwiserbridge_get_course_progress($userid) {
        global $DB, $CFG;

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
     * paramters defined for course progress function.
     */
    public static function auth_edwiserbridge_get_course_progress_parameters() {
        return new external_function_parameters(
            [
            'user_id' => new external_value( PARAM_TEXT, '' ),
            ]
        );
    }

    /**
     * paramters which will be returned from course progress function.
     */
    public static function auth_edwiserbridge_get_course_progress_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    'course_id'  => new external_value( PARAM_TEXT, '' ),
                    'completion' => new external_value( PARAM_INT, '' ),
                ]
            )
        );
    }
}