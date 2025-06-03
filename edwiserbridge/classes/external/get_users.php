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

require_once(__DIR__ . '/../../compat.php');

use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core\context\system as context_system;

/**
 * Trait implementing the external function auth_edwiserbridge_get_users
 */
trait get_users {

    /**
     * Functionality to get users list in chunks.
     *
     * @param int $offset Offset for the user list.
     * @param int $limit Limit for the number of users to retrieve.
     * @param string $searchstring Search string to filter the users.
     * @param int $totalusers Flag to retrieve the total number of users.
     * @return array Array of users.
     */
    public static function auth_edwiserbridge_get_users($offset, $limit, $searchstring, $totalusers) {
        global $DB;
        
        // Validation for context is needed.
        $systemcontext = context_system::instance();
        self::validate_context($systemcontext);
        require_capability('moodle/user:viewalldetails', $systemcontext);
        
        $params = self::validate_parameters(
            self::auth_edwiserbridge_get_users_parameters(),
            ['offset' => $offset, "limit" => $limit, "search_string" => $searchstring, "total_users" => $totalusers]
        );

        $query       = "SELECT id, username, firstname, lastname, email FROM {user} WHERE
        deleted = 0 AND confirmed = 1 AND username != 'guest'";
        $count_query = "SELECT count(*) total_count FROM {user} WHERE
            deleted = 0 AND confirmed = 1 AND username != 'guest'";
        $paramsql    = [];

        if (!empty($params['search_string'])) {
            $query .= " AND (firstname LIKE :searchstring1 OR lastname LIKE :searchstring2 OR username LIKE :searchstring3)";
            $searchstring = "%" . $params['search_string'] . "%";
            $paramsql['searchstring1'] = $paramsql['searchstring2'] = $paramsql['searchstring3'] = $searchstring;
        }

        $users = $DB->get_records_sql($query, $paramsql, $offset, $limit);
        $usercount = 0;
        if (!empty($params['total_users'])) {
            $usercount = $DB->get_record_sql($count_query);
            $usercount = $usercount->total_count;
        }

        return ["total_users" => $usercount, "users" => $users];
    }

    /**
     * Defines the parameters for the auth_edwiserbridge_get_users external function.
     *
     * This function returns an external_function_parameters object that defines the
     * parameters for the auth_edwiserbridge_get_users function. The parameters
     * include:
     *
     * - offset: The offset for the user list.
     * - limit: The limit for the number of users to retrieve.
     * - search_string: The search string to filter the users.
     * - total_users: A flag to retrieve the total number of users.
     *
     * @return external_function_parameters The parameters for the
     *         auth_edwiserbridge_get_users function.
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
     * Defines the return parameters for the auth_edwiserbridge_get_users function.
     *
     * This function returns an array with two keys:
     * - 'total_users': an integer representing the total number of users
     * - 'users': an array of user objects, each with the following properties:
     *   - 'id': the user's ID
     *   - 'username': the user's username
     *   - 'firstname': the user's first name
     *   - 'lastname': the user's last name
     *   - 'email': the user's email address
     * 
     * @return external_function_parameters The return structure for the auth_edwiserbridge_get_users function.
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
