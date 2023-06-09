<?php
global $CFG, $USER, $SESSION, $DB;
require '../../config.php';
// logon may somehow modify this
$SESSION->wantsurl = $CFG->wwwroot;

$temp_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;


// Killing session.
$wdm_data = optional_param('wdm_data', '', PARAM_RAW);
if (!empty($wdm_data)) {
    $PASSTHROUGH_KEY = checkPassthroughKeyIsSet();

    if ($PASSTHROUGH_KEY == '') {
        echo "Sorry, this plugin has not yet been configured. Please contact the Moodle administrator for details";
        die();
    }

    $rawdata  = $wdm_data;
    $userdata = decrypt_string($rawdata, $PASSTHROUGH_KEY);
    $user_id  = get_key_value($userdata, 'moodle_user_id');

    $key = 'eb_sso_user_session_id';
    set_wdm_user_session($user_id, $key, $wdm_data);

    unset( $_POST['wdm_data'] );
    die();
}

// check passthrough key is set or not
function checkPassthroughKeyIsSet()
{
    $PASSTHROUGH_KEY = get_config('auth_edwiserbridge', 'sharedsecret');
    if (!isset($PASSTHROUGH_KEY)) {
        $wordpress_url = str_replace('wp-login.php', '', $temp_url);
        if (strpos($wordpress_url, '?') !== false) {
            $wordpress_url .= '&wdm_moodle_error=wdm_moodle_error';
        } else {
            $wordpress_url .= '?wdm_moodle_error=wdm_moodle_error';
        }
        redirect($wordpress_url);
        return;
    }

    return $PASSTHROUGH_KEY;
}

if ($temp_url == null) {
    $temp_url = get_config('auth_edwiserbridge', 'wpsiteurl');
}

if ($temp_url=="") {
    $temp_url = $CFG->wwwroot;
}

$PASSTHROUGH_KEY = get_config('auth_edwiserbridge', 'sharedsecret');

if (!isset($PASSTHROUGH_KEY)) {
    $wordpress_url = str_replace('wp-login.php', '', $temp_url);
    if (strpos($wordpress_url, '?') !== false) {
        $wordpress_url .= '&wdm_moodle_error=wdm_moodle_error';
    } else {
        $wordpress_url .= '?wdm_moodle_error=wdm_moodle_error';
    }
    redirect($wordpress_url);
    return;
}

/**
 * Handler for decrypting incoming data (specially handled base-64) in which is encoded a string of key=value pairs.
 */
function decrypt_string($base64, $key)
{
    if (!$base64) {
        return '';
    }
    $data = str_replace(array('-', '_'), array('+', '/'), $base64); // manual de-hack url formatting
    $mod4 = strlen($data) % 4; // base64 length must be evenly divisible by 4
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    $crypttext = base64_decode($data);

    if (preg_match("/^(.*)::(.*)$/", $crypttext, $regs)) {

        list(, $crypttext, $enc_iv) = $regs;
        $enc_method = 'AES-128-CTR';
             $enc_key = openssl_digest( $key, 'SHA256', true);
        $decrypted_token = openssl_decrypt($crypttext, $enc_method, $enc_key, 0, hex2bin($enc_iv));
    }
    return trim($decrypted_token);
}
/**
 * querystring helper, returns the value of a key in a string formatted in key=value&key=value&key=value pairs, e.g. saved querystrings.
 */
function get_key_value($string, $key)
{
    $list = explode('&', str_replace('&amp;', '&', $string));
    foreach ($list as $pair) {
        $item = explode('=', $pair);
        if (strtolower($key) == strtolower($item[ 0 ])) {
            return urldecode($item[ 1 ]); // not for use in $_GET etc, which is already decoded, however our encoder uses http_build_query() before encrypting
        }
    }
    return '';
}

$user_id = optional_param('logout_id', 0, PARAM_INT);
if ( !empty( $user_id ) && $user_id !== 0 ) {
    $sess_key = 'eb_sso_user_session_id';

    $record   = get_wdm_user_session($user_id, $sess_key);
    $rawdata  = isset($record->value) ? $record->value : '';
    $userdata = decrypt_string($rawdata, $PASSTHROUGH_KEY);
    $hash     = get_key_value( $userdata, 'wp_one_time_hash' );

    remove_wdm_user_session($user_id);
    $veridy_code = optional_param('veridy_code', '', PARAM_RAW);
    if ( !empty( $veridy_code ) && $hash === $veridy_code ) {

        $logout_redirect = get_key_value( $userdata, 'logout_redirect' );
        if ($logout_redirect == '') {
            redirect( $temp_url );
        }
        require_logout();
        redirect( $logout_redirect );
    } else {
        $wp_url = get_config('auth_edwiserbridge', 'wpsiteurl');
        $wp_url = empty( $wp_url ) ? $CFG->wwwroot : $wp_url ;
        redirect( $wp_url );
    }
}

