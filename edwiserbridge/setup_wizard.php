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
 * Setup wizard for Edwiser Bridge plugin.
 * Functionality to manage amd display setup wizard.
 *
 * @package    auth_edwiserbridge
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');
require_once(__DIR__ . '/compat.php');
global $CFG, $PAGE;
use core\context\system as context_system;

require_once($CFG->libdir . '/adminlib.php');
require_once(dirname(__FILE__) . '/lib.php');

$setupwizard = new auth_edwiserbridge\local\setup_wizard();

$eb_setup_progress = get_config('auth_edwiserbridge', 'eb_setup_progress');
// Check progress and redirect accordingly.
$progress  = !empty( $eb_setup_progress ) ? $eb_setup_progress : '';
if ( ! empty( $progress ) ) {

    $nextstep = $setupwizard->get_next_step( $progress );

    $currentstep = optional_param('current_step', '', PARAM_TEXT);
    if (empty($currentstep)) {
        $redirecturl = $CFG->wwwroot . '/auth/edwiserbridge/setup_wizard.php?current_step=' . $nextstep;
        redirect ($redirecturl);
    }
}

// Check if the get parameter have same progress.

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
$baseurl = $CFG->wwwroot . '/auth/edwiserbridge/setup_wizard.php';

/*
 * Necessary page requirements.
 */

$PAGE->set_pagelayout("popup");

$PAGE->set_context($context);
$PAGE->set_url('/auth/edwiserbridge/edwiserbridge.php?tab=settings');

$PAGE->set_title(get_string('eb-setup-page-title', 'auth_edwiserbridge'));


$PAGE->requires->css('/auth/edwiserbridge/styles/style.css');
$PAGE->requires->css('/auth/edwiserbridge/styles/setup-wizard.css');
// 
// $PAGE->requires->js_call_amd('auth_edwiserbridge/settings', 'init');

// Actual page template output starts here.

// Output page header.
echo $OUTPUT->header();

// Start page container.
echo $OUTPUT->container_start();

// This outputs setup wizard template.
// This will use classes/class-setup-wizard.php file.
$setupwizard->eb_setup_wizard_template();

// End page container.
echo $OUTPUT->container_end();

// Output footer.
echo $OUTPUT->footer();
