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
 * Manage user cohort enrollment.
 * Functionality to manage user enrollments in courses.
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
 * Trait implementing the external function auth_edwiserbridge_manage_user_cohort_enrollment
 */
trait manage_user_cohort_enrollment {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function auth_edwiserbridge_manage_user_cohort_enrollment_parameters() {
        return new external_function_parameters(
            [
                'cohort_id' => new external_value(PARAM_INT, get_string('api_cohort_id', 'auth_edwiserbridge'), VALUE_REQUIRED),
                'users'     => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'firstname' => new external_value(
                                PARAM_TEXT,
                                get_string('api_firstname', 'auth_edwiserbridge'),
                                VALUE_REQUIRED
                            ),
                            'lastname' => new external_value(
                                PARAM_TEXT,
                                get_string('api_lastname', 'auth_edwiserbridge'),
                                VALUE_REQUIRED
                            ),
                            'password' => new external_value(
                                PARAM_TEXT,
                                get_string('api_password', 'auth_edwiserbridge'),
                                VALUE_REQUIRED),
                            'username' => new external_value(
                                PARAM_TEXT,
                                get_string('api_username', 'auth_edwiserbridge'),
                                VALUE_REQUIRED
                            ),
                            'email' => new external_value(
                                PARAM_TEXT,
                                get_string('api_email', 'auth_edwiserbridge'),
                                VALUE_REQUIRED
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
    public static function auth_edwiserbridge_manage_user_cohort_enrollment($cohortid, $users) {
        global $USER, $DB, $CFG;
        $error          = 0;
        $errormsg      = '';
        $usersresponse = [];

        $params = self::validate_parameters(
            self::auth_edwiserbridge_manage_user_cohort_enrollment_parameters(),
            ['cohort_id' => $cohortid, 'users' => $users]
        );

        // Check if cohort exists.
        if (!$DB->record_exists('cohort', ['id' => $params['cohort_id']])) {
            $error      = 1;
            $errormsg  = 'Cohort does not exist.';
        } else {
            foreach ($params['users'] as $user) {
                // Create user if the new user does not exist.
                $enrolled      = 0;
                $existinguser = $DB->get_record('user', ['email' => $user['email']], '*');

                // Check if email exists if yes then dont create new user.
                if (isset($existinguser->id)) {
                    $userid = $existinguser->id;
                } else {
                    // Create new user.
                    // check if the user name is available for new user.
                    $newusername = $user['username'];
                    $append = 1;

                    while ($DB->record_exists('user', ['username' => $user['username']])) {
                        $user['username'] = $newusername.$append;
                        ++$append;
                    }

                    $user['confirmed']  = 1;
                    $user['mnethostid'] = $CFG->mnet_localhost_id;
                    $userid = user_create_user($user, 1, false);

                    if (!$userid) {

                        array_push(
                            $usersresponse,
                            [
                                'user_id'        => 0,
                                'email'          => $user['email'],
                                'enrolled'       => 0,
                                'cohort_id'      => $params['cohort_id'],
                                'creation_error' => 1,
                            ]
                        );

                        // Unable to create user.
                        continue;
                    }
                }

                $cohort = [
                    'cohorttype' => ['type' => 'id', 'value' => $params['cohort_id']],
                    'usertype' => ['type' => 'id', 'value' => $userid],
                ];

                // Add User to cohort.
                if (!$DB->record_exists('cohort_members', ['cohortid' => $params['cohort_id'], 'userid' => $userid])) {
                    cohort_add_member($params['cohort_id'], $userid);
                    $enrolled = 1;
                }

                array_push(
                    $usersresponse,
                    [
                        'user_id'        => $userid,
                        'username'       => $user['username'],
                        'password'       => $user['password'],
                        'email'          => $user['email'],
                        'enrolled'       => $enrolled,
                        'cohort_id'      => $params['cohort_id'],
                        'creation_error' => 0,
                    ]
                );
            }
        }

        return [
            'error'     => $error,
            'error_msg' => $errormsg,
            'users'     => $usersresponse,
        ];
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function auth_edwiserbridge_manage_user_cohort_enrollment_returns() {

        return new external_function_parameters(
            [
                'error'     => new external_value(PARAM_INT, get_string('api_error', 'auth_edwiserbridge')),
                'error_msg' => new external_value(PARAM_TEXT, get_string('api_error_msg', 'auth_edwiserbridge')),
                'users'     => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'user_id' => new external_value(
                                PARAM_INT,
                                get_string('api_user_id', 'auth_edwiserbridge')
                            ),
                            'username' => new external_value(
                                PARAM_TEXT,
                                get_string('api_username', 'auth_edwiserbridge')
                            ),
                            'password' => new external_value(
                                PARAM_TEXT,
                                get_string('api_password', 'auth_edwiserbridge')
                            ),
                            'email' => new external_value(
                                PARAM_TEXT,
                                get_string('api_email', 'auth_edwiserbridge')
                            ),
                            'enrolled' => new external_value(
                                PARAM_INT,
                                get_string('api_enrolled', 'auth_edwiserbridge')
                            ),
                            'cohort_id' => new external_value(
                                PARAM_INT,
                                get_string('api_cohort_id', 'auth_edwiserbridge')
                            ),
                            'creation_error' => new external_value(
                                PARAM_INT,
                                get_string('api_creation_error', 'auth_edwiserbridge')
                            ),
                        ]
                    )
                ),
            ]
        );
    }
}
