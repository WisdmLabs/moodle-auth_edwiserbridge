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
 * SSO login script.
 * Functionality to manage SSO login.
 *
 * @package    auth_edwiserbridge
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php'); // @codingStandardsIgnoreLine
global $CFG, $SESSION, $DB, $USER;
require_once($CFG->dirroot.'/auth/edwiserbridge/lib.php');

// If user is already logged in and not in SSO flow, redirect to home
$loginid = optional_param('login_id', 0, PARAM_INT);
$logoutid = optional_param('logout_id', 0, PARAM_INT);
$wdmdata = optional_param('wdm_data', '', PARAM_RAW);
if (isloggedin() && !isguestuser() && empty($loginid) && empty($logoutid) && empty($wdmdata)) {
    // User is already logged in and not in SSO flow, redirect to home
    redirect($CFG->wwwroot);
    return;
}

// Login may somehow modify this.
$SESSION->wantsurl = $CFG->wwwroot;

$tempurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;

// Killing session.
$wdmdata = optional_param('wdm_data', '', PARAM_RAW);
if (!empty($wdmdata)) {
    $secretkey = auth_edwiserbridge_get_sso_secret_key();

    if ($secretkey == '') {
        echo get_string('plugin_not_configured', 'auth_edwiserbridge');
        die();
    }

    $rawdata  = $wdmdata;
    $userdata = auth_edwiserbridge_decrypt_string($rawdata, $secretkey);
    $userid  = auth_edwiserbridge_get_key_value($userdata, 'moodle_user_id');

    $key = 'eb_sso_user_session_id';
    auth_edwiserbridge_set_user_session($userid, $key, $wdmdata);

    unset( $_POST['wdm_data'] );
    die();
}

if ($tempurl == null) {
    $tempurl = get_config('auth_edwiserbridge', 'wpsiteurl');
}

if ($tempurl == '') {
    $tempurl = $CFG->wwwroot;
}

$secretkey = auth_edwiserbridge_get_sso_secret_key();

$userid = optional_param('logout_id', 0, PARAM_INT);
if ( !empty( $userid ) && $userid !== 0 ) {
    $sessionkey = 'eb_sso_user_session_id';

    $record   = auth_edwiserbridge_get_user_session($userid, $sessionkey);
    $rawdata  = isset($record) ? $record : '';
    $userdata = auth_edwiserbridge_decrypt_string($rawdata, $secretkey);
    $hash     = auth_edwiserbridge_get_key_value( $userdata, 'wp_one_time_hash' );

    auth_edwiserbridge_remove_user_session($userid);
    $verifycode = optional_param('veridy_code', '', PARAM_RAW);
    if ( !empty( $verifycode ) && $hash === $verifycode ) {

        $logoutredirect = auth_edwiserbridge_get_key_value( $userdata, 'logout_redirect' );
        if ($logoutredirect == '') {
            redirect( $tempurl );
        }
        require_logout();
        redirect( $logoutredirect );
    } else {
        $wpurl = get_config('auth_edwiserbridge', 'wpsiteurl');
        $wpurl = empty( $wpurl ) ? $CFG->wwwroot : $wpurl;
        redirect( $wpurl );
    }
}

