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
 * Event observer.
 * Observer file used as the callback for all the events.
 *
 * @package    auth_edwiserbridge
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_edwiserbridge;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/auth/edwiserbridge/lib.php');
require_once($CFG->dirroot . '/user/lib.php');

/**
 * Handles callbacks for all in built Moodle events.
 *
 * This class provides methods to handle various Moodle events, such as user enrollment, user creation, user update, and course creation/deletion. It communicates with a WordPress site using the Edwiser Bridge plugin to synchronize user and course data between the two platforms.
 *
 * @package     auth_edwiserbridge
 * @copyright   2021 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Functionality to handle user enrollment event.
     *
     * This method is called when a user is enrolled in a course. It collects the relevant user data and sends it to the connected WordPress site using the Edwiser Bridge plugin to synchronize the enrollment.
     *
     * @param core\event\user_enrolment_created $event The event object containing information about the user enrollment.
     */
    public static function user_enrolment_created(\core\event\user_enrolment_created $event) {
        $userdata = user_get_users_by_id([$event->relateduserid]);

        $requestdata = [
            'action'     => 'course_enrollment',
            'user_id'    => $event->relateduserid,
            'course_id'  => $event->courseid,
            'user_name'  => $userdata[$event->relateduserid]->username,
            'first_name' => $userdata[$event->relateduserid]->firstname,
            'last_name'  => $userdata[$event->relateduserid]->lastname,
            'email'      => $userdata[$event->relateduserid]->email,
        ];

        if (auth_edwiserbridge_check_if_request_is_from_wp()) {
            return;
        }

        $apihandler = auth_edwiserbridge_api_handler_instance();
        $eb_connection_settings = get_config('auth_edwiserbridge', 'eb_connection_settings');
        $eb_sync_settings = get_config('auth_edwiserbridge', 'eb_synch_settings');
        if (!empty($eb_connection_settings)) {
            $sites = json_decode($eb_connection_settings, true);
            $synchconditions = json_decode($eb_sync_settings, true);
            foreach ($sites as $value) {
                if ($synchconditions[$value['wp_name']]['course_enrollment'] && $value['wp_token']) {
                    // Adding Token for verification in WP from Moodle.
                    $requestdata['secret_key'] = $value['wp_token'];

                    $apihandler->connect_to_wp_with_args($value["wp_url"], $requestdata);
                }
            }
        }
    }

    /**
     * Functionality to handle user un enrollment event.
     *
     * This method is called when a user is unenrolled from a course. It collects the relevant user data and sends it to the connected WordPress site using the Edwiser Bridge plugin to synchronize the unenrollment.
     *
     * @param core\event\user_enrolment_deleted $event The event object containing information about the user unenrollment.
     */
    public static function user_enrolment_deleted(\core\event\user_enrolment_deleted $event) {
        $userdata = user_get_users_by_id([$event->relateduserid]);
        $requestdata = [
            'action'     => 'course_un_enrollment',
            'user_id'    => $event->relateduserid,
            'course_id'  => $event->courseid,
            'user_name'  => $userdata[$event->relateduserid]->username,
            'first_name' => $userdata[$event->relateduserid]->firstname,
            'last_name'  => $userdata[$event->relateduserid]->lastname,
            'email'      => $userdata[$event->relateduserid]->email,
        ];

        // Checks if the request is from the wordpress site or from te Moodle site itself.
        if (auth_edwiserbridge_check_if_request_is_from_wp()) {
            return;
        }

        $apihandler = auth_edwiserbridge_api_handler_instance();
        $eb_connection_settings = get_config('auth_edwiserbridge', 'eb_connection_settings');
        $eb_sync_settings = get_config('auth_edwiserbridge', 'eb_synch_settings');
        
        if (!empty($eb_connection_settings)) {
            $sites = json_decode($eb_connection_settings, true);
            $synchconditions = json_decode($eb_sync_settings, true);

            foreach ($sites as $value) {
                if ($synchconditions[$value['wp_name']]['course_un_enrollment'] && $value['wp_token']) {
                    // Adding Token for verification in WP from Moodle.
                    $requestdata['secret_key'] = $value['wp_token'];
                    $apihandler->connect_to_wp_with_args($value["wp_url"], $requestdata);
                }
            }
        }
    }

    /**
     * Functionality to handle user creation event.
     *
     * This method is called when a new user is created in the Moodle system. It collects the relevant user data and sends it to the connected WordPress site using the Edwiser Bridge plugin to synchronize the user creation.
     *
     * @param core\event\user_created $event The event object containing information about the newly created user.
     */
    public static function user_created(\core\event\user_created $event) {
        global $CFG;
        $userdata = user_get_users_by_id([$event->relateduserid]);

        // User password should be encrypted. Using Openssl for it.
        // We will use token as the key as it is present on both sites.
        // Open SSL encryption initialization.
        $encmethod = 'AES-128-CTR';

        $apihandler = auth_edwiserbridge_api_handler_instance();
        $eb_connection_settings = get_config('auth_edwiserbridge', 'eb_connection_settings');
        $eb_sync_settings = get_config('auth_edwiserbridge', 'eb_synch_settings');
        
        if (!empty($eb_connection_settings)) {
            $sites = json_decode($eb_connection_settings, true);
            $synchconditions = json_decode($eb_sync_settings, true);

            foreach ($sites as $value) {
                if ($synchconditions[$value["wp_name"]]["user_creation"] && $value['wp_token']) {
                    $password    = '';
                    $enciv       = '';
                    $newpassword = optional_param('newpassword', '', PARAM_TEXT);

                    // If new password in not empty.
                    if ($newpassword && !empty($newpassword)) {
                        $enckey   = openssl_digest($value["wp_token"], 'SHA256', true);
                        $enciv    = substr(hash('sha256', $value["wp_token"]), 0, 16);
                        $password = openssl_encrypt($newpassword, $encmethod, $enckey, 0, $enciv);
                    }

                    require_once("$CFG->dirroot/user/profile/lib.php");

                    $requestdata = [
                        'action' => 'user_creation',
                        'user_id'     => $event->relateduserid,
                        'user_name'   => $userdata[$event->relateduserid]->username,
                        'first_name'  => $userdata[$event->relateduserid]->firstname,
                        'last_name'   => $userdata[$event->relateduserid]->lastname,
                        'email'       => $userdata[$event->relateduserid]->email,
                        'password'    => $password,
                        'enc_iv'      => $enciv,
                        'secret_key' => $value['wp_token'], // Adding Token for verification in WP from Moodle.
                        'custom_fields' => json_encode(profile_user_record($event->relateduserid, false)), // Custom fields data.
                    ];

                    $apihandler->connect_to_wp_with_args($value["wp_url"], $requestdata);
                }
            }
        }
    }

    /**
     * Functionality to handle user update event.
     *
     * @param core\event\user_updated $event event.
     */
    public static function user_updated(\core\event\user_updated $event) {
        global $CFG;
        $userdata = user_get_users_by_id([$event->relateduserid]);

        // User password should be encrypted. Using Openssl for it.
        // We will use token as the key as it is present on both sites.
        // Open SSL encryption initialization.
        $encmethod = 'AES-128-CTR';

        $apihandler = auth_edwiserbridge_api_handler_instance();
        $eb_connection_settings = get_config('auth_edwiserbridge', 'eb_connection_settings');
        $eb_sync_settings = get_config('auth_edwiserbridge', 'eb_synch_settings');
        
        if (!empty($eb_connection_settings)) {

            $sites = json_decode($eb_connection_settings, true);
            $synchconditions = json_decode($eb_sync_settings, true);

            foreach ($sites as $value) {
                if (
                    isset($synchconditions[$value["wp_name"]]["user_updation"]) &&
                    $synchconditions[$value["wp_name"]]["user_updation"] &&
                    $value['wp_token']
                ) {
                    $password    = '';
                    $enciv       = '';
                    $newpassword = optional_param('newpassword', '', PARAM_TEXT);

                    // If new password in not empty.
                    if ($newpassword && !empty($newpassword)) {
                        $enckey   = openssl_digest($value["wp_token"], 'SHA256', true);
                        $enciv = substr(hash('sha256', $value["wp_token"]), 0, 16);
                        $password = openssl_encrypt($newpassword, $encmethod, $enckey, 0, $enciv);
                    }

                    require_once("$CFG->dirroot/user/profile/lib.php");

                    $requestdata = [
                        'action'        => 'user_updated',
                        'user_id'       => $event->relateduserid,
                        'first_name'    => $userdata[$event->relateduserid]->firstname,
                        'last_name'     => $userdata[$event->relateduserid]->lastname,
                        'email'         => $userdata[$event->relateduserid]->email,
                        'country'       => $userdata[$event->relateduserid]->country,
                        'city'          => $userdata[$event->relateduserid]->city,
                        'phone'         => $userdata[$event->relateduserid]->phone1,
                        'password'      => $password,
                        'enc_iv'        => $enciv,
                        'secret_key'    => $value['wp_token'], // Adding Token for verification in WP from Moodle.
                        'custom_fields' => json_encode(profile_user_record($event->relateduserid, false)), // Custom fields data.
                    ];

                    $apihandler->connect_to_wp_with_args($value["wp_url"], $requestdata);
                }
            }
        }
    }

    /**
     * Functionality to handle user password update event.
     *
     * This method is called when a user's password is updated in the system.
     * It is responsible for updating the user's password in the connected WordPress sites.
     *
     * @param core\event\user_password_updated $event The event object containing information about the password update.
     */
    public static function user_password_updated(\core\event\user_password_updated $event) {
        global $CFG;

        $userid = $event->userid;
        if ($event->relateduserid) {
            $userid = $event->relateduserid;
        }

        $userdata = user_get_users_by_id([$userid]);

        // User password should be encrypted. Using Openssl for it.
        // We will use token as the key as it is present on both sites.
        // Open SSL encryption initialization.
        $encmethod = 'AES-128-CTR';
        $apihandler  = auth_edwiserbridge_api_handler_instance();
        $eb_connection_settings = get_config('auth_edwiserbridge', 'eb_connection_settings');
        $eb_sync_settings = get_config('auth_edwiserbridge', 'eb_synch_settings');
        
        if (!empty($eb_connection_settings)) {

            $sites = json_decode($eb_connection_settings, true);
            $synchconditions = json_decode($eb_sync_settings, true);

            foreach ($sites as $value) {
                if (
                    isset($synchconditions[$value["wp_name"]]["user_updation"]) &&
                    $synchconditions[$value["wp_name"]]["user_updation"] &&
                    $value['wp_token']
                ) {

                    $password    = '';
                    $enciv       = '';
                    $newpassword = optional_param('newpassword1', '', PARAM_TEXT);
                    if (empty($newpassword)) {
                        $newpassword = optional_param('password', '', PARAM_TEXT);
                    }

                    // If new password in not empty.
                    if ($newpassword && !empty($newpassword)) {
                        $enckey   = openssl_digest($value["wp_token"], 'SHA256', true);
                        $enciv    = substr(hash('sha256', $value["wp_token"]), 0, 16);
                        $password = openssl_encrypt($newpassword, $encmethod, $enckey, 0, $enciv);
                    }

                    $requestdata = [
                        'action'     => 'user_updated',
                        'user_id'    => $userid,
                        'email'      => $userdata[$userid]->email,
                        'password'   => $password,
                        'enc_iv'     => $enciv,
                        'secret_key' => $value['wp_token'], // Adding Token for verification in WP from Moodle.
                    ];

                    $apihandler->connect_to_wp_with_args($value["wp_url"], $requestdata);
                }
            }
        }
    }

    /**
     * Functionality to handle user deletion event.
     *
     * @param core\event\user_deleted $event event.
     */
    public static function user_deleted(\core\event\user_deleted $event) {
        $requestdata = [
            'action'  => 'user_deletion',
            'user_id' => $event->relateduserid,
        ];

        if (auth_edwiserbridge_check_if_request_is_from_wp()) {
            return;
        }

        $apihandler = auth_edwiserbridge_api_handler_instance();
        $eb_connection_settings = get_config('auth_edwiserbridge', 'eb_connection_settings');
        $eb_sync_settings = get_config('auth_edwiserbridge', 'eb_synch_settings');
        
        if (!empty($eb_connection_settings)) {
            $sites = json_decode($eb_connection_settings, true);
            $synchconditions = json_decode($eb_sync_settings, true);

            foreach ($sites as $value) {
                if (isset($synchconditions[$value["wp_name"]]["user_deletion"]) &&
                        $synchconditions[$value["wp_name"]]["user_deletion"] && $value['wp_token']
                ) {
                    // Adding Token for verification in WP from Moodle.
                    $requestdata['secret_key'] = $value['wp_token'];

                    $apihandler->connect_to_wp_with_args($value["wp_url"], $requestdata);
                }
            }
        }
    }

    /**
     * Functionality to handle Course creation event.
     *
     * @param core\event\course_created $event The course creation event.
     */
    public static function course_created(\core\event\course_created $event) {
        // Get course info.
        $course = get_course($event->courseid);

        $apihandler = auth_edwiserbridge_api_handler_instance();
        $eb_connection_settings = get_config('auth_edwiserbridge', 'eb_connection_settings');
        $eb_sync_settings = get_config('auth_edwiserbridge', 'eb_synch_settings');
        
        if (!empty($eb_connection_settings)) {
            $sites = json_decode($eb_connection_settings, true);
            $synchconditions = json_decode($eb_sync_settings, true);

            foreach ($sites as $value) {
                if (
                    isset($synchconditions[$value["wp_name"]]["course_creation"]) &&
                    $synchconditions[$value["wp_name"]]["course_creation"] &&
                    $value['wp_token']
                ) {
                    $requestdata = [
                        'action'      => 'course_created',
                        'course_id'   => $event->courseid,
                        'fullname'    => $course->fullname,
                        'summary'     => $course->summary,
                        'cat'         => $course->category,
                        'secret_key'  => $value['wp_token'], // Adding Token for verification in WP from Moodle.
                    ];

                    $apihandler->connect_to_wp_with_args($value["wp_url"], $requestdata);
                }
            }
        }
    }

    /**
     * Functionality to handle Course deletion event.
     *
     * @param core\event\course_deleted $event The course deletion event.
     */
    public static function course_deleted(\core\event\course_deleted $event) {
        $requestdata = [
            'action'    => 'course_deleted',
            'course_id' => $event->objectid,
        ];

        $apihandler = auth_edwiserbridge_api_handler_instance();
        $eb_connection_settings = get_config('auth_edwiserbridge', 'eb_connection_settings');
        $eb_sync_settings = get_config('auth_edwiserbridge', 'eb_synch_settings');
        
        if (!empty($eb_connection_settings)) {
            $sites = json_decode($eb_connection_settings, true);
            $synchconditions = json_decode($eb_sync_settings, true);

            foreach ($sites as $value) {
                if (
                    isset($synchconditions[$value["wp_name"]]["course_deletion"]) &&
                    $synchconditions[$value["wp_name"]]["course_deletion"] &&
                    $value['wp_token']
                ) {
                    // Adding Token for verification in WP from Moodle.
                    $requestdata['secret_key'] = $value['wp_token'];

                    $apihandler->connect_to_wp_with_args($value["wp_url"], $requestdata);
                }
            }
        }
    }

    /**
     * Handles the dashboard viewed event.
     *
     * @param \core\event\dashboard_viewed $event The dashboard viewed event.
     */
    public static function dashboard_viewed(\core\event\dashboard_viewed $event) {
        global $CFG;

        // Check if user is admin or not.
        if (!is_siteadmin()) {
            return;
        }

        $transient = get_config('auth_edwiserbridge', 'plugin_update_transient');
        if ( $transient < time() && isset($CFG->enable_auto_update_check) && $CFG->enable_auto_update_check ) {
            auth_edwiserbridge_check_plugin_update();
            set_config('plugin_update_transient', time() + (7 * 24 * 60 * 60), 'auth_edwiserbridge');
        }
    }
}
