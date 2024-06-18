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
 * Get courses list.
 * Functionality to get courses list(with limited data) from moodle.
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
 * Trait implementing the external function auth_edwiserbridge_get_courses
 */
trait get_courses {

    /**
     * functionality to get courses in chunk.
     * @param  int $offset offset
     * @param  int $limit  limit
     * @param  string $searchstring searchstring
     * @param  int $totalcourses totalcourses
     * @return array array of courses.
     */
    public static function auth_edwiserbridge_get_courses($offset, $limit, $searchstring, $totalcourses) {
        global $DB;

        // Validation for context is needed.
        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        
        $params = self::validate_parameters(
            self::auth_edwiserbridge_get_courses_parameters(),
            ['offset' => $offset, "limit" => $limit, "search_string" => $searchstring, "total_courses" => $totalcourses]
        );

        $query = "SELECT id, fullname, category as categoryid FROM {course}";

        if (!empty($params['search_string'])) {
            $searchstring = "%" . $params['search_string'] . "%";
            $query .= " WHERE (fullname LIKE '$searchstring')";
        }

        $courses = $DB->get_records_sql($query, null, $offset, $limit);
        $coursecount = 0;
        if (!empty($params['total_courses'])) {
            $coursecount = $DB->get_record_sql("SELECT count(*) total_count FROM {course}");
            $coursecount = $coursecount->total_count;
        }

        return ["total_courses" => $coursecount, "courses" => $courses];
    }

    /**
     * paramters defined for get courses function.
     */
    public static function auth_edwiserbridge_get_courses_parameters() {
        return new external_function_parameters(
            [
                'offset'        => new external_value(
                    PARAM_INT,
                    get_string('web_service_offset', 'auth_edwiserbridge')
                ),
                'limit'         => new external_value(
                    PARAM_INT,
                    get_string('web_service_limit', 'auth_edwiserbridge')
                ),
                'search_string' => new external_value(
                    PARAM_TEXT,
                    get_string('web_service_search_string', 'auth_edwiserbridge')
                ),
                'total_courses'   => new external_value(
                    PARAM_INT,
                    get_string('web_service_total_courses', 'auth_edwiserbridge')
                ),
            ]
        );
    }

    /**
     * paramters which will be returned from get courses function.
     */
    public static function auth_edwiserbridge_get_courses_returns() {
        return new external_function_parameters(
            [
                'total_courses' => new external_value(PARAM_INT, ''),
                'courses' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'id'        => new external_value(
                                PARAM_INT,
                                get_string('web_service_courseid', 'auth_edwiserbridge')
                            ),
                            'fullname'  => new external_value(
                                PARAM_TEXT,
                                get_string('web_service_fullname', 'auth_edwiserbridge')
                            ),
                            'categoryid' => new external_value(
                                PARAM_INT,
                                get_string('web_service_categoryid', 'auth_edwiserbridge')
                            ),
                        ]
                    )
                ),
            ]
        );
    }
}
