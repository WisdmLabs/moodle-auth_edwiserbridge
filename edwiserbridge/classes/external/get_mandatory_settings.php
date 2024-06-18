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
 * Get mandatory settings.
 * Functionality to get mandatory settings.
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
 * Trait implementing the external function auth_edwiserbridge_get_mandatory_settings
 */
trait get_mandatory_settings {

    /**
     * Request to test connection
     *
     * @param  string $wpurl   wpurl.
     * @param  string $wptoken wptoken.
     *
     * @return array
     */
    public static function auth_edwiserbridge_get_mandatory_settings() {
        global $CFG, $DB;

        // Validation for context is needed.
        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        
        $settings = [];
        // Get all settings and form array.
        $protocols = $CFG->webserviceprotocols;

        // Get rest_protocol settings.
        if ( in_array( 'rest', explode(',', $protocols) ) ) {
            $settings['rest_protocol'] = 1;
        } else {
            $settings['rest_protocol'] = 0;
        }

        // Get web_service settings.
        $settings['web_service'] = $CFG->enablewebservices;

        // Get password policy settings.
        $settings['password_policy'] = $CFG->passwordpolicy;

        // Get allow_extended_char settings.
        $settings['allow_extended_char'] = $CFG->extendedusernamechars;

        $studentroleid = $DB->get_record('role', ['shortname' => 'student'])->id;

        $settings['student_role_id'] = $studentroleid;

        // Get lang_code settings.
        $settings['lang_code'] = $CFG->lang;

        return $settings;

    }

    /**
     * Request to test connection parameter.
     */
    public static function auth_edwiserbridge_get_mandatory_settings_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * paramters which will be returned from test connection function.
     */
    public static function auth_edwiserbridge_get_mandatory_settings_returns() {
        return new external_single_structure(
            [
                'rest_protocol' => new external_value(
                    PARAM_TEXT, get_string('web_service_rest_protocol', 'auth_edwiserbridge')
                ),
                'web_service' => new external_value(
                    PARAM_RAW,
                    get_string('web_service_web_service', 'auth_edwiserbridge')
                ),
                'allow_extended_char' => new external_value(
                    PARAM_RAW,
                    get_string('web_service_extended_char', 'auth_edwiserbridge')
                ),
                'password_policy' => new external_value(
                    PARAM_RAW,
                    get_string('web_service_password_policy', 'auth_edwiserbridge')
                ),
                'lang_code' => new external_value(
                    PARAM_RAW,
                    get_string('web_service_lang_code', 'auth_edwiserbridge')
                ),
                'student_role_id' => new external_value(
                    PARAM_RAW,
                    get_string('web_service_student_role_id', 'auth_edwiserbridge')
                ),
            ]
        );
    }
}
