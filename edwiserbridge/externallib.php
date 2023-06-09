<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * External course participation api.
 *
 * This api is mostly read only, the actual enrol and unenrol
 * support is in each enrol plugin.
 *
 * @category   external
 *
 * @copyright  2011 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/enrol/cohort/locallib.php');
require_once($CFG->dirroot . '/user/externallib.php');
require_once($CFG->dirroot . '/cohort/externallib.php');
require_once($CFG->dirroot . '/enrol/externallib.php');
require_once($CFG->dirroot. '/user/lib.php');
require_once($CFG->dirroot. '/cohort/lib.php');

/**
 * Manual enrolment external functions.
 *
 * @category   external
 *
 * @copyright  2011 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @since Moodle 2.2
 */
class auth_sso_token_verify_external extends external_api
{
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     *
     * @since SSO 1.2.1
     */
    public static function wdm_sso_verify_token_parameters()
    {
        return new external_function_parameters(
            array(
                'token' => new external_value(PARAM_TEXT, 'Token to verify'),
            )
        );
    }

    /**
     * Returns description of method parameters.
     *
     * @return bool
     *
     * @since SSO 1.2.1
     */
    public static function wdm_sso_verify_token($token)
    {
        $params = self::validate_parameters(
            self::wdm_sso_verify_token_parameters(),
            array('token' => $token)
        );
        $responce = array('success' => false,'msg' => 'Invalid token provided,please check token and try again');
        $mdl_key = get_config('auth_edwiserbridge', 'sharedsecret');
        if ($params['token'] == $mdl_key) {
            $responce['success'] = true;
            $responce['msg'] = 'Token verified successfully';
        }

        return $responce;
    }

    /**
     * Returns description of method parameters.
     *
     * @return bool
     *
     * @since SSO 1.2.1
     */
    public static function wdm_sso_verify_token_returns()
    {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'true if the token matches otherwise false'),
                'msg'     => new external_value(PARAM_RAW, 'Sucess faile message'),
                )
        );
    }
}

