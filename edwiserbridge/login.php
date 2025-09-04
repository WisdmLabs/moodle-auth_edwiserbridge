<?php
// This file is part of Edwiser Bridge Moodle Plugin.
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
 * Login handling for Edwiser Bridge.
 *
 * @package   auth_edwiserbridge
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG, $USER, $SESSION, $DB;
require('../../config.php');

// Logon may somehow modify this.
$SESSION->wantsurl = $CFG->wwwroot;

$tempurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;


// Killing session.
$wdmdata = optional_param('wdm_data', '', PARAM_RAW);
if (!empty($wdmdata)) {
    $passthroughkey = checkpassthroughkeyisset();

    if ($passthroughkey == '') {
        echo "Sorry, this plugin has not yet been configured. Please contact the Moodle administrator for details";
        die();
    }

    $rawdata  = $wdmdata;
    $userdata = decrypt_string($rawdata, $passthroughkey);
    $userid  = get_key_value($userdata, 'moodle_user_id');

    $key = 'eb_sso_user_session_id';
    set_wdm_user_session($userid, $key, $wdmdata);

    unset( $_POST['wdm_data'] );
    die();
}

// check passthrough key is set or not
function checkpassthroughkeyisset() {
    $passthroughkey = get_config('auth_edwiserbridge', 'sharedsecret');
    if (!isset($passthroughkey)) {
        $wordpressurl = str_replace('wp-login.php', '', $tempurl);
        if (strpos($wordpressurl, '?') !== false) {
            $wordpressurl .= '&wdm_moodle_error=wdm_moodle_error';
        } else {
            $wordpressurl .= '?wdm_moodle_error=wdm_moodle_error';
        }
        redirect($wordpressurl);
        return;
    }

    return $passthroughkey;
}

if ($tempurl == null) {
    $tempurl = get_config('auth_edwiserbridge', 'wpsiteurl');
}

if ($tempurl == "") {
    $tempurl = $CFG->wwwroot;
}

$passthroughkey = get_config('auth_edwiserbridge', 'sharedsecret');

if (!isset($passthroughkey)) {
    $wordpressurl = str_replace('wp-login.php', '', $tempurl);
    if (strpos($wordpressurl, '?') !== false) {
        $wordpressurl .= '&wdm_moodle_error=wdm_moodle_error';
    } else {
        $wordpressurl .= '?wdm_moodle_error=wdm_moodle_error';
    }
    redirect($wordpressurl);
    return;
}

/**
 * Handler for decrypting incoming data (specially handled base-64) in which is encoded a string of key=value pairs.
 */
function decrypt_string($base64, $key) {
    if (!$base64) {
        return '';
    }
    $data = str_replace(array('-', '_'), array('+', '/'), $base64); // Convert URL-safe Base64 back to standard Base64
    
    // Base64 length must be evenly divisible by 4, so we pad if necessary
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    // Decode the Base64 data
    $crypttext = base64_decode($data);

    // AES-256-ECB does not use an IV, so we don't need to split the data
    // if (preg_match("/^(.*)::(.*)$/", $crypttext, $regs)) {

        // list(, $crypttext, $enc_iv) = $regs;
    // Directly decrypt the data
    $encmethod = 'AES-256-ECB'; // Use AES-256-ECB encryption method.
    $enckey = openssl_digest($key, 'SHA256', true); // Hash the key to 256 bits using SHA-256.
    // Decrypt the token with AES-256-ECB (no IV required).
    $decryptedtoken = openssl_decrypt($crypttext, $encmethod, $enckey, 0);
    // }
    // Return the decrypted value, trimmed of any extra spaces or characters
    return trim($decryptedtoken);
}
/**
 * querystring helper, returns the value of a key in a string formatted in key=value&key=value&key=value pairs, e.g. saved querystrings.
 */
function get_key_value($string, $key) {
    $list = explode('&', str_replace('&amp;', '&', $string));
    foreach ($list as $pair) {
        $item = explode('=', $pair);
        if (strtolower($key) == strtolower($item[0])) {
            return urldecode($item[1]); // Not for use in $_GET etc, which is already decoded, however our encoder uses http_build_query() before encrypting.
        }
    }
    return '';
}

