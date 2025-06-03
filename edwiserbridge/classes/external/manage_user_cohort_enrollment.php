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

require_once(__DIR__ . '/../../compat.php');

use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core\context\system as context_system;

defined('MOODLE_INTERNAL') || die();
global $CFG;
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
     * Returns the description of the parameters for the auth_edwiserbridge_manage_user_cohort_enrollment external function.
     *
     * This function defines the parameters that can be passed to the auth_edwiserbridge_manage_user_cohort_enrollment function,
     * including the cohort ID and an array of user data (firstname, lastname, password, username, email).
     *
     * @return external_function_parameters The description of the function parameters.
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
     * Enrolls users in a specified cohort.
     *
     * This function checks if the cohort exists, and then processes the provided user data.
     * If the user does not exist, it creates a new user account. It then adds the user to the specified cohort.
     * The function returns an array containing information about the enrollment process, including any errors that occurred.
     *
     * @param int $cohortid The ID of the cohort to enroll users in.
     * @param array $users An array of user data, including firstname, lastname, password, username, and email.
     * @return array An array containing the following keys:
     *   - error: 0 if successful, 1 if an error occurred.
     *   - error_msg: A string describing the error, if any.
     *   - users: An array of user enrollment information, including user_id, username, password, email, enrolled, cohort_id, and creation_error.
     */
    public static function auth_edwiserbridge_manage_user_cohort_enrollment($cohortid, $users) {
        global $DB, $CFG;
        
        
        // Validation for context is needed.
        $systemcontext = context_system::instance();
        self::validate_context($systemcontext);
        // Check if the user has the capability to assign users to a cohort.
        require_capability( 'moodle/cohort:assign', $systemcontext );

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
            $errormsg  = get_string('api_cohort_not_found', 'auth_edwiserbridge');
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
     * Returns the description of the method result value for the
     * auth_edwiserbridge_manage_user_cohort_enrollment function.
     *
     * @return external_function_parameters The description of the method result value.
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
