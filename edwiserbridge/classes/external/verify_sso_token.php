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
 * Verify SSO token.
 * Functionality to verify SSO token.
 *
 * @package    auth_edwiserbridge
 * @category   external
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_edwiserbridge\external;

use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use core\context\system as context_system;

/**
 * Trait implementing the external function auth_edwiserbridge_verify_sso_token
 */
trait verify_sso_token {

    /**
     * Returns the external function parameters for the auth_edwiserbridge_verify_sso_token function.
     *
     * @return external_function_parameters The function parameters.
     * @since SSO 1.2.1
     */
    public static function auth_edwiserbridge_verify_sso_token_parameters() {
        return new external_function_parameters([
            'token' => new external_value(
                PARAM_TEXT,
                get_string('wp_test_connection_token', 'auth_edwiserbridge'),
                VALUE_REQUIRED,
                null,
                NULL_NOT_ALLOWED
            )
        ]);
    }
    /**
     * Verifies the provided SSO token.
     *
     * @param string $token The token to verify.
     * @return array An array containing the success status and a message.
     */
    public static function auth_edwiserbridge_verify_sso_token($token) {

        // Validation for context is needed.
        $systemcontext = context_system::instance();

        self::validate_context($systemcontext);

        require_capability('moodle/site:config', $systemcontext);
        
        $params = self::validate_parameters(
            self::auth_edwiserbridge_verify_sso_token_parameters(),
            ['token' => $token]
        );
        $responce = ['success' => false, 'msg' => get_string('invalid_sso_token_err', 'auth_edwiserbridge')];
        $secretkey = get_config('auth_edwiserbridge', 'sharedsecret');
        if ($params['token'] == $secretkey) {
            $responce['success'] = true;
            $responce['msg'] = get_string('valid_sso_token_success', 'auth_edwiserbridge');
        }

        return $responce;
    }

    /**
     * Returns the external function return structure for the auth_edwiserbridge_verify_sso_token function.
     *
     * @return external_single_structure The function return structure.
     * @since SSO 1.2.1
     */
    public static function auth_edwiserbridge_verify_sso_token_returns() {
        return new external_single_structure(
            [
                'success' => new external_value(PARAM_BOOL, get_string('success', 'auth_edwiserbridge')),
                'msg'     => new external_value(PARAM_RAW, get_string('success_error_msg', 'auth_edwiserbridge')),
            ]
        );
    }
}
