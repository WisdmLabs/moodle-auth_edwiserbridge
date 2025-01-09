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
 * Provides auth_edwiserbridge\external\course_progress_data trait.
 *
 * @package     auth_edwiserbridge
 * @category    external
 * @copyright   2021 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Wisdmlabs
 */

namespace auth_edwiserbridge\external;

defined('MOODLE_INTERNAL') || die();

use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use core_completion\progress;

// require_once($CFG->libdir.'/externallib.php');

/**
 * Trait implementing the external function auth_edwiserbridge_course_progress_data
 */
trait eb_validate_token {

    /**
     * Request to test connection
     *
     * @param  string $wpurl   wpurl.
     * @param  string $wptoken wptoken.
     *
     * @return array
     */
    public static function eb_validate_token($wpurl, $wptoken) {
        $params = self::validate_parameters(
            self::eb_validate_token_parameters(),
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

        // get webservice id by token 
        global $DB, $CFG;
        $userid = $DB->get_field('external_tokens', 'userid', array('token' => $params["wp_token"]));
        $roleid = $DB->get_records('role_assignments', ['userid' => $userid]);
        $manager_id = $DB->get_field('role', 'id', ['archetype' => 'manager']);
        // $roleid = $DB->get_field('mdl_role', 'archetype', ['id' => $roleid]);
        if ( ! empty($roleid) ) {
            foreach ($roleid as $role) {
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

    /**
     * Request to test connection parameter.
     */
    public static function eb_validate_token_parameters() {
        return new external_function_parameters(
            array(
                'wp_url'          => new external_value(PARAM_TEXT, get_string('web_service_wp_url', 'auth_edwiserbridge')),
                'wp_token'        => new external_value(PARAM_TEXT, get_string('web_service_wp_token', 'auth_edwiserbridge')),
            )
        );
    }

    /**
     * paramters which will be returned from test connection function.
     */
    public static function eb_validate_token_returns() {
        return new external_single_structure(
            array(
                'token_mismatch'    => new external_value(PARAM_BOOL, get_string('web_service_validate_token_msg', 'auth_edwiserbridge')), 
                'is_authorized'     => new external_value(PARAM_BOOL, get_string('web_service_validate_user_msg', 'auth_edwiserbridge')), 
            )
        );
    }
}
