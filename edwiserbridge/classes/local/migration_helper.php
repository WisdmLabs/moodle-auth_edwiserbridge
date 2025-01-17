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
        var_dump('execute_migration');
        $result = true;
        $result = $result && $this->migrate_connection_settings();
        $result = $result && $this->migrate_sync_settings();

        return $result;
    }

    /**
     * Migrates connection settings from serialized to JSON format
     *
     * @return bool Success status
     */
    protected function migrate_connection_settings() {
        global $CFG;
        $settings = $CFG->eb_connection_settings;
        var_dump($settings);
        if (empty($settings)) {
            return true;
        }
        $temp = json_decode($settings, true);
        if ( JSON_ERROR_NONE === json_last_error() ) {
            return true;
        }
        list($success, $data) = $this->convert_serialized_to_json($settings);
        
        var_dump($success);
        var_dump($data);
        if ($success) {
            set_config( 'eb_connection_settings', $data);
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
        $settings = $CFG->eb_synch_settings;
        if (empty($settings)) {
            return true;
        }
        $temp = json_decode($settings, true);
        if ( JSON_ERROR_NONE === json_last_error() ) {
            return true;
        }
        list($success, $data) = $this->convert_serialized_to_json($settings);
        if ($success) {
            set_config( 'eb_synch_settings', $data );
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

        $decoded = @unserialize($data);
        if ($decoded === false) {
            return [false, 'Invalid serialized data format'];
        }

        $json = json_encode($decoded);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [false, 'Failed to convert to JSON: ' . json_last_error_msg()];
        }

        return [true, $json];
    }
}