class auth_wdmgroupregistration_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function eb_manage_cohort_enrollment_parameters() {
        return new external_function_parameters(
            array(
                'cohort' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'courseId' => new external_value(PARAM_INT, 'Course Id in which cohort wil be enrolled.', VALUE_REQUIRED),
                            'cohortId' => new external_value(PARAM_INT, 'Cohort Id which will be enrolled in the course.', VALUE_REQUIRED),
                            'unenroll' => new external_value(PARAM_INT, 'If true, cohort will be unenrolled from the course.', VALUE_OPTIONAL),
                        )
                    )
                )
            )
        );
    }

    /**
     * Function responsible for enrolling cohort in course
     * @return string welcome message
     */
    public static function eb_manage_cohort_enrollment($cohort) {
        global $USER, $DB;

        //Parameter validation
        //REQUIRED

        $params = self::validate_parameters(
            self::eb_manage_cohort_enrollment_parameters(),
            array('cohort' => $cohort)
        );

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        //Capability checking
        //OPTIONAL but in most web service it should present
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }

        foreach ($params['cohort'] as $cohortDetails) {
            $cohortDetails = (object)$cohortDetails;
            if (isset($cohortDetails->cohortId) && !empty($cohortDetails->cohortId) && isset($cohortDetails->courseId) && !empty($cohortDetails->courseId)) {
                $courseid = $cohortDetails->courseId;
                $cohortid = $cohortDetails->cohortId;

                if(isset($cohortDetails->unenroll) && $cohortDetails->unenroll == 1){
                    $enrol = enrol_get_plugin('cohort');
                    $instances = enrol_get_instances($courseid, false);
                    $instanceId = 0;
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

                    $course = $DB->get_record('course', array('id' => $courseid));

                    $instances = enrol_get_instances($courseid, false);
                    foreach ($instances as $instance) {
                        if ($instance->enrol === 'cohort' && $instance->customint1 == $cohortid) {
                            // Already enrolled.
                            return $instance->id;
                        }
                    }
                    $instance = array();
                    $instance['name'] = '';
                    $instance['status'] = ENROL_INSTANCE_ENABLED; // Enable it.
                    $instance['customint1'] = $cohortid; // Used to store the cohort id.
                    $instance['roleid'] = 5; // Default role for cohort enrol which is usually student.
                    $instance['customint2'] = 0; // Optional group id.
                    $instanceId = $enrol->add_instance($course, $instance);

                    // Sync the existing cohort members.
                    $trace = new null_progress_trace();
                    enrol_cohort_sync($trace, $course->id);
                    $trace->finished();
                }
            }
        }
        return $instanceId;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function eb_manage_cohort_enrollment_returns() {
        return new external_value(PARAM_INT, 'Id of the instance');
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function eb_delete_cohort_parameters() {
        return new external_function_parameters(
            array(
                'cohort' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'cohortId' => new external_value(PARAM_INT, 'Cohort Id which will be deleted in Moodle', VALUE_REQUIRED)
                        )
                    )
                )
            )
        );
    }

    /**
     * Function responsible for enrolling cohort in course
     * @return string welcome message
     */
    public static function eb_delete_cohort($cohort) {
        global $USER, $DB;

        //Parameter validation
        //REQUIRED
        $params = self::validate_parameters(
            self::eb_delete_cohort_parameters(),
            array('cohort' => $cohort)
        );

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        //Capability checking
        //OPTIONAL but in most web service it should present
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }

        $response = array(
            "status" => 1
        );

        foreach ($params["cohort"] as $cohortDetails) {
            try {
                $cohort = $DB->get_record('cohort', array('id' => $cohortDetails["cohortId"]), '*', MUST_EXIST);
                if (isset($cohort->id)) {
                    $context = context::instance_by_id($cohort->contextid, MUST_EXIST);
                    cohort_delete_cohort($cohort);
                } else {
                    throw new Exception('Error');
                }
            } catch (Exception $e) {
                $response['status'] = 0;
            }
        }
        return $response;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function eb_delete_cohort_returns() {
        return new external_single_structure(
            array(
                'status'  => new external_value(PARAM_TEXT, 'This will return 1 if successful connection and 0 on failure')
            )
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function eb_manage_user_cohort_enrollment_parameters() {
        return new external_function_parameters(
            array(
                'cohort_id' => new external_value(PARAM_INT, get_string('api_cohort_id', 'auth_edwiserbridge'), VALUE_REQUIRED),
                'users'     => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'firstname' => new external_value(PARAM_TEXT, get_string('api_firstname', 'auth_edwiserbridge'), VALUE_REQUIRED),
                            'lastname'  => new external_value(PARAM_TEXT, get_string('api_lastname', 'auth_edwiserbridge'), VALUE_REQUIRED),
                            'password'  => new external_value(PARAM_TEXT, get_string('api_password', 'auth_edwiserbridge'), VALUE_REQUIRED),
                            'username'  => new external_value(PARAM_TEXT, get_string('api_username', 'auth_edwiserbridge'), VALUE_REQUIRED),
                            'email'     => new external_value(PARAM_TEXT, get_string('api_email', 'auth_edwiserbridge'), VALUE_REQUIRED)
                        )
                    )
                )
            )
        );
    }

    /**
     * Function responsible for enrolling cohort in course
     * @return string welcome message
     */
    public static function eb_manage_user_cohort_enrollment($cohort_id, $users) {
        global $USER, $DB, $CFG;
        $error          = 0;
        $error_msg      = '';
        $users_response = array();

        $params = self::validate_parameters(
            self::eb_manage_user_cohort_enrollment_parameters(),
            array('cohort_id' => $cohort_id, 'users' => $users)
        );

        // Check 
        if (!$DB->record_exists('cohort', array('id' => $params['cohort_id']))) {
            $error      = 1;
            $error_msg  = 'Cohort_does_not_exist';
        } else {
            foreach ($params['users'] as $user) {
                // Create user if the new user
                
                $enrolled      = 0;
                $existing_user = $DB->get_record('user', array('email' => $user['email']), '*');

                // check if email exists if yes then dont create new user
                if (isset($existing_user->id)) {
                    $user_id = $existing_user->id;
                } else {
                    // create new user
                    // check if the user name is available for new user.
                    $o_user_name = $user['username'];
                    $append = 1;

                    while ($DB->record_exists('user', array('username' => $user['username']))) {
                        $user['username'] = $o_user_name.$append;
                        ++$append;
                    }

                    $user['confirmed']  = 1;
                    $user['mnethostid'] = $CFG->mnet_localhost_id;
                    $user_id = user_create_user($user, 1, false);

                    if (!$user_id) {

                        array_push(
                            $users_response,
                            array(
                                'user_id'        => 0,
                                'email'          => $user['email'],
                                'enrolled'       => 0,
                                'cohort_id'      => $params['cohort_id'],
                                'creation_error' => 1
                            )
                        );

                        // Unable to create user.
                        continue;
                    }
                }

                $cohort = array(
                    'cohorttype' => array('type' => 'id', 'value' => $params['cohort_id']),
                    'usertype' => array('type' => 'id', 'value' => $user_id)
                );

                $flag = 'aaa';

                // Add User to cohort.
                if (!$DB->record_exists('cohort_members', array('cohortid' => $params['cohort_id'], 'userid' => $user_id))) {
                    $flag = 'bbbb';

                    cohort_add_member($params['cohort_id'], $user_id);
                    $enrolled = 1;
                }

                array_push(
                    $users_response,
                    array(
                        'user_id'        => $user_id,
                        'username'       => $user['username'],
                        'password'       => $user['password'],
                        'email'          => $user['email'],
                        'enrolled'       => $enrolled,
                        'cohort_id'      => $params['cohort_id'],
                        'creation_error' => 0
                    )
                );
            }  
        }

        return array(
            'error'     => $error,
            'error_msg' => $error_msg,
            'users'     => $users_response
        );
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function eb_manage_user_cohort_enrollment_returns() {

        return new external_function_parameters(
            array(
                'error'     => new external_value(PARAM_INT, get_string('api_error', 'auth_edwiserbridge')),
                'error_msg' => new external_value(PARAM_TEXT, get_string('api_error_msg', 'auth_edwiserbridge')),
                'users'     => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'user_id'        => new external_value(PARAM_INT, get_string('api_user_id', 'auth_edwiserbridge')),
                            'username'       => new external_value(PARAM_TEXT, get_string('api_username', 'auth_edwiserbridge')),
                            'password'       => new external_value(PARAM_TEXT, get_string('api_password', 'auth_edwiserbridge')),
                            'email'          => new external_value(PARAM_TEXT, get_string('api_email', 'auth_edwiserbridge')),
                            'enrolled'       => new external_value(PARAM_INT, get_string('api_enrolled', 'auth_edwiserbridge')),
                            'cohort_id'      => new external_value(PARAM_INT, get_string('api_cohort_id', 'auth_edwiserbridge')),
                            'creation_error' => new external_value(PARAM_INT, get_string('api_creation_error', 'auth_edwiserbridge'))
                        )
                    )
                )
            )
        );
    }
}
