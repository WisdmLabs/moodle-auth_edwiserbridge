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
 * Anobody can login with any password.
 *
 * @package auth_none
 * @author Martin Dougiamas
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/authlib.php');

/**
 * Plugin for no authentication.
 */
class auth_plugin_edwiserbridge extends auth_plugin_base {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->authtype = 'edwiserbridge';
        $this->config = get_config('auth_edwiserbridge');
    }

    /**
     * Old syntax of class constructor. Deprecated in PHP7.
     *
     * @deprecated since Moodle 3.1
     */
    public function auth_plugin_wdmwpmoodle()
    {
        debugging('Use of class name as constructor is deprecated', DEBUG_DEVELOPER);
        self::__construct();
    }

    /**
     * Returns true if the username and password work or don't exist and false
     * if the user exists and the password is wrong.
     *
     * @param string $username The username
     * @param string $password The password
     *
     * @return bool Authentication success or failure.
     */
    public function user_login($username, $password = null)
    {
        global $CFG, $DB;

        if ($password == null || $password == '') {
            return false;
        }
        $user = $DB->get_record('user', array('username' => $username, 'password' => $password, 'mnethostid' => $CFG->mnet_localhost_id));

        if (!empty($user->suspended)) {
            error_log("user_login: suspended");
            return false;
        }
        
        if ($user) {
            return true;
        }

        return false;
    }

    public function prevent_local_passwords()
    {
        return false;
    }

    /**
     * Returns true if this authentication plugin is 'internal'.
     *
     * @return bool
     */
    public function is_internal()
    {
        return false;
    }

    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * @return bool
     */
    public function can_change_password()
    {
        return false;
    }

    /**
     * Returns the URL for changing the user's pw, or empty if the default can
     * be used.
     *
     * @return moodle_url
     */
    public function change_password_url()
    {
        return;
    }

    /**
     * Returns true if plugin allows resetting of internal password.
     *
     * @return bool
     */
    public function can_reset_password()
    {
        return false;
    }

    public function eb_send_curl_request($request_data)
    {
        $request_url = $this->config->wpsiteurl;
        $request_url .= '/wp-json/edwiser-bridge/sso/';

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $request_url,
            CURLOPT_TIMEOUT => 100
        ));

        // If wordpress server have user-agent restriction then uncomment below line
        // curl_setopt($curl,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');

        curl_setopt( $curl, CURLOPT_POST, 1 );
        curl_setopt( $curl, CURLOPT_POSTFIELDS, $request_data );
        $response = curl_exec( $curl );
    }

    /**
     * Function to login user into wp site.
     * @since 1.2
     */
    public function user_authenticated_hook(&$user, $username, $password)
    {
        global $CFG, $SESSION;

        // Guest user.
        if (isguestuser($user->id)) {
            return true;
        }

        //Secret key is empty.
        if (empty($this->config->sharedsecret)) {
            return true;
        }

        // WP URL is not a valid URL.
        if (!filter_var($this->config->wpsiteurl, FILTER_VALIDATE_URL)) {
            return true;
        }

        $wpsiteurl = strtok($this->config->wpsiteurl, '?');

        $hash = hash('md5', rand( 10,1000 ) );


        // All conditions are passed.
        $args = array(
            'action'            => 'login',
            'mdl_uid'           => $user->id,
            'mdl_uname'         => $user->username,
            'mdl_email'         => $user->email,
            'mdl_key'           => $this->config->sharedsecret,
            'mdl_wpurl'         => $wpsiteurl,
            'redirect_to'       => isset($SESSION->wantsurl) ? $SESSION->wantsurl : $CFG->wwwroot,
            'mdl_one_time_code' => $hash
        );

        $encrypted_args = self::wdm_get_encrypted_query_args($args, $this->config->sharedsecret);

        // Send curl to wp site with data.
        $this->eb_send_curl_request( array( 'wdmargs' => $encrypted_args ) );

        $SESSION->wantsurl = $CFG->wwwroot.'/auth/edwiserbridge/wdmwplogin.php?'.'wdmaction=login&mdl_uid=' . $user->id . '&verify_code=' . $hash . '&wpsiteurl='.urlencode( $wpsiteurl );

        return true;
    }

    /**
     * Redirect users to specific page after logout. Also, logs out from wp site.
     * @since 1.2
     */
    public function logoutpage_hook()
    {
        global $redirect, $USER;

        //Secret key is empty.
        if (empty($this->config->sharedsecret)) {
            return true;
        }

        // Redirect URL is a valid URL.
        if (filter_var($this->config->logoutredirecturl, FILTER_VALIDATE_URL)) {
            $redirect = $this->config->logoutredirecturl;
        }

        // WP Site URL is not a valid URL.
        if (!filter_var($this->config->wpsiteurl, FILTER_VALIDATE_URL)) {
            return true;
        }
        $hash = hash('md5', rand( 10,1000 ) );

        $args = array(
            'action'        => 'logout',
            'mdl_key'       => $this->config->sharedsecret,
            'redirect_to'   => $redirect,
            'mdl_uid'       => $USER->id,
            'mdl_uname'     => $USER->username,
            'mdl_email'     => $USER->email,
            'mdl_one_time_code' => $hash
        );

        $encrypted_args = self::wdm_get_encrypted_query_args($args, $this->config->sharedsecret);
        $this->eb_send_curl_request( array( 'wdmargs' => $encrypted_args ) );

        $redirect = strtok($this->config->wpsiteurl, '?') .'?wdmaction=logout&mdl_uid=' . $USER->id . '&verify_code=' . $hash;

    }

    /**
     * Function to encrypt query argument.
     * @since 1.2
     */
    public static function wdm_get_encrypted_query_args($args, $key)
    {
        $query = http_build_query( $args, 'flags_' );
        $token = $query;

        $enc_method = 'AES-128-CTR';

        $enc_key = openssl_digest( $key, 'SHA256', true );

        $enc_iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($enc_method));
        $crypttext = openssl_encrypt($token, $enc_method, $enc_key, 0, $enc_iv) . "::" . bin2hex($enc_iv);

        $data = base64_encode($crypttext);
        $data = str_replace(array('+', '/', '='), array('-', '_', ''), $data);

        $encrypted_args = trim($data);
        return $encrypted_args;
    }


    /**
     * Return a list of identity providers to display on the login page.
     *
     * @param string|moodle_url $wantsurl The requested URL.
     * @return array List of arrays with keys url, iconurl and name.
     */
    public function loginpage_idp_list($wantsurl) {
        global $CFG, $SESSION;

        //Secret key is empty.
        if (empty($this->config->wploginenablebtn)) {
            return array();
        }

        if (empty($this->config->sharedsecret)) {
            return array();
        }

        // WP URL is not a valid URL.
        if (!filter_var($this->config->wpsiteurl, FILTER_VALIDATE_URL)) {
            return array();
        }

        $wpsiteurl = strtok($this->config->wpsiteurl, '?');

        $hash = hash('md5', rand( 10,1000 ) );

        // All conditions are passed.
        $args = array(
            'mdl_key' => $this->config->sharedsecret,
        );

        $encrypted_args = self::wdm_get_encrypted_query_args($args, $this->config->sharedsecret );

        $url  = $wpsiteurl .'?wdmaction=login_with_moodle&data=' . $encrypted_args;
        $url = new moodle_url( $url, array( 'installdepx' => 1, 'confirminstalldep' => 1 ) );

        $text = ! empty($this->config->wploginbtntext) ? $this->config->wploginbtntext : get_string('WordPress', 'auth_edwiserbridge') ;
        if(isset($this->config->wploginbtnicon) && !empty($this->config->wploginbtnicon)) {
            $iconurl = moodle_url::make_pluginfile_url(
                context_system::instance()->id,
                'auth_edwiserbridge',
                'wploginbtnicon',
                0,
                '',
                $this->config->wploginbtnicon
            );
        } else {
            //load default icon from pix folder.
            $iconurl = $CFG->wwwroot . '/auth/edwiserbridge/pix/wp-logo.png';
        }

        $result[] = ['url' => $url, 'iconurl' => $iconurl, 'name' => $text ];

        return $result;
    }
}
