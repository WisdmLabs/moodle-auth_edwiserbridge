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
 * Upgrade script for Edwiser Bridge plugin.
 * Functionality to manage upgrade of the plugin.
 *
 * @package    auth_edwiserbridge
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/auth/edwiserbridge/lib.php');

/**
 * Upgrades the Edwiser Bridge plugin.
 *
 * This function is responsible for managing the upgrade process of the Edwiser Bridge plugin.
 * It checks for the Edwiser Bridge Pro dependency, enables the plugin in the default authentication method,
 * and updates the webservice functions as needed.
 *
 * @return bool True to continue the upgrade process.
 */
function xmldb_auth_edwiserbridge_upgrade($oldversion) {
    // Enable plugin in the default authentication method.
    auth_edwiserbridge_enable_plugin();

    // Check and upgrade webservice functions.
    // Migrate serialized data to json format & global config to plugin level config.
    $migrationhelper = new \auth_edwiserbridge\local\migration_helper();
    $migrationhelper->execute_migration();
    auth_edwiserbridge_check_and_update_webservice_functions();
    

    return true; // Return true to continue, it is must.
}
