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

require_once(__DIR__ . '/../../compat.php');

use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use core\context\system as context_system;

/**
 * Trait implementing the external function auth_edwiserbridge_get_mandatory_settings
 */
trait get_mandatory_settings {
    /**
     * Retrieves the mandatory settings for the Edwiser Bridge authentication plugin.
     *
     * This function fetches the necessary settings from the Moodle configuration and
     * returns them as an associative array. The settings include the REST protocol
     * status, web service status, password policy, extended character support, student
     * role ID, and the language code.
     *
     * @return array An associative array containing the mandatory settings.
     */
    public static function auth_edwiserbridge_get_mandatory_settings() {
        global $CFG;

        // Validation for context is needed.
        $systemcontext = context_system::instance();

        self::validate_context($systemcontext);
        
        require_capability('moodle/site:config', $systemcontext);
        
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

        require_once($CFG->libdir . '/accesslib.php');

        // Get Role ID of student role.
        global $DB;

        // Get roles where the archetype is defined.
        $studentroles = $DB->get_records('role', ['archetype' => 'student']);
        if ($studentroles) {
            // Assuming the first role in the list is the one we want
            $studentroleid = current($studentroles)->id;
        } else {
            // Handle the case where no 'student' archetype role is found
            $studentroleid = null;
            debugging('Student role archetype not found in the system.', DEBUG_DEVELOPER);
        }
        $settings['student_role_id'] = $studentroleid;


        // Get lang_code settings.
        $settings['lang_code'] = $CFG->lang;

        return $settings;

    }

    /**
     * Returns the parameters for the auth_edwiserbridge_get_mandatory_settings external function.
     *
     * This function does not take any parameters, as it is used to retrieve the mandatory settings
     * for the Edwiser Bridge authentication plugin.
     *
     * @return external_function_parameters The parameters for the external function.
     */
    public static function auth_edwiserbridge_get_mandatory_settings_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * Returns the structure of the mandatory settings for the Edwiser Bridge authentication plugin.
     *
     * This function is used to define the structure of the settings that will be returned by the
     * auth_edwiserbridge_get_mandatory_settings external function. It includes settings such as
     * the REST protocol, web service, password policy, language code, and student role ID.
     *
     * @return external_single_structure The structure of the mandatory settings.
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