$user_id = optional_param('login_id', 0, PARAM_INT);
if (!empty($user_id) && $user_id !== 0) {
    $temp_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;

    $sess_key = 'eb_sso_user_session_id';

    $record  = get_wdm_user_session($user_id, $sess_key);
    $rawdata = isset($record->value) ? $record->value : '';
    
    remove_wdm_user_session($user_id);

    $userdata = decrypt_string( $rawdata, $PASSTHROUGH_KEY );
    $user_id  = get_key_value( $userdata, 'moodle_user_id' ); // the users id in the wordpress database, stored here for possible user-matching
    $hash     = get_key_value( $userdata, 'wp_one_time_hash' );

    $veridy_code = optional_param('veridy_code', '', PARAM_RAW);
    if ( !empty( $veridy_code ) && $hash === $veridy_code ) {
        if ($user_id == '') {
            $wordpress_url = str_replace('wp-login.php', '', $temp_url);
            if (strpos($wordpress_url, '?') !== false) {
                $wordpress_url .= '&wdm_moodle_error=wdm_moodle_error';
            } else {
                $wordpress_url .= '?wdm_moodle_error=wdm_moodle_error';
            }
            redirect($wordpress_url);
            return;
        }
        $login_redirect = get_key_value($userdata, 'login_redirect');

        // get course id from login_redirect
        $course_id = 0;
        if (strpos($login_redirect, 'course/view.php?id=') !== false) {
            $course_id = explode('course/view.php?id=', $login_redirect)[1];
        }

        if ($course_id != 0) {
            $course = $DB->record_exists('course', array('id' => $course_id));
            // if course is not available then redirect to site home page
            if (empty($course)) {
                $login_redirect = $CFG->wwwroot;
            }
        }
        if ($DB->record_exists('user', array('id' => $user_id))) {
            // update manually created user that has the same username but doesn't yet have the right idnumber
            // ensure we have the latest data
            $user = get_complete_user_data('id', $user_id);
        } else {
            $wordpress_url = str_replace('wp-login.php', '', $temp_url);
            if (strpos($wordpress_url, '?') !== false) {
                $wordpress_url .= '&wdm_moodle_error=wdm_moodle_error';
            } else {
                $wordpress_url .= '?wdm_moodle_error=wdm_moodle_error';
            }
            redirect($wordpress_url);
            return;
        }

        // all that's left to do is to authenticate this user and set up their active session
        $authplugin = get_auth_plugin('edwiserbridge'); // me!
        if ($authplugin->user_login($user->username, $user->password)) {
            $user->loggedin = true;
            $user->site = $CFG->wwwroot;
            complete_user_login($user); // now performs \core\event\user_loggedin event
        }

        if ($login_redirect != '') {
            redirect($login_redirect);
        }
        $course_id = get_key_value($userdata, 'moodle_course_id');
        if ($course_id != '') {
            $SESSION->wantsurl = $CFG->wwwroot.'/course/view.php?id='.$course_id;
        }
    } else {
        $wp_url = get_config('auth_edwiserbridge', 'wpsiteurl');
        $wp_url = empty( $wp_url ) ? $CFG->wwwroot : $wp_url ;
        redirect( $wp_url );
    }

}
redirect($SESSION->wantsurl);

// user_session_wdmwpmoodle
// Set wdm_user session
function get_wdm_user_session($user_id, $sess_key)
{
    global $DB, $CFG;
    $table = 'user_preferences';
    $record = $DB->get_record($table, array('userid'=>$user_id, 'name'=>$sess_key));
    return $record;
}

// Get wdm_user session
function set_wdm_user_session($user_id, $sess_key, $wdm_data)
{
    set_user_preference($sess_key, $wdm_data, $user_id);
}

// Remove wdm_user session
function remove_wdm_user_session($user_id)
{
    global $DB, $CFG;

    $table = 'user_preferences';
    $DB->delete_records($table, array('userid'=>$user_id, 'name'=>'eb_sso_user_session_id'));
}

function unsetPostMethod()
{
    unset($_POST['wdm_data']);
    unset($_POST['redirect_to']);
    unset($_POST['next_user_id']);
}
