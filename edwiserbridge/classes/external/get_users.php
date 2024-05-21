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
 * Get users list.
 * Functionality to get users list in chunks.
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
 * Trait implementing the external function auth_edwiserbridge_get_users
 */
trait get_users {

    /**
     * functionality to get users in chunk.
     * @param  int $offset offset
     * @param  int $limit  limit
     * @param  string $searchstring searchstring
     * @param  int $totalusers totalusers
     * @return array array of users.
     */
    public static function auth_edwiserbridge_get_users($offset, $limit, $searchstring, $totalusers) {
        global $DB;

        $params = self::validate_parameters(
            self::auth_edwiserbridge_get_users_parameters(),
            ['offset' => $offset, "limit" => $limit, "search_string" => $searchstring, "total_users" => $totalusers]
        );

        $query = "SELECT id, username, firstname, lastname, email FROM {user} WHERE
        deleted = 0 AND confirmed = 1 AND username != 'guest' ";

        if (!empty($params['search_string'])) {
            $searchstring = "%" . $params['search_string'] . "%";
            $query .= " AND (firstname LIKE '$searchstring' OR lastname LIKE '$searchstring' OR username LIKE '$searchstring')";
        }

        $users = $DB->get_records_sql($query, null, $offset, $limit);
        $usercount = 0;
        if (!empty($params['total_users'])) {
            $usercount = $DB->get_record_sql("SELECT count(*) total_count FROM {user} WHERE
            deleted = 0 AND confirmed = 1 AND username != 'guest' ");
            $usercount = $usercount->total_count;
        }

        return ["total_users" => $usercount, "users" => $users];
    }

    /**
     * paramters defined for get users function.
     */
    public static function auth_edwiserbridge_get_users_parameters() {
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
                'total_users'   => new external_value(
                    PARAM_INT,
                    get_string('web_service_total_users', 'auth_edwiserbridge')
                ),
            ]
        );
    }

    /**
     * paramters which will be returned from get users function.
     */
    public static function auth_edwiserbridge_get_users_returns() {
        return new external_function_parameters(
            [
                'total_users' => new external_value(PARAM_INT, ''),
                'users' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'id'        => new external_value(
                                PARAM_INT,
                                get_string('web_service_id', 'auth_edwiserbridge')
                            ),
                            'username'  => new external_value(
                                PARAM_TEXT,
                                get_string('web_service_username', 'auth_edwiserbridge')
                            ),
                            'firstname' => new external_value(
                                PARAM_TEXT,
                                get_string('web_service_firstname', 'auth_edwiserbridge')
                            ),
                            'lastname'  => new external_value(
                                PARAM_TEXT,
                                get_string('web_service_lastname', 'auth_edwiserbridge')
                            ),
                            'email'     => new external_value(
                                PARAM_TEXT,
                                get_string('web_service_email', 'auth_edwiserbridge')
                            ),
                        ]
                    )
                ),
            ]
        );
    }
}
