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

use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use core_completion\progress;

/**
 * Trait implementing the external function auth_edwiserbridge_setup_test_connection
 */
trait setup_test_connection {

    /**
     * Request to test connection
     *
     * @param  string $wpurl   wpurl.
     * @param  string $wptoken wptoken.
     *
     * @return array
     */
    public static function auth_edwiserbridge_setup_test_connection($wpurl) {

        $params = self::validate_parameters(
            self::auth_edwiserbridge_setup_test_connection_parameters(),
            [
                'wp_url' => $wpurl,
            ]
        );

        $status = 0;
        $msg    = get_string('setup_test_conn_error', 'auth_edwiserbridge');

        $requesturl = $params["wp_url"] . '/wp-json';

        $curl = curl_init();
        curl_setopt_array(
            $curl,
            [
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL            => $requesturl,
                CURLOPT_TIMEOUT        => 100,
            ]
        );

        global $CFG;
        $useragent = 'Moodle/' . $CFG->version . ' (' . $CFG->wwwroot . ') Edwiser Bridge Moodle Server';
        curl_setopt($curl, CURLOPT_USERAGENT, $useragent);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // Skip SSL Verification.

        $response = curl_exec($curl);

        json_decode($response);

        if (json_last_error() == JSON_ERROR_NONE) {
            $status = 1;
            $msg    = get_string('setup_test_conn_succ', 'auth_edwiserbridge');
        }

        return ["status" => $status, "msg" => $msg];
    }

    /**
     * Request to test connection parameter.
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
     * paramters which will be returned from test connection function.
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
