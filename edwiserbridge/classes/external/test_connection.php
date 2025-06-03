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
 * Test connection.
 * Functionality to test wordpress and moodle connection.
 *
 * @package    auth_edwiserbridge
 * @category   external
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_edwiserbridge\external;

require_once(__DIR__ . '/../../compat.php');

use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core\context\system as context_system;

/**
 * Trait implementing the external function auth_edwiserbridge_test_connection
 */
trait test_connection {

    /**
     * Request to test connection
     *
     * @param string $wpurl   wpurl.
     * @param string $wptoken wptoken.
     * @param string $testconnection Test connection type, defaults to "moodle".
     *
     * @return array
     */
    public static function auth_edwiserbridge_test_connection($wpurl, $wptoken, $testconnection = "moodle") {

        
        // Validation for context is needed.
        $systemcontext = context_system::instance();
        self::validate_context($systemcontext);
        require_capability('moodle/site:config', $systemcontext);
        
        $params = self::validate_parameters(
            self::auth_edwiserbridge_test_connection_parameters(),
            [
                'wp_url' => $wpurl,
                "wp_token" => $wptoken,
                "test_connection" => $testconnection,
            ]
        );

        if ($params["test_connection"] == "wordpress") {
            $msg = get_string('wp_test_connection_success', 'auth_edwiserbridge');
            $warnings = [];

            $defaultvalues = auth_edwiserbridge_get_connection_settings();

            $tokenmatch = false;
            foreach ($defaultvalues['eb_connection_settings'] as $site => $value) {
                if ($value['wp_token'] == $params["wp_token"]) {
                    $tokenmatch = true;
                }
            }
            if (!$tokenmatch) {
                $warnings[] = get_string('wp_test_connection_token_mismatch', 'auth_edwiserbridge');
            }
            // Get webservice id by token.
            global $DB;
            $serviceid = $DB->get_field('external_tokens', 'externalserviceid', ['token' => $params["wp_token"]]);
            $count = auth_edwiserbridge_get_service_list($serviceid);
            if ($count == 1) {
                $status = 0;
                $msg = $count . ' ' .  get_string('wp_test_connection_function_missing', 'auth_edwiserbridge');
            } else if ($count > 1) {
                $status = 0;
                $msg = $count . ' ' . get_string('wp_test_connection_functions_missing', 'auth_edwiserbridge');
            } else {
                $status = 1;
            }
            return ["status" => $status, "msg" => $msg, "warnings" => $warnings];
        } else {
            $requestdata = [
                'action'     => "test_connection",
                'secret_key' => $params["wp_token"],
            ];

            $apihandler = auth_edwiserbridge_api_handler_instance();
            $response   = $apihandler->connect_to_wp_with_args($params["wp_url"], $requestdata);
            $status = 0;
            $msg    = isset($response["msg"]) ? $response["msg"] : get_string('check_wp_site_config', 'auth_edwiserbridge');

            if (!$response["error"] && isset($response["data"]->msg) &&
                    isset($response["data"]->status) && $response["data"]->status
            ) {
                $status = $response["data"]->status;
                $msg = $response["data"]->msg;
                if (!$status) {
                    $msg = $response["data"]->msg . get_string('wp_test_connection_failed', 'auth_edwiserbridge');
                }
            } else {
                // Test connection error messages.
                // 1. Wrong token don't show detailed message.
                // 2. Redirection or other isues will show detailed error message.
                if (isset($response["data"]->msg)) {
                    $servermsg = $response["data"]->msg;
                } else {
                    $servermsg = get_string('check_wp_site_config', 'auth_edwiserbridge');
                }

                $msg = '<div>
                            <div class="eb_connection_short_msg">
                                ' . get_string('test_connection_fail_err_1', 'auth_edwiserbridge') . '
                                <span class="eb_test_connection_log_open"> ' . get_string('test_connection_fail_err_close_link', 'auth_edwiserbridge') . ' </span>.
                            </div>
                            <div class="eb_test_connection_log">
                                <div style="display:flex;">
                                    <div class="eb_connection_err_response">
                                        <h4> ' . get_string('test_connection_fail_err_2', 'auth_edwiserbridge') . ' </h4>
                                        <div>' . get_string('test_connection_fail_err_3', 'auth_edwiserbridge') . '</div>
                                        <div>' . get_string('test_connection_fail_url', 'auth_edwiserbridge') . $params['wp_url'] .'/wp-json/edwiser-bridge/wisdmlabs/</div>
                                        <div>' . get_string('test_connection_fail_response', 'auth_edwiserbridge') . $servermsg .'</div>
                                        <div>' . get_string('test_connection_fail_next', 'auth_edwiserbridge', $params['wp_url'] . "/wp-admin/admin.php?page=eb-settings&tab=connection") . '</div>
                                    </div>
                                    <div class="eb_admin_templ_dismiss_notice_message">
                                        <span class="eb_test_connection_log_close " style="color:red;"> X </span>
                                    </div>
                                </div>
                            </div>
                        </div>';
            }

            return ["status" => $status, "msg" => $msg, "warnings" => []];
        }
    }

    /**
     * Defines the parameters for the 'auth_edwiserbridge_test_connection' web service function.
     *
     * This function returns an external_function_parameters object that defines the parameters
     * for the 'auth_edwiserbridge_test_connection' web service function. The parameters include:
     *
     * - 'wp_url': The URL of the WordPress site to test the connection with.
     * - 'wp_token': The token used to authenticate the connection to the WordPress site.
     * - 'test_connection': The text to be used for the 'test_connection' parameter, with a default value of 'moodle'.
     *
     * @return external_function_parameters The parameters for the 'auth_edwiserbridge_test_connection' web service function.
     */
    public static function auth_edwiserbridge_test_connection_parameters() {
        return new external_function_parameters([
            'wp_url' => new external_value(
                PARAM_URL,
                get_string('web_service_wp_url', 'auth_edwiserbridge'),
                VALUE_REQUIRED
            ),
            'wp_token' => new external_value(
                PARAM_TEXT,
                get_string('web_service_wp_token', 'auth_edwiserbridge'),
                VALUE_REQUIRED,
                null,
                NULL_NOT_ALLOWED
            ),
            'test_connection' => new external_value(
                PARAM_TEXT,
                get_string('web_service_test_conn', 'auth_edwiserbridge'),
                VALUE_DEFAULT,
                'moodle'
            )
        ]);
    }

    /**
     * Defines the return parameters for the 'auth_edwiserbridge_test_connection' web service function.
     *
     * This function returns an external_single_structure object that defines the parameters
     * that will be returned by the 'auth_edwiserbridge_test_connection' web service function.
     * The returned parameters include:
     *
     * - 'status': The status of the connection test, as a text value.
     * - 'msg': The message returned from the connection test, as a raw value.
     * - 'warnings': An optional array of warning messages, as text values.
     *
     * @return external_single_structure The return parameters for the 'auth_edwiserbridge_test_connection' web service function.
     */
    public static function auth_edwiserbridge_test_connection_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(
                    PARAM_TEXT,
                    get_string('web_service_test_conn_status', 'auth_edwiserbridge'),
                    VALUE_OPTIONAL
                ),
                'msg' => new external_value(
                    PARAM_RAW,
                    get_string('web_service_test_conn_msg', 'auth_edwiserbridge'),
                    VALUE_OPTIONAL
                ),
                'warnings' => new external_multiple_structure(
                    new external_value(
                        PARAM_TEXT,
                        get_string('web_service_test_conn_warning', 'auth_edwiserbridge')
                    ),
                    VALUE_OPTIONAL
                ),
            ]
        );
    }
}
