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
 * Used to create web service.
 * Functionality to create web service.
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
 * Defines the web services form for the Edwiser Bridge plugin.
 * This class extends the moodleform class and provides the functionality to create and manage web services.
 */
class service_form extends moodleform {

    /**
     * Defines the web services form for the Edwiser Bridge plugin.
     * This method is responsible for creating and managing the web services form, including adding various form elements such as service list, service input, user list, site language, site URL, and token.
     */
    public function definition() {
        global $CFG;

        $mform            = $this->_form;
        $existingservices = auth_edwiserbridge_get_existing_services();
        $authusers        = auth_edwiserbridge_get_administrators();

        $edwiser_bridge_last_created_token = get_config('auth_edwiserbridge', 'edwiser_bridge_last_created_token');
        $ebexistingserviceselect = get_config('auth_edwiserbridge', 'ebexistingserviceselect');
        $token            = !empty($edwiser_bridge_last_created_token) ? $edwiser_bridge_last_created_token : ' - ';
        $service          = !empty($ebexistingserviceselect) ? $ebexistingserviceselect : '';
        $tokenfield       = '';

        // 1st Field Service list
        $select = $mform->addElement(
            'select',
            'eb_sevice_list',
            get_string('existing_service_lbl', 'auth_edwiserbridge'),
            $existingservices
        );
        $mform->addHelpButton('eb_sevice_list', 'eb_mform_service_desc', 'auth_edwiserbridge');
        $select->setMultiple(false);

        // 2nd Field Service input name
        $mform->addElement(
            'text',
            'eb_service_inp',
            get_string('new_service_inp_lbl', 'auth_edwiserbridge'),
            ['class' => 'eb_service_field']
        );
        $mform->setType('eb_service_inp', PARAM_TEXT);

        // 3rd field Users List.
        $select = $mform->addElement(
            'select',
            'eb_auth_users_list',
            get_string('new_service_user_lbl', 'auth_edwiserbridge'),
            $authusers,
            ['class' => '']
        );
        $select->setMultiple(false);

        $sitelang = '<div class="eb_copy_txt_wrap eb_copy_div"> <div style="width:60%;"> <b class="eb_copy" id="eb_mform_lang">'
            . $CFG->lang . '</b> </div> <div>  <button class="btn btn-primary eb_primary_copy_btn">'
            . get_string('copy', 'auth_edwiserbridge') . '</button></div></div>';

        $mform->addElement(
            'static',
            'eb_mform_lang_wrap',
            get_string('lang_label', 'auth_edwiserbridge'),
            $sitelang
        );
        $mform->addHelpButton('eb_mform_lang_wrap', 'eb_mform_lang_desc', 'auth_edwiserbridge');

        $siteurl = '<div class="eb_copy_txt_wrap eb_copy_div"> <div style="width:60%;"> <b class="eb_copy" id="eb_mform_site_url">'
            . $CFG->wwwroot . '</b> </div> <div> <button class="btn btn-primary eb_primary_copy_btn">'
            . get_string('copy', 'auth_edwiserbridge')
            . '</button></div></div>';
        // 4th field Site Url
        $mform->addElement(
            'static',
            'eb_mform_site_url_wrap',
            get_string('site_url', 'auth_edwiserbridge'),
            $siteurl
        );
        $mform->addHelpButton('eb_mform_site_url_wrap', 'eb_mform_ur_desc', 'auth_edwiserbridge');

        // If service is empty then show just the blank text with dash.
        $tokenfield = $token;

        if (!empty($service)) {
            // If the token available then show the token.
            $tokenfield = auth_edwiserbridge_create_token_field($service, $token);
        }

        // 5th field Token
        $mform->addElement(
            'static',
            'eb_mform_token_wrap',
            get_string('token', 'auth_edwiserbridge'),
            '<b id="id_eb_token_wrap">' . $tokenfield . '</b>'
        );

        $mform->addHelpButton('eb_mform_token_wrap', 'eb_mform_token_desc', 'auth_edwiserbridge');
        $mform->addElement(
            'static',
            'eb_mform_common_error',
            '',
            '<div id="eb_common_err"></div><div id="eb_common_success"></div>'
        );
        $mform->addElement('button', 'eb_mform_create_service', get_string("link", 'auth_edwiserbridge'));

        if (!class_exists('webservice')) {
            require_once($CFG->dirroot . "/webservice/lib.php");
        }

        // Set default values.
        if (!empty($service)) {
            $mform->setDefault("eb_sevice_list", $service);
        }

        $mform->addElement(
            'html',
            '<div class="eb_connection_btns"><input type="submit" class="btn btn-primary eb_setting_btn" id="service_submit_continue" name="service_submit_continue"
                value="' . get_string("next", 'auth_edwiserbridge') . '"></div>'
        );
    }
}
