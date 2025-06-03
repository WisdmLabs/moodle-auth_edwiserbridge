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

require_once(__DIR__ . '/../../compat.php');

use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core\context\system as context_system;
/**
 * Trait implementing the external function auth_edwiserbridge_get_courses
 */
trait get_courses {

    /**
     * Functionality to get courses in chunk.
     *
     * @param int $offset Offset for the course list.
     * @param int $limit Limit the number of courses to return.
     * @param string $searchstring Search string to filter the courses.
     * @param int $totalcourses Flag to indicate if the total course count should be returned.
     * @return array Array of courses.
     */
    public static function auth_edwiserbridge_get_courses($offset, $limit, $searchstring, $totalcourses) {
        global $DB;

        // Validation for context is needed.
        $systemcontext = context_system::instance();
        self::validate_context($systemcontext);

        require_capability('moodle/course:view', $systemcontext);
        
        $params = self::validate_parameters(
            self::auth_edwiserbridge_get_courses_parameters(),
            ['offset' => $offset, "limit" => $limit, "search_string" => $searchstring, "total_courses" => $totalcourses]
        );

        $query = "SELECT id, fullname, category as categoryid FROM {course}";
        $count_query = "SELECT count(*) total_count FROM {course}";
        $paramsql = [];

        if (!empty($params['search_string'])) {
            $query .= " WHERE (fullname LIKE :searchstring)";
            $paramsql['searchstring'] = '%' . $params['search_string'] . '%';
        }

        $courses = $DB->get_records_sql($query, $paramsql, $offset, $limit);
        $coursecount = 0;
        if (!empty($params['total_courses'])) {
            $coursecount = $DB->get_record_sql($count_query);
            $coursecount = $coursecount->total_count;
        }

        return ["total_courses" => $coursecount, "courses" => $courses];
    }

    /**
     * Defines the parameters for the auth_edwiserbridge_get_courses external function.
     *
     * This function returns an external_function_parameters object that defines the
     * expected parameters for the auth_edwiserbridge_get_courses function. The
     * parameters include:
     *
     * - offset: The offset for the course list.
     * - limit: The maximum number of courses to return.
     * - search_string: A search string to filter the courses.
     * - total_courses: A flag to indicate if the total course count should be returned.
     *
     * @return external_function_parameters The parameters for the
     *         auth_edwiserbridge_get_courses function.
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
     * Defines the return structure for the auth_edwiserbridge_get_courses external function.
     *
     * This function returns an external_function_parameters object that defines the
     * expected return structure for the auth_edwiserbridge_get_courses function. The
     * return structure includes:
     *
     * - total_courses: The total number of courses.
     * - courses: An array of course information, including the course ID, full name, and category ID.
     *
     * @return external_function_parameters The return structure for the
     *         auth_edwiserbridge_get_courses function.
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
