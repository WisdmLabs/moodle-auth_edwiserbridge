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
 * Plugin administration pages are defined here.
 *
 * @package     auth_edwiserbridge
 * @copyright   2021 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Wisdmlabs
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/lib.php');

global $CFG, $PAGE;

if ( check_edwiser_bridge_pro_dependancy() ) {
    $PAGE->requires->js(new moodle_url('/auth/edwiserbridge/js/eb_settings.js'));
    $PAGE->requires->js(new moodle_url('/auth/edwiserbridge/js/sso_settings.js'));
    $PAGE->requires->js_call_amd('auth_edwiserbridge/eb_settings', 'init');
    $PAGE->requires->js_call_amd('auth_edwiserbridge/eb_sso_settings', 'init');

    $stringmanager = get_string_manager();
    $strings = $stringmanager->load_component_strings('auth_edwiserbridge', 'en');
    $PAGE->requires->strings_for_js(array_keys($strings), 'auth_edwiserbridge');

    // add new cateogry in admin settings.

    $ADMIN->add(
        'modules',
        new admin_category(
            'edwisersettings',
            new lang_string(
                'edwiserbridge',
                'auth_edwiserbridge'
            )
        )
    );

    $ADMIN->add(
        'edwisersettings',
        new admin_externalpage(
            'edwiserbridge_conn_synch_settings',
            new lang_string(
                'nav_name',
                'auth_edwiserbridge'
            ),
            "$CFG->wwwroot/auth/edwiserbridge/edwiserbridge.php?tab=settings",
            array(
                'moodle/user:update',
                'moodle/user:delete'
            )
        )
    );

    $ADMIN->add(
        'edwisersettings',
        new admin_externalpage(
            'edwiserbridge_setup',
            new lang_string(
                'run_setup',
                'auth_edwiserbridge'
            ),
            "$CFG->wwwroot/auth/edwiserbridge/setup_wizard.php",
            array(
                'moodle/user:update',
                'moodle/user:delete'
            )
        )
    );

    if ($ADMIN->fulltree) {
        global $CFG;
        $settings_link = new moodle_url('/auth/edwiserbridge/edwiserbridge.php', array('tab' => 'sso'));
        $heading = new lang_string('settings_migration', 'auth_edwiserbridge') . ' <a href="' . $settings_link . '">' . get_string('click_here', 'auth_edwiserbridge') . '</a>';
        $settings->add(new admin_setting_heading('auth_edwiserbridge_settings', '', $heading));
    }
    

}
