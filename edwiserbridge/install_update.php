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
 * Install edwiser plugin
 *
 * @package   auth_edwiserbridge
 * @copyright 2020 WisdmLabs <edwiser@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */

use core\update\remote_info;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/filelib.php');

$installupdate = required_param('installupdate', PARAM_COMPONENT); // Install given available update.
$confirminstallupdate = optional_param('confirminstallupdate', 0, PARAM_INT);
$dismiss = optional_param('dismiss', 0, PARAM_INT);
$download = optional_param('download', 0, PARAM_INT);
$sesskey = optional_param('sesskey', 0, PARAM_RAW);

if($dismiss) {
    set_config('edwiserbridge_dismiss_update_notification', 1, 'auth_edwiserbridge');
    redirect(new moodle_url('/my'));
}

require_login();
$syscontext = context_system::instance();
require_capability('moodle/site:config', $syscontext);

$params = array(
    'installupdate' => $installupdate,
    'download' => $download,
    'sesskey' => $sesskey
);
$pageurl = new moodle_url('/auth/edwiserbridge/install_update.php', $params);

$PAGE->set_url($pageurl);
$PAGE->set_context($syscontext);

$edwiserpluginupdate = new auth_edwiserbridge\update(!$download && !$confirminstallupdate);
$plugin = $edwiserpluginupdate->get_plugin_update($params);
if ($plugin === false) {
    $output = $PAGE->get_renderer('core', 'admin');
    echo $output->header();
    throw new moodle_exception('unable to fetch update');
    echo $output->footer();
    die;
}

require_once($CFG->libdir.'/upgradelib.php');
require_sesskey();

$PAGE->set_pagelayout('maintenance');
$PAGE->set_popup_notification_allowed(false);

$type = explode('_', $installupdate)[0];
$pluginname = str_replace($type.'_', '', $installupdate);
$installable = new remote_info;
$installable->name = get_string('pluginname', $installupdate);
$installable->component = $installupdate;
$installable->version = $plugin;
$edwiserpluginupdate->upgrade_install_plugin(
    $installable,
    $confirminstallupdate,
    get_string('updateavailableinstallallhead', 'core_admin'),
    new moodle_url(
        $PAGE->url,
        array(
            'installupdate' => $installupdate,
            'confirminstallupdate' => 1,
            'sesskey' => $sesskey
        )
    ),
    $plugin->url,
    new moodle_url('/my')
);
