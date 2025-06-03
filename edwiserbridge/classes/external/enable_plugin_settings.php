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

require_once(__DIR__ . '/../../compat.php');

use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use core\context\system as context_system;

/**
 * Trait implementing the external function auth_edwiserbridge_enable_plugin_settings
 */
trait enable_plugin_settings {
    /**
     * Returns the parameter description of the auth_edwiserbridge_enable_plugin_settings() function.
     *
     * @return external_function_parameters The parameter description.
     */
    public static function auth_edwiserbridge_enable_plugin_settings_parameters() {
        return new external_function_parameters([]);

    }

    /**
     * Enables the mandatory plugin settings for the Edwiser Bridge authentication plugin.
     *
     * This function performs the following actions:
     * - Validates the system context
     * - Ensures the REST web service protocol is enabled
     * - Enables the web services feature
     * - Disables the password policy
     * - Allows extended user name characters
     * - Returns an array of the enabled settings
     *
     * @return array An array containing the enabled plugin settings
     */
    public static function auth_edwiserbridge_enable_plugin_settings() {
        global $CFG;

        // Validation for context is needed.
        $systemcontext = context_system::instance();
        self::validate_context($systemcontext);
        
        require_capability('moodle/site:config', $systemcontext);
        
        // Call the function to get the list of protocols
        $activewebservices = explode(',', $CFG->webserviceprotocols);

        if (empty($activewebservices) || ! in_array('rest', $activewebservices)) {
            $activewebservices[] = 'rest';
        }

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
     * Returns the description of the result value for the auth_edwiserbridge_enable_plugin_settings() function.
     *
     * @return external_single_structure The description of the result value.
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
