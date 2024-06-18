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
 * Get Edwiser plugins info.
 * Functionality to get Edwiser plugins info installed on Moodle.
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
 * Trait implementing the external function auth_edwiserbridge_course_progress_data
 */
trait get_edwiser_plugins_info {

    /**
     * functionality to link existing services.
     * @return array
     */
    public static function auth_edwiserbridge_get_edwiser_plugins_info() {

        // Validation for context is needed.
        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        
        $response    = [];
        $pluginman   = \core_plugin_manager::instance();

        $authplugin = $pluginman->get_plugins_of_type('auth');
        if (isset($authplugin['edwiserbridge'])) {
            $plugins[] = [
                'plugin_name' => 'moodle_edwiser_bridge',
                'version'     => $authplugin['edwiserbridge']->release,
            ];
        }

        // Check licensing.
        global $CFG;
        $license = new auth_edwiserbridge\eb_pro_license_controller();
        if ($license->get_data_from_db() == 'available') {
            $plugins[] = [
                'plugin_name' => 'moodle_edwiser_bridge_pro',
                'version'     => 'available',
            ];
        } else {
            $plugins[] = [
                'plugin_name' => 'moodle_edwiser_bridge_pro',
                'version'     => 'not_available',
            ];
        }

        $response['plugins'] = $plugins;

        return $response;
    }

    /**
     * paramters defined for get plugin info function.
     */
    public static function auth_edwiserbridge_get_edwiser_plugins_info_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * paramters which will be returned from get plugin info function.
     */
    public static function auth_edwiserbridge_get_edwiser_plugins_info_returns() {
        return new external_single_structure(
            [
                'plugins' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'plugin_name' => new external_value(PARAM_TEXT, get_string('eb_plugin_name', 'auth_edwiserbridge')),
                            'version'     => new external_value(PARAM_TEXT, get_string('eb_plugin_version', 'auth_edwiserbridge')),
                        ]
                    )
                ),
            ]
        );
    }
}