$userid = optional_param('logout_id', 0, PARAM_INT);
if (!empty($userid) && $userid !== 0) {
    $sesskey = 'eb_sso_user_session_id';

    $record   = get_wdm_user_session($userid, $sesskey);
    $rawdata  = isset($record) ? $record : '';
    $userdata = decrypt_string($rawdata, $passthroughkey);
    $hash     = get_key_value( $userdata, 'wp_one_time_hash' );

    remove_wdm_user_session($userid);
    $veridycode = optional_param('veridy_code', '', PARAM_RAW);
    if (!empty($veridycode) && $hash === $veridycode) {

        $logoutredirect = get_key_value( $userdata, 'logout_redirect' );
        if ($logoutredirect == '') {
            redirect( $tempurl );
        }
        require_logout();
        redirect( $logoutredirect );
    } else {
        $wpurl = get_config('auth_edwiserbridge', 'wpsiteurl');
        $wpurl = empty($wpurl) ? $CFG->wwwroot : $wpurl;
        redirect( $wpurl );
    }
}

$userid = optional_param('login_id', 0, PARAM_INT);
if (!empty($userid) && $userid !== 0) {
    $tempurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;

    $sesskey = 'eb_sso_user_session_id';

    $record  = get_wdm_user_session($userid, $sesskey);
    $rawdata = isset($record) ? $record : '';
    
    remove_wdm_user_session($userid);

    $userdata = decrypt_string( $rawdata, $passthroughkey );
    $userid  = get_key_value( $userdata, 'moodle_user_id' ); // the users id in the wordpress database, stored here for possible user-matching
    $hash     = get_key_value( $userdata, 'wp_one_time_hash' );

    $veridycode = optional_param('veridy_code', '', PARAM_RAW);
    if (!empty($veridycode) && $hash === $veridycode) {
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
        $loginredirect = get_key_value($userdata, 'login_redirect');

        // get course id from login_redirect
        $courseid = 0;
        if (strpos($loginredirect, 'course/view.php?id=') !== false) {
            $courseid = explode('course/view.php?id=', $loginredirect)[1];
        }

        if ($courseid != 0) {
            $course = $DB->record_exists('course', array('id' => $courseid));
            // if course is not available then redirect to site home page
            if (empty($course)) {
                $loginredirect = $CFG->wwwroot;
            }
        }
        if ($DB->record_exists('user', array('id' => $userid))) {
            // update manually created user that has the same username but doesn't yet have the right idnumber
            // ensure we have the latest data
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

        // all that's left to do is to authenticate this user and set up their active session
        $authplugin = get_auth_plugin('edwiserbridge'); // me!
        if ($authplugin->user_login($user->username, $user->password)) {
            $user->loggedin = true;
            $user->site = $CFG->wwwroot;
            complete_user_login($user); // now performs \core\event\user_loggedin event
        }

        if ($loginredirect != '') {
            redirect($loginredirect);
        }
        $courseid = get_key_value($userdata, 'moodle_course_id');
        if ($courseid != '') {
            $SESSION->wantsurl = $CFG->wwwroot.'/course/view.php?id='.$courseid;
        }
    } else {
        $wpurl = get_config('auth_edwiserbridge', 'wpsiteurl');
        $wpurl = empty($wpurl) ? $CFG->wwwroot : $wpurl;
        redirect( $wpurl );
    }

}
redirect($SESSION->wantsurl);

// user_session_wdmwpmoodle
// Set wdm_user session
function get_wdm_user_session($userid, $sesskey) {
    global $DB, $CFG;
    $table = 'user_preferences';
    $record = $DB->get_record($table, array('userid' => $userid, 'name' => $sesskey));

    $record = get_user_preferences($sesskey, '', $userid);

    return $record;
}

// Get wdm_user session
function set_wdm_user_session($userid, $sesskey, $wdmdata) {
    set_user_preference($sesskey, $wdmdata, $userid);
}

// Remove wdm_user session
function remove_wdm_user_session($userid) {
    global $DB, $CFG;

    unset_user_preference('eb_sso_user_session_id', $userid);
}

function unsetpostmethod() {
    unset($_POST['wdm_data']);
    unset($_POST['redirect_to']);
    unset($_POST['next_user_id']);
}
