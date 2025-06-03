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
 * Trait implementing the external function auth_edwiserbridge_validate_token
 */
trait validate_token {

    public static function auth_edwiserbridge_validate_token($wpurl, $wptoken) { 
        // Validation for context is needed.
        $systemcontext = context_system::instance();
        self::validate_context($systemcontext);
        require_capability('moodle/site:config', $systemcontext);
        
        $params = self::validate_parameters(
            self::auth_edwiserbridge_validate_token_parameters(),
            array(
                'wp_url' => $wpurl,
                "wp_token" => $wptoken,
            )
        );

        $defaultvalues = auth_edwiserbridge_get_connection_settings();

        $is_authorized = false;
        $token_match = false;
        foreach ($defaultvalues['eb_connection_settings'] as $site => $value) {
            if ($value['wp_token'] == $params["wp_token"]) {
                $token_match = true;
            }
        }
        // get user id by token 
        global $DB, $CFG;
        $userid = $DB->get_field('external_tokens', 'userid', array('token' => $params["wp_token"]));
        // $roleid = $DB->get_records('role_assignments', ['userid' => $userid]);
        $userroles = get_user_roles($systemcontext, $userid);
        $manager_id = $DB->get_field('role', 'id', ['archetype' => 'manager']);
        // $roleid = $DB->get_field('mdl_role', 'archetype', ['id' => $roleid]);
        if ( ! empty($userroles) ) {
            foreach ($userroles as $role) {
                $roleid = $role->roleid;
                if ( $roleid == $manager_id ) {
                    $is_authorized = true;
                    break;
                }
            }
        }
        $site_admins = $CFG->siteadmins;
        
        if ( in_array( $userid, explode(',', $site_admins) ) ) {
            $is_authorized = true;
        }   

        return array( "token_mismatch" => ! $token_match, 'is_authorized' => $is_authorized );
    }


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
