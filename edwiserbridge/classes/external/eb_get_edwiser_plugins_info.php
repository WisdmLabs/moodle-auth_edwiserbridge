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
trait eb_get_edwiser_plugins_info {

    /**
     * functionality to link existing services.
     * @return array
     */
    public static function eb_get_edwiser_plugins_info() {
        $response    = array();
        $pluginman   = \core_plugin_manager::instance();

        $authplugin = $pluginman->get_plugins_of_type('auth');
        if (isset($authplugin['edwiserbridge'])) {
            $plugins[] = array(
                'plugin_name' => 'moodle_edwiser_bridge',
                'version'     => $authplugin['edwiserbridge']->release,
            );
        }

        // check licensing
        global $CFG;
        require_once($CFG->dirroot . '/auth/edwiserbridge/classes/class-eb-pro-license_controller.php');
        $license = new \eb_pro_license_controller();
        if($license->get_data_from_db() == 'available'){
            $plugins[] = array(
                'plugin_name' => 'moodle_edwiser_bridge_pro',
                'version'     => 'available',
            );
        } else {
            $plugins[] = array(
                'plugin_name' => 'moodle_edwiser_bridge_pro',
                'version'     => 'not_available',
            );
        }

        $response['plugins'] = $plugins;

        return $response;
    }

    /**
     * paramters defined for get plugin info function.
     */
    public static function eb_get_edwiser_plugins_info_parameters() {
        return new external_function_parameters(array());
    }

    /**
     * paramters which will be returned from get plugin info function.
     */
    public static function eb_get_edwiser_plugins_info_returns() {
        return new external_single_structure(
            array(
                'plugins' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'plugin_name' => new external_value(PARAM_TEXT, get_string('eb_plugin_name', 'auth_edwiserbridge')),
                            'version'     => new external_value(PARAM_TEXT, get_string('eb_plugin_version', 'auth_edwiserbridge')),
                        )
                    )
                ),
            )
        );
    }
}
