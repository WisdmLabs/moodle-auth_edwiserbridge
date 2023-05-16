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
            'edwiserbridge_sso',
            new lang_string(
                'eb_sso_settings',
                'auth_edwiserbridge'
            ),
            "$CFG->wwwroot/admin/settings.php?section=edwiserbridge_sso_settings",
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

    // Adding settings page.
    $settings = new admin_settingpage('edwiserbridge_sso_settings', new lang_string('eb_sso_settings', 'auth_edwiserbridge'));
    // $ADMIN->add('authsettings', $settings);

    // $settings->add(
    //     new admin_setting_heading(
    //         'auth_edwiserbridge/eb_settings_msg',
    //         '',
    //         '<div class="eb_settings_btn_cont" style="padding:20px;">' . get_string('eb_settings_msg', 'auth_edwiserbridge')
    //             . '<a target="_blank" class="eb_settings_btn" style="padding: 7px 18px; border-radius: 4px; color: white;
    //         background-color: #2578dd; margin-left: 5px;" href="' . $CFG->wwwroot . '/auth/edwiserbridge/setup_wizard.php'
    //             . '" >' . get_string('click_here', 'auth_edwiserbridge') . '</a></div>'
    //     )
    // );

    // Adding this field so that the setting page will be shown after installation.
    // $settings->add(
    //     new admin_setting_configcheckbox(
    //         'auth_edwiserbridge/eb_setup_wizard_field',
    //         get_string(
    //             'eb_dummy_msg',
    //             'auth_edwiserbridge'
    //         ),
    //         ' ',
    //         1
    //     )
    // );

    if ($ADMIN->fulltree) {
        // Introductory explanation.
        $settings->add(new admin_setting_heading('auth_edwiserbridge/description', '', new lang_string('auth_edwiserbridgedescription', 'auth_edwiserbridge')));

        /*
        * Secreate key settings filed.
        */
        $connection = isset($CFG->eb_connection_settings) ? unserialize($CFG->eb_connection_settings) : false;
        if( !empty($connection)) {
            foreach ($connection as $key => $value) {
                $wp_url = $value['wp_url'] . '/wp-admin/admin.php?page=eb-settings&tab=sso_settings_general';
                $wp_url = ' <a href="' . $wp_url . '" target="_blank">here</a>';
                break;
            }
        }
        $wp_site_url = get_config('auth_edwiserbridge', 'wpsiteurl');
        if (!empty($wp_site_url)) {
            $wp_url = ' <a href="' . $wp_site_url . '/wp-admin/admin.php?page=eb-settings&tab=sso_settings_general" target="_blank">here</a>';
        }
        $settings->add(new admin_setting_configtext(
            'auth_edwiserbridge/sharedsecret',
            get_string('auth_edwiserbridge_secretkey', 'auth_edwiserbridge'),
            get_string('auth_edwiserbridge_secretkey_desc', 'auth_edwiserbridge') . $wp_url,
            '',
            PARAM_RAW
        ));

        $settings->add(
            new admin_setting_configtext(
                'auth_edwiserbridge/wpsiteurl',
                get_string('auth_edwiserbridge_wpsiteurl_lbl', 'auth_edwiserbridge'),
                get_string('auth_edwiserbridge_wpsiteurl_desc', 'auth_edwiserbridge'),
                '',
                PARAM_RAW
            )
        );

        /*
        * Setting filed for the logout redirect.
        */
        $settings->add(new admin_setting_configtext(
            'auth_edwiserbridge/logoutredirecturl',
            get_string('auth_edwiserbridge_logoutredirecturl_lbl', 'auth_edwiserbridge'),
            get_string('auth_edwiserbridge_logoutredirecturl_desc', 'auth_edwiserbridge'),
            '',
            PARAM_RAW
        ));


        // Setting to enable login with WordPress Button.
        $settings->add(new admin_setting_configcheckbox(
            'auth_edwiserbridge/wploginenablebtn',
            get_string('auth_edwiserbridge_wploginenablebtn_lbl', 'auth_edwiserbridge'),
            get_string('auth_edwiserbridge_wploginenablebtn_desc', 'auth_edwiserbridge'),
            '',
            PARAM_RAW
        ));


        // Setting to show text for the button.
        $settings->add(new admin_setting_configtext(
            'auth_edwiserbridge/wploginbtntext',
            get_string('auth_edwiserbridge_wploginbtntext_lbl', 'auth_edwiserbridge'),
            get_string('auth_edwiserbridge_wploginbtntext_desc', 'auth_edwiserbridge'),
            '',
            PARAM_RAW
        ));

        // Add wordpress login icon upload.
        $settings->add(new admin_setting_configstoredfile(
            'auth_edwiserbridge/wploginbtnicon',
            get_string('auth_edwiserbridge_wploginbtnicon_lbl', 'auth_edwiserbridge'),
            get_string('auth_edwiserbridge_wploginbtnicon_desc', 'auth_edwiserbridge'),
            'wploginbtnicon',
            0,
            array('maxfiles' => 1, 'accepted_types' => array('image'))
        ));

    }
}