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
 * @package   auth_edwiserbridge
 * @copyright (c) 2021 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Ishwar Singh
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/auth/edwiserbridge/lib.php');

/**
 * custom code to be run on installing the plugin.
 */
function xmldb_auth_edwiserbridge_install() {
    
    if ( ! check_edwiser_bridge_pro_dependancy() ) {
        edwiser_bridge_pro_dependancy_notice();
    }

    //enable plugin in the default authentication method
    enable_edwiserbridge_plugin();

    // check and upgrade webservice functions.
    check_and_update_webservice_functions_edwiserbridge();
}