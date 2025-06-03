<?php
require_once(__DIR__ . '/compat.php');
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
 * Authentication plugin for Edwiser Bridge.
 * This plugin allows users to login to Moodle using their WordPress credentials.
 *
 * @package    auth_edwiserbridge
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/authlib.php');
use core\context\system as context_system;
/**
 * Plugin for no authentication.
 */
class auth_plugin_edwiserbridge extends auth_plugin_base {

    /**
     * Initializes the Edwiser Bridge authentication plugin.
     *
     * This constructor sets the authentication type to 'edwiserbridge' and loads the
     * plugin configuration from the 'auth_edwiserbridge' settings.
     */
    public function __construct() {
        $this->authtype = 'edwiserbridge';
        $this->config = get_config('auth_edwiserbridge');
    }

    /**
     * Old syntax of class constructor. Deprecated in PHP7.
     *
     * This method is a deprecated constructor for the `auth_plugin_edwiserbridge` class.
     * It calls the modern constructor `__construct()` and outputs a debugging message
     * indicating that the old constructor syntax is deprecated.
     *
     * @deprecated since Moodle 3.1
     */
    public function auth_plugin_wdmwpmoodle() {
        debugging('Use of class name as constructor is deprecated', DEBUG_DEVELOPER);
        self::__construct();
    }

    /**
     * Attempts to authenticate the user with the given username and password.
     *
     * This method checks if the provided username and password are valid for
     * authenticating the user. It returns true if the authentication is successful,
     * and false otherwise. Returns true if the username and password work or don't exist and false
     * if the user exists and the password is wrong.
     *
     * @param string $username The username to authenticate.
     * @param string $password The password to authenticate.
     * @return bool True if the authentication is successful, false otherwise.
     */
    public function user_login($username, $password = null) {
        global $CFG, $DB;

        if ($password == null || $password == '') {
            return false;
        }
        $user = $DB->get_record(
            'user',
            ['username' => $username, 'password' => $password, 'mnethostid' => $CFG->mnet_localhost_id]
        );

        if (!empty($user->suspended)) {
            return false;
        }

        if ($user) {
            return true;
        }

        return false;
    }

    /**
     * Indicates that local passwords are not prevented.
     *
     * This method returns false, which means that local passwords are not prevented
     * by this authentication plugin. This is likely an implementation detail of the
     * plugin, rather than an exported API.
     *
     * @return bool Always returns false.
     */
    public function prevent_local_passwords() {
        return false;
    }

    /**
     * Returns true if this authentication plugin is 'internal'.
     *
     * This method indicates whether the authentication plugin is considered an
     * "internal" plugin, meaning it is part of the core Moodle authentication
     * system. This is likely an implementation detail of the plugin, rather than
     * an exported API.
     *
     * @return bool Always returns false, indicating this plugin is not internal.
     */
    public function is_internal() {
        return false;
    }

    /**
     * Returns true if this authentication plugin can change the user's password.
     *
     * This method indicates whether the authentication plugin supports changing the
     * user's password. In this case, it returns false, indicating that the plugin
     * does not support changing the user's password.
     *
     * @return bool Always returns false.
     */
    public function can_change_password() {
        return false;
    }

    /**
     * Returns the URL for changing the user's password, or an empty string if the default
     * password change mechanism can be used.
     *
     * This method is an implementation detail of the authentication plugin, rather than
     * an exported API. It indicates whether the plugin provides a custom password change
     * mechanism, or if the default Moodle password change functionality can be used.
     *
     * @return void
     */
    public function change_password_url() {
        return;
    }

    /**
     * Returns true if plugin allows resetting of internal password.
     *
     * This method indicates whether the authentication plugin supports resetting the
     * user's password. In this case, it returns false, indicating that the plugin
     * does not support resetting the user's password.
     *
     * @return bool Always returns false.
     */
    public function can_reset_password() {
        return false;
    }

    /**
     * Sends a cURL request to the WordPress site.
     *
     * This method uses Moodle's cURL class to send a POST request to the WordPress site's
     * Edwiser Bridge SSO endpoint. It sets the necessary headers and options for the
     * cURL request, and returns the response.
     *
     * @param array $requestdata The data to be sent in the POST request.
     * @return string The response from the WordPress site.
     */
    public function eb_send_curl_request($requestdata) {
        global $CFG;
        $requesturl = $this->config->wpsiteurl;
        $requesturl .= '/wp-json/edwiser-bridge/sso/';

        // Use Moodle's curl class
        include_once($CFG->libdir . '/filelib.php'); // Ensure Moodle's curl class is available.

        // Use Moodle's curl class
        $curl = new \curl();

        // Set user agent header
        $useragent = 'Moodle/' . $CFG->version . ' (' . $CFG->wwwroot . ') Edwiser Bridge SSO';
        $curl->setHeader('User-Agent: ' . $useragent);

        // Set additional options
        $options = [
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_TIMEOUT' => 100,
        ];

        // Execute POST request
        $response = $curl->post($requesturl, $requestdata, $options);

        return $response;
    }

