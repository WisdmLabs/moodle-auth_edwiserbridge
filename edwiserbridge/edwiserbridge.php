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
 * Edwiser Bridge plugin settings page.
 * Functionality to manage and display settings page.
 *
 * @package    auth_edwiserbridge
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */



require('../../config.php');
require_once(__DIR__ . '/compat.php');

use auth_edwiserbridge\settings\navigation_form as navigation_form;
use core\context\system as context_system;
global $CFG, $PAGE;
require_once($CFG->libdir . '/adminlib.php');
require_once(dirname(__FILE__) . '/lib.php');


$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');

// Restrict normal user to access this page.
admin_externalpage_setup('edwiserbridge_conn_synch_settings');

$stringmanager = get_string_manager();
$strings = $stringmanager->load_component_strings('auth_edwiserbridge', 'en');
$PAGE->requires->strings_for_js(array_keys($strings), 'auth_edwiserbridge');

// Require Login.
require_login();
$context = context_system::instance();
$baseurl = $CFG->wwwroot . '/auth/edwiserbridge/edwiserbridge.php?tab=connection';

/*
* Creating array of the objects which will be created.
*/
$mform = [
    'service' => [
        'id'  => 'eb_service_form',
    ],
    'connection' => [
        'id'  => 'eb_connection_form',
    ],
    'synchronization' => [
        'id'  => 'eb_synch_form',
    ],
    'settings' => [
        'id'  => 'eb_settings_form',
    ],
    'summary' => [
        'id'  => 'eb_summary_form',
    ],
    'sso' => [
        'id'  => 'eb_sso_form',
    ],
];

$mformnavigation = new navigation_form();

/*
 * Necessary page requirements.
 */
$PAGE->set_pagelayout('admin');

$PAGE->set_context($context);
$PAGE->set_url('/auth/edwiserbridge/edwiserbridge.php?tab=settings');

$PAGE->set_title(get_string('eb-setting-page-title', 'auth_edwiserbridge'));
$PAGE->requires->css('/auth/edwiserbridge/styles/style.css');
$PAGE->requires->js_call_amd("auth_edwiserbridge/edwiser_bridge", "init");

echo $OUTPUT->header();
echo $OUTPUT->container_start();

/*
 * Navigation form
 */
$mformnavigation->display();

/*
* Functionality to display tab wise forms
*/
$pageurl = $CFG->wwwroot . '/auth/edwiserbridge/edwiserbridge.php?tab=';


foreach ($mform as $key => $mformdata) {
    // initialize class based on key.
    switch($key) {
        case 'service':
            $object = new auth_edwiserbridge\settings\service_form($pageurl . $key, null, 'post', '', ["id" => $mformdata['id']], true, null);
            break;
        case 'connection':
            $object = new auth_edwiserbridge\settings\connection_form($pageurl . $key, null, 'post', '', ["id" => $mformdata['id']], true, null);
            break;
        case 'synchronization':
            $object = new auth_edwiserbridge\settings\synchronization_form($pageurl . $key, null, 'post', '', ["id" => $mformdata['id']], true, null);
            break;
        case 'settings':
            $object = new auth_edwiserbridge\settings\settings_form($pageurl . $key, null, 'post', '', ["id" => $mformdata['id']], true, null);
            break;
        case 'summary':
            $object = new auth_edwiserbridge\settings\summary_form($pageurl . $key, null, 'post', '', ["id" => $mformdata['id']], true, null);
            break;
        case 'sso':
            $object = new auth_edwiserbridge\settings\sso_form($pageurl . $key, null, 'post', '', ["id" => $mformdata['id']], true, null);
            break;
    }

    $fileoptions = [
        'maxbytes' => 0,
        'maxfiles' => '1',
        'subdirs' => 0,
        'context' => context_system::instance(),
    ];

    if ($key == 'sso') {
        $data = new stdClass();
        $data = file_prepare_standard_filemanager(
            $data,
            'wploginbtnicon',
            $fileoptions,
            context_system::instance(),
            'auth_edwiserbridge',
            'wploginbtnicon',
            0 // 0 is the item id.
        );
    }

    if ($formdata = $object->get_data()) {
        // In this case you process validated data. $mform->get_data() returns data posted in form.

        // Calling the save function for each tabn if present.
        $functionname = 'auth_edwiserbridge_save_' . $key . '_form_settings';

        if (function_exists($functionname)) {
            $functionname($formdata, $object);
        }

        if ($key == 'sso') {
            $data = file_postupdate_standard_filemanager(
                $data, 'wploginbtnicon',
                $fileoptions,
                context_system::instance(),
                'auth_edwiserbridge',
                'wploginbtnicon',
                0
            );
            // Save filename in database.
            $filename = '';
            $fs = get_file_storage();
            $file = $fs->get_area_files(
                context_system::instance()->id,
                'auth_edwiserbridge',
                'wploginbtnicon',
                0,
                'itemid, filepath, filename, filesize'
            );
            foreach ($file as $f) {
                if (!$f->is_directory()) {
                    $filename = $f->get_filename();
                }
            }
            if ($filename != '') {
                $filename = '/'.$filename;
            }
            set_config('wploginbtnicon', $filename, 'auth_edwiserbridge');
        }
    }

    $tab = optional_param('tab', '', PARAM_TEXT);

    // Display connection form  for the first time.
    if ($tab == $key) {
        if ($key == 'sso') {
            $object->set_data($data);
        }
        $object->display();
    }
}

echo $OUTPUT->container_end();
echo $OUTPUT->footer();
