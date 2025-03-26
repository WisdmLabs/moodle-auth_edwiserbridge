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
 * Migration helper for Edwiser Bridge.
 *
 * @package    auth_edwiserbridge
 * @copyright  2024 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_edwiserbridge\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Class migration_helper handles data migration from serialized to JSON format
 */
class migration_helper {

    /**
     * Executes the migration of serialized data to JSON format
     *
     * @return bool True if migration successful, false otherwise
     */
    public function execute_migration() {
        global $CFG;
        $result = true;
        $result = $result && $this->migrate_connection_settings();
        $result = $result && $this->migrate_sync_settings();
        $result = $result && $this->migrate_global_settings();

        return $result;
    }

    /**
     * Migrates connection settings from serialized to JSON format
     *
     * @return bool Success status
     */
    protected function migrate_connection_settings() {
        global $CFG;
        if (!isset($CFG->eb_connection_settings) || empty($CFG->eb_connection_settings)) {
            return true;
        }
        $plugin_config = get_config('auth_edwiserbridge', 'eb_connection_settings');
        if (!empty($plugin_config)) {
            return true;
        } 
        $settings = json_decode($CFG->eb_connection_settings, true);
        if ( JSON_ERROR_NONE === json_last_error() ) {
            return true;
        }
        list($success, $data) = $this->convert_serialized_to_json($CFG->eb_connection_settings);
        if ($success) {
            set_config( 'eb_connection_settings', $data, 'auth_edwiserbridge' );
            return true;
        }
        debugging('Connection settings migration failed: ' . $data, DEBUG_DEVELOPER);
        return false;
    }

    /**
     * Migrates sync settings from serialized to JSON format
     *
     * @return bool Success status
     */
    protected function migrate_sync_settings() {
        global $CFG;
        if (!isset($CFG->eb_synch_settings) || empty($CFG->eb_synch_settings)) {
            return true;
        }
        $plugin_config = get_config('auth_edwiserbridge', 'eb_synch_settings');
        if (!empty($plugin_config)) {
            return true;
        } 
        $settings = json_decode($CFG->eb_synch_settings, true);
        if ( JSON_ERROR_NONE === json_last_error() ) {
            return true;
        }
        list($success, $data) = $this->convert_serialized_to_json($CFG->eb_synch_settings);
        if ($success) {
            set_config( 'eb_synch_settings', $data, 'auth_edwiserbridge' );
            return true;
        }

        debugging('Sync settings migration failed: ' . $data, DEBUG_DEVELOPER);
        return false;
    }

    /**
     * Converts serialized data to JSON format
     *
     * @param string $data Serialized data to convert
     * @return array [success, data/error_message]
     */
    protected function convert_serialized_to_json($data) {
        if (empty($data)) {
            return [true, '{}'];
        }
        $decoded = unserialize($data);        
        if ($decoded === false) {
            return [false, 'Invalid serialized data format'];
        }

        $json = json_encode($decoded);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [false, 'Failed to convert to JSON: ' . json_last_error_msg()];
        }

        return [true, $json];
    }

    /**
     * Migrates global settings from the Moodle configuration to the plugin configuration.
     *
     * This function iterates through a list of global settings and moves them from the Moodle
     * configuration to the plugin configuration. This is likely part of a migration process
     * to move settings from the global Moodle configuration to the plugin-specific configuration.
     *
     * @return bool True if the migration was successful, false otherwise.
     */
    protected function migrate_global_settings() {
        global $CFG;
        $configs = [
            // 'eb_connection_settings',
            // 'eb_synch_settings',
            'wploginbtnicon',
            'edwiserbridge_dismiss_update_notification',
            'sharedsecret',
            'wpsiteurl',
            'logoutredirecturl',
            'wploginenablebtn',
            'wploginbtntext',
            'edwiserbridge_plugin_versions',
            'edwiserbridge_update_msg',
            'edwiserbridge_update_available',
            'edwiserbridge_update_data',
            'plugin_update_transient',
            'eb_setup_progress',
            'ebexistingserviceselect',
            'edwiser_bridge_last_created_token',
            'eb_setup_wp_site_name',
        ];
        foreach ($configs as $config) {
            $plugin_config = get_config('auth_edwiserbridge', $config);
            if ( isset($CFG->$config) && empty($plugin_config) ) {
                set_config($config, $CFG->$config, 'auth_edwiserbridge');
                continue;
            }
        }
        return true;
    }
}
