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
 * Get site specific synch settings.
 * Functionality to get site specific synchrnoization settings.
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
 * Trait implementing the external function auth_edwiserbridge_get_site_data
 */
trait get_site_data {

    /**
     * functionality to get all site related data.
     * @param  string $siteindex siteindex
     * @return array
     */
    public static function auth_edwiserbridge_get_site_data($siteindex) {

        // Validation for context is needed.
        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        
        $params = self::validate_parameters(
            self::auth_edwiserbridge_get_site_data_parameters(),
            ['site_index' => $siteindex]
        );
        return auth_edwiserbridge_get_synch_settings($params['site_index']);
    }

    /**
     * paramters defined for get site data function.
     */
    public static function auth_edwiserbridge_get_site_data_parameters() {
        return new external_function_parameters(
            [
                'site_index' => new external_value(
                    PARAM_TEXT,
                    get_string('web_service_site_index', 'auth_edwiserbridge')
                ),
            ]
        );
    }

    /**
     * paramters which will be returned from get site data function.
     */
    public static function auth_edwiserbridge_get_site_data_returns() {
        return new external_single_structure(
            [
                'course_enrollment'    => new external_value(
                    PARAM_INT,
                    get_string('web_service_course_enrollment', 'auth_edwiserbridge')
                ),
                'course_un_enrollment' => new external_value(
                    PARAM_INT,
                    get_string('web_service_course_un_enrollment', 'auth_edwiserbridge')
                ),
                'user_creation'        => new external_value(
                    PARAM_INT,
                    get_string('web_service_user_creation', 'auth_edwiserbridge')
                ),
                'user_updation'        => new external_value(
                    PARAM_INT,
                    get_string('web_service_user_update', 'auth_edwiserbridge')
                ),
                'user_deletion'        => new external_value(
                    PARAM_INT,
                    get_string('web_service_user_deletion', 'auth_edwiserbridge')
                ),
                'course_creation'        => new external_value(
                    PARAM_INT,
                    get_string('web_service_course_creation', 'auth_edwiserbridge')
                ),
                'course_deletion'        => new external_value(
                    PARAM_INT,
                    get_string('web_service_course_deletion', 'auth_edwiserbridge')
                ),
            ]
        );
    }
}
