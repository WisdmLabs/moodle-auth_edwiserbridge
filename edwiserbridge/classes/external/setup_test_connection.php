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
 * Setup Wizard Test Connection.
 * Functionality to test connection in setup wizard.
 *
 * @package    auth_edwiserbridge
 * @category   external
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_edwiserbridge\external;

require_once(__DIR__ . '/../../compat.php');

use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use core\context\system as context_system;

/**
 * Trait implementing the external function auth_edwiserbridge_setup_test_connection
 */
trait setup_test_connection {

    /**
     * Request to test connection
     *
     * @param string $wpurl WordPress URL to test the connection against.
     * @return array An array containing the status and message of the connection test.
     */
    public static function auth_edwiserbridge_setup_test_connection($wpurl) {
        global $CFG;
        
        include_once($CFG->libdir . '/filelib.php'); // Include Moodle's curl class.  
        
        // Validation for context is needed.
        $systemcontext = context_system::instance();
        self::validate_context($systemcontext);
        require_capability('moodle/site:config', $systemcontext);

        $params = self::validate_parameters(
            self::auth_edwiserbridge_setup_test_connection_parameters(),
            [
                'wp_url' => $wpurl,
            ]
        );

        $status = 0;
        $msg    = get_string('setup_test_conn_error', 'auth_edwiserbridge');

        $requesturl = $params["wp_url"] . '/wp-json';

        // Use Moodle's curl class.
        $curl = new \curl();

        // Construct the User-Agent string.
        global $CFG;
        $useragent = 'Moodle/' . $CFG->version . ' (' . $CFG->wwwroot . ') Edwiser Bridge Moodle Server';

        // Set custom headers.
        $curl->setHeader('User-Agent: ' . $useragent);

        // Set additional options.
        $options = [
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_TIMEOUT' => 100,
            'CURLOPT_SSL_VERIFYPEER' => false, // Skip SSL verification.
        ];

        // Execute a GET request.
        $response = $curl->get($requesturl, [], $options);

        // Decode the response.
        $response_data = json_decode($response);

        // Check if the response is valid JSON.
        if (json_last_error() == JSON_ERROR_NONE) {
            $status = 1;
            $msg    = get_string('setup_test_conn_succ', 'auth_edwiserbridge');
        }

        return ["status" => $status, "msg" => $msg];

    }

    /**
     * Defines the parameters for the auth_edwiserbridge_setup_test_connection function.
     *
     * This function returns the parameters required for the auth_edwiserbridge_setup_test_connection
     * function, which is used to test the connection to the WordPress site.
     *
     * @return external_function_parameters The parameters for the auth_edwiserbridge_setup_test_connection function.
     */
    public static function auth_edwiserbridge_setup_test_connection_parameters() {
        return new external_function_parameters(
            [
                'wp_url' => new external_value(
                    PARAM_RAW,
                    get_string('web_service_wp_url',
                    'auth_edwiserbridge')
                ),
            ]
        );
    }

    /**
     * Returns the parameters that will be returned from the test connection function.
     *
     * This function defines the structure of the return value for the
     * auth_edwiserbridge_setup_test_connection function, which is used to test the
     * connection to the WordPress site. The return value includes a status and a
     * message.
     *
     * @return external_single_structure The parameters that will be returned from the test connection function.
     */
    public static function auth_edwiserbridge_setup_test_connection_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(
                    PARAM_TEXT,
                    get_string('web_service_test_conn_status', 'auth_edwiserbridge')
                ),
                'msg' => new external_value(
                    PARAM_RAW,
                    get_string('web_service_test_conn_msg', 'auth_edwiserbridge')
                ),
            ]
        );
    }
}
