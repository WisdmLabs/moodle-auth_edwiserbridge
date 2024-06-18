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
 * Enable plugin settings.
 * Functionality to enable mandatory plugin settings.
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
 * Trait implementing the external function auth_edwiserbridge_enable_plugin_settings
 */
trait enable_plugin_settings {

    /**
     * Parameter description of auth_edwiserbridge_enable_plugin_settings() parameters
     *
     * @return external_function_parameters
     */
    public static function auth_edwiserbridge_enable_plugin_settings_parameters() {
        return new external_function_parameters([]);

    }

    /**
     * Get list of active course enrolment methods for current user.
     *
     * @param int $courseid
     * @return array of course enrolment methods
     * @throws moodle_exception
     */
    public static function auth_edwiserbridge_enable_plugin_settings() {
        global $DB, $CFG;

        // Validation for context is needed.
        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        
        $activewebservices[] = 'rest';

        set_config('webserviceprotocols', implode(',', $activewebservices));
        set_config('enablewebservices', 1);
        set_config('extendedusernamechars', 1);
        set_config('passwordpolicy', 0);

        $response = [
            'rest_protocol' => 1,
            'web_service' => 1,
            'disable_password' => 1,
            'allow_extended_char' => 1,
            'lang_code' => $CFG->lang,
        ];

        return $response;

    }

    /**
     * Returns description of auth_edwiserbridge_enable_plugin_settings() result value
     *
     * @return external_description
     */
    public static function auth_edwiserbridge_enable_plugin_settings_returns() {

        return new external_single_structure(
            [
                'rest_protocol' => new external_value(
                    PARAM_TEXT,
                    get_string('web_service_rest_protocol', 'auth_edwiserbridge')
                ),
                'web_service' => new external_value(
                    PARAM_RAW,
                    get_string('web_service_web_service', 'auth_edwiserbridge')
                ),
                'disable_password' => new external_value(
                    PARAM_RAW,
                    get_string('web_service_password_policy', 'auth_edwiserbridge')
                ),
                'allow_extended_char' => new external_value(
                    PARAM_RAW,
                    get_string('web_service_extended_char', 'auth_edwiserbridge')
                ),
                'lang_code' => new external_value(
                    PARAM_RAW,
                    get_string('web_service_lang_code', 'auth_edwiserbridge')
                ),
            ]
        );
    }
}
