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
 * Manage SSO settings.
 * Functionality to manage SSO settings.
 *
 * @package    auth_edwiserbridge
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace auth_edwiserbridge\settings;
use moodleform;
use auth_edwiserbridge\eb_pro_license_controller;

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/formslib.php");

/**
 * form shown while adding Edwiser Bridge settings.
 */
class sso_form extends moodleform {

    /**
     * Defining sso form.
     */
    public function definition() {
        $mform         = $this->_form;
        $sites         = auth_edwiserbridge_get_site_list();
        $sitekeys      = array_keys($sites);

        global $CFG;
        $license = new eb_pro_license_controller();
        if ($license->get_data_from_db() == 'available') {
            $mform->addElement('html', '<div class="eb-auto-generate-key-container">');
            $mform->addElement(
                'text',
                'sharedsecret',
                get_string('auth_edwiserbridge_secretkey', 'auth_edwiserbridge'),
                'size="35"'
            );
            $mform->addHelpButton(
                'sharedsecret',
                'auth_edwiserbridge_secretkey',
                'auth_edwiserbridge'
            );
            $mform->setType('sharedsecret', PARAM_TEXT);

            // Add auto generate key button next to secret key field.
            $mform->addElement(
                'button',
                'secret_key_generate',
                get_string('auth_edwiserbridge_auto_generate_key', 'auth_edwiserbridge')
            );

            $mform->addElement(
                'text',
                'wpsiteurl',
                get_string('auth_edwiserbridge_wpsiteurl', 'auth_edwiserbridge'),
                'size="35"'
            );
            $mform->addHelpButton(
                'wpsiteurl',
                'auth_edwiserbridge_wpsiteurl',
                'auth_edwiserbridge'
            );
            $mform->setType('wpsiteurl', PARAM_TEXT);

            $mform->addElement(
                'text',
                'logoutredirecturl',
                get_string('auth_edwiserbridge_logoutredirecturl', 'auth_edwiserbridge'),
                'size="35"'
            );
            $mform->addHelpButton(
                'logoutredirecturl',
                'auth_edwiserbridge_logoutredirecturl',
                'auth_edwiserbridge'
            );
            $mform->setType('logoutredirecturl', PARAM_TEXT);

            $mform->addElement(
                'advcheckbox',
                'wploginenablebtn',
                get_string('auth_edwiserbridge_wploginenablebtn', 'auth_edwiserbridge'),
                get_string('auth_edwiserbridge_wploginenablebtn_default', 'auth_edwiserbridge'),
                'size="35"'
            );
            $mform->addHelpButton(
                'wploginenablebtn',
                'auth_edwiserbridge_wploginenablebtn',
                'auth_edwiserbridge'
            );

            $mform->addElement(
                'text',
                'wploginbtntext',
                get_string('auth_edwiserbridge_wploginbtntext', 'auth_edwiserbridge'),
                'size="35"'
            );
            $mform->addHelpButton(
                'wploginbtntext',
                'auth_edwiserbridge_wploginbtntext',
                'auth_edwiserbridge'
            );
            $mform->setType('wploginbtntext', PARAM_TEXT);

            $mform->addElement(
                'filemanager',
                'wploginbtnicon_filemanager',
                get_string('auth_edwiserbridge_wploginbtnicon', 'auth_edwiserbridge'),
                null,
                ['maxbytes' => 1024 * 1024, 'accepted_types' => ['.png', '.jpg', '.jpeg'], 'maxfiles' => 1]
            );
            $mform->addHelpButton(
                'wploginbtnicon_filemanager',
                'auth_edwiserbridge_wploginbtnicon',
                'auth_edwiserbridge'
            );

            $wplogin = get_config('auth_edwiserbridge', 'wploginenablebtn');
            $wplogin = $wplogin == 0 ? 0 : 1;
            $mform->setDefault(
                "sharedsecret",
                get_config('auth_edwiserbridge', 'sharedsecret') ? get_config('auth_edwiserbridge', 'sharedsecret') : ''
            );
            $mform->setDefault(
                "wpsiteurl",
                get_config('auth_edwiserbridge', 'wpsiteurl') ? get_config('auth_edwiserbridge', 'wpsiteurl') : ''
            );
            $mform->setDefault(
                "logoutredirecturl",
                get_config('auth_edwiserbridge', 'logoutredirecturl') ? get_config('auth_edwiserbridge', 'logoutredirecturl') : ''
            );
            $mform->setDefault("wploginenablebtn", $wplogin);
            $mform->setDefault(
                "wploginbtntext",
                get_config('auth_edwiserbridge', 'wploginbtntext') ? get_config('auth_edwiserbridge', 'wploginbtntext') : ''
            );

            $mform->addElement(
                'html',
                '<div class="eb_connection_btns">
                    <input type="submit" class="btn btn-primary eb_setting_btn" id="sso_submit" name="sso_submit" value="'
                    . get_string("save", "auth_edwiserbridge")
                    . '"><input type="submit" class="btn btn-primary eb_setting_btn" id="sso_submit_continue"
                    name="sso_submit_continue" value="' . get_string("save_cont", "auth_edwiserbridge") . '">
                </div>'
            );
        } else {
            global $CFG;
            $settingurl = $CFG->wwwroot . '/auth/edwiserbridge/edwiserbridge.php?tab=summary';
            $mform->addElement(
                'html',
                '<p>Please activate licence from <a href="' . $settingurl . ' ">here</a> to access this setting.</p>'
            );
        }
    }
}
