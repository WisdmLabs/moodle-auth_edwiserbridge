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
 * Edwiser Bridge - WordPress and Moodle integration.
 * This file is responsible for WordPress connection related functionality.
 *
 * @package    auth_edwiserbridge
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_edwiserbridge\local;
/**
 * Handles API requests and response from WordPress.
 */
class api_handler {
    /**
     * Returns the singleton instance of the api_handler class.
     *
     * @return object self object.
     */
    protected static $instance = null;

    /**
     * Returns the singleton instance of the api_handler class.
     *
     * @return object self object.
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Connects to WordPress with the provided request URL and data.
     *
     * @param string $requesturl The URL for the WordPress API request.
     * @param array $requestdata The data to be sent in the WordPress API request.
     * @return array An array containing the response data or an error message.
     */
    public function connect_to_wp_with_args($requesturl, $requestdata) {
        global $CFG;
        include_once($CFG->libdir . '/filelib.php'); // Include Moodle's curl class.

        $requesturl .= '/wp-json/edwiser-bridge/wisdmlabs/';

        // Create an instance of Moodle's curl class.
        $curl = new \curl();

        // Construct the User-Agent string.
        $useragent = 'Moodle/' . $CFG->version . ' (' . $CFG->wwwroot . ') Edwiser Bridge Moodle Server';

        // Set headers.
        $curl->setHeader('User-Agent: ' . $useragent);

        // Set additional options.
        $options = [
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_TIMEOUT' => 100,
            'CURLOPT_SSL_VERIFYPEER' => true, // Enforce SSL verification.
        ];

        // Execute the POST request.
        $response = $curl->post($requesturl, $requestdata, $options);

        // Get the HTTP status code.
        $statuscode = $curl->info['http_code'];

        // Check for errors.
        if ($response === false) {
            $errormsg = $curl->error;
            return ["error" => 1, "msg" => $errormsg];
        } else {
            if ("200" == $statuscode) {
                return ["error" => 0, "data" => json_decode($response)];
            } else {
                $msg = get_string("default_error", "auth_edwiserbridge");
                // Check if response is html.
                if ($response != strip_tags($response)) {
                    $msg = get_string('wp_site_error', 'auth_edwiserbridge');
                }
                if (strpos($response, "BitNinja") !== false || strpos($response, "Security check by BitNinja.IO") !== false) {
                    $msg = get_string('bitninja_error', 'auth_edwiserbridge');
                }
                if (strpos($response, "Cloudflare Ray ID") !== false) {
                    $msg = get_string('cloudflare_error', 'auth_edwiserbridge');
                }
                if (strpos($response, "Mod_Security") !== false) {
                    $msg = get_string('modsecurity_error', 'auth_edwiserbridge');
                }
                return ["error" => 1, "msg" => $msg];
            }
        }
    }
}
