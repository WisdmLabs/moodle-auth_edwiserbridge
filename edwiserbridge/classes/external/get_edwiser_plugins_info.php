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

require_once(__DIR__ . '/../../compat.php');

use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core\context\system as context_system;
// use core\plugin_manager as core_plugin_manager;

/**
 * Trait implementing the external function auth_edwiserbridge_course_progress_data
 */
trait get_edwiser_plugins_info {
    /**
     * Retrieves information about Edwiser plugins installed on the Moodle site.
     *
     * This function checks the installed authentication plugins and the Edwiser Bridge Pro
     * license status to gather information about the Edwiser plugins. It returns an array
     * containing the plugin names and versions.
     *
     * @return array An array with information about the installed Edwiser plugins.
     */
    public static function auth_edwiserbridge_get_edwiser_plugins_info() {

        
        // Validation for context is needed.
        $systemcontext = context_system::instance();
        self::validate_context($systemcontext);
        require_capability('moodle/site:config', $systemcontext);
        
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
        $license = new \auth_edwiserbridge\local\eb_pro_license_controller();
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
     * Returns the parameters for the auth_edwiserbridge_get_edwiser_plugins_info function.
     *
     * This function does not take any parameters, as the function it documents
     * retrieves information about the installed Edwiser plugins without requiring
     * any input from the caller.
     *
     * @return external_function_parameters The parameters for the
     *         auth_edwiserbridge_get_edwiser_plugins_info function.
     */
    public static function auth_edwiserbridge_get_edwiser_plugins_info_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * Returns the structure of the response for the auth_edwiserbridge_get_edwiser_plugins_info function.
     *
     * This function defines the structure of the response that will be returned by the
     * auth_edwiserbridge_get_edwiser_plugins_info function. It specifies that the response
     * will be a single structure containing a 'plugins' field, which is a multiple structure
     * containing individual plugin information with 'plugin_name' and 'version' fields.
     *
     * @return external_single_structure The structure of the response for the
     *         auth_edwiserbridge_get_edwiser_plugins_info function.
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
