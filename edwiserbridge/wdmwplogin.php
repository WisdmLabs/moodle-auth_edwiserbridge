<?php
// This file is part of Edwiser Bridge Moodle Plugin.
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
 * This file triggers WordPress login after moodle login.
 *
 * @package   auth_edwiserbridge
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

global $CFG, $USER, $SESSION, $DB;

function wdmredirecttoroot() {
    global $CFG, $SESSION;
    $SESSION->wantsurl = $CFG->wwwroot;
    redirect($SESSION->wantsurl);
}

// Requested to wp login.
$wdmaction = optional_param('wdmaction', '', PARAM_ALPHA);
if ( !empty( $wdmaction ) && $wdmaction === 'login' ) {

    // User is not logged in or is a guest user.
    if ( ! isloggedin() || isguestuser() ) {
        wdmredirecttoroot();
    }

    $wpsiteurl = optional_param('wpsiteurl', '', PARAM_RAW);
    if ( empty( $wpsiteurl ) || ! filter_var( $wpsiteurl, FILTER_VALIDATE_URL ) ) {
        wdmredirecttoroot();
    }

    $mdluid = optional_param('mdl_uid', '', PARAM_RAW);
    if (empty($mdluid)) {
        wdmredirecttoroot();
    }

    // All checks are passed. Redirect to wp site for login.
    $verifycode = optional_param('verify_code', '', PARAM_RAW);
    $redirectto = strtok($wpsiteurl, '?') . '?wdmaction=login&mdl_uid=' . $mdluid . '&verify_code=' . $verifycode;

    redirect($redirectto);
}

wdmredirecttoroot();