$userid = optional_param('login_id', 0, PARAM_INT);
if (!empty($userid) && $userid !== 0) {
    $tempurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;

    $sessionkey = 'eb_sso_user_session_id';

    $record  = auth_edwiserbridge_get_user_session($userid, $sessionkey);
    $rawdata = isset($record) ? $record : '';

    auth_edwiserbridge_remove_user_session($userid);

    $userdata = auth_edwiserbridge_decrypt_string( $rawdata, $secretkey );
    $userid = auth_edwiserbridge_get_key_value( $userdata, 'moodle_user_id' ); // The users id in the wordpress database.
    $hash = auth_edwiserbridge_get_key_value( $userdata, 'wp_one_time_hash' );

    $verifycode = optional_param('veridy_code', '', PARAM_RAW);
    if ( !empty( $verifycode ) && $hash === $verifycode ) {
        if ($userid == '') {
            $wordpressurl = str_replace('wp-login.php', '', $tempurl);
            if (strpos($wordpressurl, '?') !== false) {
                $wordpressurl .= '&wdm_moodle_error=wdm_moodle_error';
            } else {
                $wordpressurl .= '?wdm_moodle_error=wdm_moodle_error';
            }
            redirect($wordpressurl);
            return;
        }
        $loginredirect = auth_edwiserbridge_get_key_value($userdata, 'login_redirect');

        // Get course id from login_redirect.
        $courseid = 0;
        if (strpos($loginredirect, 'course/view.php?id=') !== false) {
            $courseid = explode('course/view.php?id=', $loginredirect)[1];
        }

        if ($courseid != 0) {
            $course = $DB->record_exists('course', ['id' => $courseid]);
            // If course is not available then redirect to site home page.
            if (empty($course)) {
                $loginredirect = $CFG->wwwroot;
            }
        }
        if ($DB->record_exists('user', ['id' => $userid])) {
            // Update manually created user that has the same username but doesn't yet have the right idnumber
            // ensure we have the latest data.
            $user = get_complete_user_data('id', $userid);
        } else {
            $wordpressurl = str_replace('wp-login.php', '', $tempurl);
            if (strpos($wordpressurl, '?') !== false) {
                $wordpressurl .= '&wdm_moodle_error=wdm_moodle_error';
            } else {
                $wordpressurl .= '?wdm_moodle_error=wdm_moodle_error';
            }
            redirect($wordpressurl);
            return;
        }

        // All that's left to do is to authenticate this user and set up their active session.
        // Check if user data was successfully retrieved before proceeding
        if ($user && is_object($user) && isset($user->username) && isset($user->password)) {  
            $authplugin = get_auth_plugin('edwiserbridge');
            if ($authplugin->user_login($user->username, $user->password)) {
                $user->loggedin = true;
                $user->site = $CFG->wwwroot;
                complete_user_login($user); // Now performs \core\event\user_loggedin event.

                // Ensure session is written and cookie is set before redirect.
                // This is critical for the session to persist after redirect.
                if (class_exists('\core\session\manager')) {
                    \core\session\manager::write_close();
                } else {
                    // Fallback for older Moodle versions.
                    if (function_exists('session_write_close')) {
                        session_write_close();
                    }
                }
                
                // Use login_redirect if available, otherwise use wantsurl
                $redirecturl = !empty($loginredirect) ? $loginredirect : $SESSION->wantsurl;
                
                // Ensure redirect URL is set, default to home page
                if (empty($redirecturl)) {
                    $redirecturl = $CFG->wwwroot;
                }
                
                // If redirect URL is relative, make it absolute
                if (strpos($redirecturl, 'http') !== 0) {
                    $redirecturl = $CFG->wwwroot . '/' . ltrim($redirecturl, '/');
                }
                
                // Prevent redirecting to login pages when user is already logged in
                $loginpages = ['login/index.php', 'login.php', 'auth/edwiserbridge/login.php', 'auth/edwiserbridge/wdmwplogin.php'];
                foreach ($loginpages as $loginpage) {
                    if (strpos($redirecturl, $loginpage) !== false) {
                        // If redirecting to login page, redirect to dashboard instead
                        $redirecturl = $CFG->wwwroot . '/my/';
                        break;
                    }
                }
                
                // Remove loginredirect parameter from URL if present
                if (strpos($redirecturl, 'loginredirect=') !== false) {
                    $redirecturl = preg_replace('/[?&]loginredirect=[^&]*/', '', $redirecturl);
                    $redirecturl = rtrim($redirecturl, '?&');
                    // If URL becomes empty or just base URL, redirect to dashboard
                    if (empty($redirecturl) || $redirecturl == $CFG->wwwroot || $redirecturl == $CFG->wwwroot . '/') {
                        $redirecturl = $CFG->wwwroot . '/my/';
                    }
                }
                
                // Add cache-busting parameter to force fresh page load and ensure session is recognized.
                // This prevents browser from serving cached page that doesn't show logged-in state.
                $separator = (strpos($redirecturl, '?') !== false) ? '&' : '?';
                $redirecturl .= $separator . 'eb_sso=' . time();
                
                // Ensure session is written before redirect
                if (isset($SESSION)) {
                    $SESSION->wantsurl = $redirecturl;
                }
                
                redirect($redirecturl);
                return;
            }
        } else {
            // If user data is invalid, redirect to WordPress with error
            $wordpressurl = str_replace('wp-login.php', '', $tempurl);
            if (strpos($wordpressurl, '?') !== false) {
                $wordpressurl .= '&wdm_moodle_error=wdm_moodle_error';
            } else {
                $wordpressurl .= '?wdm_moodle_error=wdm_moodle_error';
            }
            redirect($wordpressurl);
            return;
        }
    }
}
redirect($SESSION->wantsurl);
