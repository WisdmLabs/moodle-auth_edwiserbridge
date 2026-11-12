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

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../compat.php');

use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core\context\system as context_system;

/**
 * Trait implementing the external function auth_edwiserbridge_validate_token
 */
trait validate_token {
    /**
     * Validates the WordPress token against the stored connection settings.
     *
     * @param string $wpurl WordPress site URL.
     * @param string $wptoken WordPress token to validate.
     * @return array Array with token_mismatch and is_authorized flags.
     */
    public static function auth_edwiserbridge_validate_token($wpurl, $wptoken) {
        // Validation for context is needed.
        $systemcontext = context_system::instance();
        self::validate_context($systemcontext);
        require_capability('moodle/site:config', $systemcontext);

        $params = self::validate_parameters(
            self::auth_edwiserbridge_validate_token_parameters(),
            [
                'wp_url' => $wpurl,
                "wp_token" => $wptoken,
            ]
        );

        $defaultvalues = auth_edwiserbridge_get_connection_settings();

        $isauthorized = false;
        $tokenmatch = false;
        foreach ($defaultvalues['eb_connection_settings'] as $site => $value) {
            if ($value['wp_token'] == $params["wp_token"]) {
                $tokenmatch = true;
            }
        }
        // Get user id by token.
        global $DB, $CFG;
        $userid = $DB->get_field('external_tokens', 'userid', ['token' => $params["wp_token"]]);
        $userroles = get_user_roles($systemcontext, $userid);
        $managerid = $DB->get_field('role', 'id', ['archetype' => 'manager']);
        if (! empty($userroles)) {
            foreach ($userroles as $role) {
                $roleid = $role->roleid;
                if ($roleid == $managerid) {
                    $isauthorized = true;
                    break;
                }
            }
        }
        $siteadmins = $CFG->siteadmins;

        if (in_array($userid, explode(',', $siteadmins))) {
            $isauthorized = true;
        }

        return [ "token_mismatch" => ! $tokenmatch, 'is_authorized' => $isauthorized ];
    }


    /**
     * Returns the parameters for the auth_edwiserbridge_validate_token external function.
     *
     * @return external_function_parameters The parameters for the function.
     */
    public static function auth_edwiserbridge_validate_token_parameters() {
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
        ]);
    }

    /**
     * Returns the structure of the validate token response.
     *
     * @return external_single_structure The structure of the response.
     */
    public static function auth_edwiserbridge_validate_token_returns() {
        return new external_single_structure(
            [
                'token_mismatch' => new external_value(
                    PARAM_BOOL,
                    get_string('web_service_validate_token_msg', 'auth_edwiserbridge'),
                    VALUE_OPTIONAL
                ),
                'is_authorized' => new external_value(
                    PARAM_BOOL,
                    get_string('web_service_validate_user_msg', 'auth_edwiserbridge'),
                    VALUE_OPTIONAL
                ),
            ]
        );
    }
}
