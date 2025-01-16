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
 * Settings form.
 * Functionality to manage and display settings form.
 *
 * @package    auth_edwiserbridge
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace auth_edwiserbridge\settings;
use moodleform;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("$CFG->libdir/formslib.php");

/**
 * Defines the settings form for the Edwiser Bridge authentication plugin.
 *
 * This class extends the moodleform class and provides the definition of the
 * settings form for the Edwiser Bridge authentication plugin. The form includes
 * various checkboxes for configuring the plugin's settings, such as the REST
 * protocol, web service, password policy, extended username, and auto-update
 * check. The form also includes buttons for saving the settings.
 */
class settings_form extends moodleform {

    /**
     * Defines the form definition for the settings form of the Edwiser Bridge authentication plugin.
     *
     * This method sets up the various form elements, including checkboxes for configuring the
     * REST protocol, web service, password policy, extended username, and auto-update check.
     * It also adds the submit buttons for saving the settings.
     */
    public function definition() {
        $mform         = $this->_form;
        $defaultvalues = auth_edwiserbridge_get_required_settings();

        // 1st field.
        $mform->addElement(
            'advcheckbox',
            'rest_protocol',
            get_string('web_rest_protocol_cb', 'auth_edwiserbridge'),
            get_string('web_rest_protocol_cb_desc', 'auth_edwiserbridge'),
            ['group' => 1],
            [0, 1]
        );

        // 2nd field.
        $mform->addElement(
            'advcheckbox',
            'web_service',
            get_string('web_service_cb', 'auth_edwiserbridge'),
            get_string('web_service_cb_desc', 'auth_edwiserbridge'),
            ['group' => 1],
            [0, 1]
        );

        // 3rd field.
        $mform->addElement(
            'advcheckbox',
            'pass_policy',
            get_string('password_policy_cb', 'auth_edwiserbridge'),
            get_string('password_policy_cb_desc', 'auth_edwiserbridge'),
            ['group' => 1],
            [0, 1]
        );

        // 4th field.
        $mform->addElement(
            'advcheckbox',
            'extended_username',
            get_string('extended_char_username_cb', 'auth_edwiserbridge'),
            get_string('extended_char_username_cb_desc', 'auth_edwiserbridge'),
            ['group' => 1],
            [0, 1]
        );

        // 5th field.
        $mform->addElement(
            'advcheckbox',
            'enable_auto_update_check',
            get_string('enable_auto_update_check', 'auth_edwiserbridge'),
            get_string('enable_auto_update_check_desc', 'auth_edwiserbridge'),
            ['group' => 1],
            [0, 1]
        );

        // Fill form with the existing values.
        if (! empty($defaultvalues)) {
            $mform->setDefault('rest_protocol', $defaultvalues['rest_protocol']);
            $mform->setDefault('web_service', $defaultvalues['web_service']);
            $mform->setDefault('pass_policy', $defaultvalues['pass_policy']);
            $mform->setDefault('extended_username', $defaultvalues['extended_username']);
            $mform->setDefault('enable_auto_update_check', $defaultvalues['enable_auto_update_check']);
        }

        $mform->addElement(
            'html',
            '<div class="eb_connection_btns">
                <input type="submit" class="btn btn-primary eb_setting_btn" id="settings_submit"
                name="settings_submit" value="' . get_string('save', 'auth_edwiserbridge') . '">
                <input type="submit" class="btn btn-primary eb_setting_btn" id="settings_submit_continue"
                name="settings_submit_continue" value="' . get_string('save_cont', 'auth_edwiserbridge') . '">
            </div>'
        );
    }

    /**
     * Validates the form data submitted by the user.
     *
     * @param array $data  The submitted form data.
     * @param array $files The submitted files, if any.
     *
     * @return void
     */
    public function validation($data, $files) {
        return [];
    }
}