    /**
     * Handles user authentication for the Edwiser Bridge plugin.
     *
     * This method is called when a user is authenticated in the Moodle system. It performs
     * various checks to ensure the user is allowed to log in, such as checking if the user
     * is a guest, if the shared secret is empty, or if the WordPress site URL is not valid.
     * If all checks pass, it sends a cURL request to the WordPress site to log the user in.
     *
     * @param object $user The Moodle user object.
     * @param string $username The username of the authenticated user.
     * @param string $password The password of the authenticated user.
     * @return bool True if the user is authenticated, false otherwise.
     */
    public function user_authenticated_hook(&$user, $username, $password) {
        global $CFG, $SESSION;

        // Guest user.
        if (isguestuser($user->id)) {
            return true;
        }

        // Secret key is empty.
        if (empty($this->config->sharedsecret)) {
            return true;
        }

        // WP URL is not a valid URL.
        if (!filter_var($this->config->wpsiteurl, FILTER_VALIDATE_URL)) {
            return true;
        }

        $wpsiteurl = strtok($this->config->wpsiteurl, '?');

        $hash = hash('md5', rand(10, 1000));

        // All conditions are passed.
        $args = [
            'action'            => 'login',
            'mdl_uid'           => $user->id,
            'mdl_uname'         => $user->username,
            'mdl_email'         => $user->email,
            'mdl_key'           => $this->config->sharedsecret,
            'mdl_wpurl'         => $wpsiteurl,
            'redirect_to'       => isset($SESSION->wantsurl) ? $SESSION->wantsurl : $CFG->wwwroot,
            'mdl_one_time_code' => $hash,
        ];

        $encryptedargs = self::wdm_get_encrypted_query_args($args, $this->config->sharedsecret);

        // Send curl to wp site with data.
        $this->eb_send_curl_request(['wdmargs' => $encryptedargs]);

        $SESSION->wantsurl = $CFG->wwwroot.'/auth/edwiserbridge/wdmwplogin.php?'
                            .'wdmaction=login&mdl_uid=' . $user->id . '&verify_code=' . $hash
                            . '&wpsiteurl='.urlencode( $wpsiteurl );

        return true;
    }

    /**
     * Redirects the user to a specific page after logout, and also logs the user out from the WordPress site.
     *
     * This method is called when the user logs out from the Moodle site. It checks if the shared secret and the WordPress site URL are valid, and then constructs a URL to log the user out from the WordPress site. The method also sets the redirect URL for the user after logout.
     */
    public function logoutpage_hook() {
        global $redirect, $USER;

        // Secret key is empty.
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
        $hash = hash('md5', rand(10, 1000) );

        $args = [
            'action'        => 'logout',
            'mdl_key'       => $this->config->sharedsecret,
            'redirect_to'   => $redirect,
            'mdl_uid'       => $USER->id,
            'mdl_uname'     => $USER->username,
            'mdl_email'     => $USER->email,
            'mdl_one_time_code' => $hash,
        ];

        $encryptedargs = self::wdm_get_encrypted_query_args($args, $this->config->sharedsecret);
        $this->eb_send_curl_request(['wdmargs' => $encryptedargs]);

        $redirect = strtok($this->config->wpsiteurl, '?') .'?wdmaction=logout&mdl_uid=' . $USER->id . '&verify_code=' . $hash;

    }

    /**
     * Encrypts the given query arguments using AES-128-CTR encryption.
     *
     * @param array $args The query arguments to be encrypted.
     * @param string $key The shared secret key used for encryption.
     * @return string The encrypted query arguments.
     */
    public static function wdm_get_encrypted_query_args($args, $key) {
        $query = http_build_query( $args, 'flags_' );
        $token = $query;

        $encmethod = 'AES-256-ECB'; // Changed to AES-256-ECB

        // Ensure the key is hashed to 256 bits (32 bytes) using SHA-256
        $enckey = openssl_digest( $key, 'SHA256', true );

        $crypttext = openssl_encrypt($token, $encmethod, $enckey, 0); // No IV needed for ECB mode

        // Base64 encode the encrypted token
        $data = base64_encode($crypttext);
        // Convert to URL-safe Base64 (replace + with -, / with _, and remove = padding)
        $data = str_replace(['+', '/', '='], ['-', '_', ''], $data);

        // Trim any unwanted spaces or characters
        $encryptedargs = trim($data);
        
        return $encryptedargs;
    }


    /**
     * Return a list of identity providers to display on the login page.
     *
     * @param string|moodle_url $wantsurl The requested URL.
     * @return array List of arrays with keys url, iconurl and name.
     */
    public function loginpage_idp_list($wantsurl) {
        global $CFG;

        // Secret key is empty.
        if (empty($this->config->wploginenablebtn)) {
            return [];
        }

        if (empty($this->config->sharedsecret)) {
            return [];
        }

        // WP URL is not a valid URL.
        if (!filter_var($this->config->wpsiteurl, FILTER_VALIDATE_URL)) {
            return [];
        }

        $wpsiteurl = strtok($this->config->wpsiteurl, '?');

        // All conditions are passed.
        $args = [
            'mdl_key' => $this->config->sharedsecret,
        ];

        $encryptedargs = self::wdm_get_encrypted_query_args($args, $this->config->sharedsecret );

        $url  = $wpsiteurl .'?wdmaction=login_with_moodle&data=' . $encryptedargs;
        $url = new moodle_url( $url, ['installdepx' => 1, 'confirminstalldep' => 1]);

        if (!empty($this->config->wploginbtntext) ) {
            $text = $this->config->wploginbtntext;
        } else {
            $text = get_string('WordPress', 'auth_edwiserbridge');
        }

        if (isset($this->config->wploginbtnicon) && !empty($this->config->wploginbtnicon)) {
            $iconurl = moodle_url::make_pluginfile_url(
                context_system::instance()->id,
                'auth_edwiserbridge',
                'wploginbtnicon',
                0,
                '',
                $this->config->wploginbtnicon
            );
        } else {
            // Load default icon from pix folder.
            $iconurl = $CFG->wwwroot . '/auth/edwiserbridge/pix/wp-logo.png';
        }

        $result[] = ['url' => $url, 'iconurl' => $iconurl, 'name' => $text ];

        return $result;
    }
}
