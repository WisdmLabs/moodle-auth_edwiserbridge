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
 * Navigation form.
 * Functionality to manage navigation form.
 *
 * @package    auth_edwiserbridge
 * @category   external
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_edwiserbridge\settings;
use moodleform;

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/formslib.php");

/**
 * Defines the navigation form for the Edwiser Bridge plugin.
 * The navigation form includes tabs for different sections of the plugin settings.
 */
class navigation_form extends moodleform {
    
    /**
     * Defines the navigation form for the Edwiser Bridge plugin.
     * This method sets up the navigation tabs for the different sections of the plugin settings.
     */
    public function definition() {
        global $CFG;
        $mform = $this->_form;

        $currenttab = optional_param('tab', '', PARAM_TEXT);

        $summarystatus = 'eb-tabs eb_summary_tab summary_tab_' . auth_edwiserbridge_get_summary_status();

        $summary = 'summary' === $currenttab ? 'active-tab ' . $summarystatus : $summarystatus;

        $tabs = [
            [
                'link'  => $CFG->wwwroot . "/auth/edwiserbridge/edwiserbridge.php?tab=settings",
                'label' => get_string('tab_mdl_required_settings', 'auth_edwiserbridge'),
                'css'   => 'settings' === $currenttab ? 'active-tab eb-tabs ' : 'eb-tabs',
            ],
            [
                'link'  => $CFG->wwwroot . "/auth/edwiserbridge/edwiserbridge.php?tab=service",
                'label' => get_string('tab_service', 'auth_edwiserbridge'),
                'css'   => 'service' === $currenttab ? 'active-tab eb-tabs ' : 'eb-tabs',
            ],
            [
                'link'  => $CFG->wwwroot . "/auth/edwiserbridge/edwiserbridge.php?tab=connection",
                'label' => get_string('tab_conn', 'auth_edwiserbridge'),
                'css'   => 'connection' === $currenttab ? 'active-tab eb-tabs ' : 'eb-tabs',
            ],
            [
                'link'  => $CFG->wwwroot . "/auth/edwiserbridge/edwiserbridge.php?tab=synchronization",
                'label' => get_string('tab_synch', 'auth_edwiserbridge'),
                'css'   => 'synchronization' === $currenttab ? 'active-tab eb-tabs ' : 'eb-tabs',
            ],
            [
                'link'  => $CFG->wwwroot . "/auth/edwiserbridge/edwiserbridge.php?tab=sso",
                'label' => get_string('tab_sso', 'auth_edwiserbridge'),
                'css'   => 'sso' === $currenttab ? 'active-tab eb-tabs ' : 'eb-tabs',
            ],
            [
                'link'  => $CFG->wwwroot . "/auth/edwiserbridge/edwiserbridge.php?tab=summary",
                'label' => get_string('summary', 'auth_edwiserbridge'),
                'css'   => $summary,
            ],
        ];

        $mform->addElement('html', '<div class="eb-tabs-cont">' . $this->print_tabs($tabs) . '</div>');
    }

    /**
     * Prepares and prints the list of tab links.
     *
     * @param array $tabs An array of settings arrays, each containing a link, label, and CSS class for a tab.
     * @return void
     */
    private function print_tabs($tabs) {
        ob_start();
        global $CFG;
        $ebexistingserviceselect = get_config('auth_edwiserbridge', 'ebexistingserviceselect');
        $service = !empty($ebexistingserviceselect) ? $ebexistingserviceselect : '';
        echo "<div id='web_service_id' data-serviceid='$service'></div>";
        foreach ($tabs as $tab) {
            echo "<a href='$tab[link]' class='$tab[css]'>$tab[label]</a>";
        }
        return ob_get_clean();
    }
}
