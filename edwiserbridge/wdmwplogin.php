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
 * SSO login page.
 * This file is responsible for SSO login page.
 *
 * @package    auth_edwiserbridge
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
global $CFG;
require_once($CFG->dirroot.'/auth/edwiserbridge/lib.php');

// Requested to wp login.
$wdmaction = optional_param('wdmaction', '', PARAM_ALPHA);
if (!empty($wdmaction) && $wdmaction === 'login') {

    // User is not logged in or is a guest user.
    if (! isloggedin() || isguestuser()) {
        auth_edwiserbridge_redirect_to_root();
    }

    $wpsiteurl = optional_param('wpsiteurl', '', PARAM_RAW);
    if (empty($wpsiteurl) || ! filter_var($wpsiteurl, FILTER_VALIDATE_URL)) {
        auth_edwiserbridge_redirect_to_root();
    }

    $moodleuserid = optional_param('mdl_uid', '', PARAM_RAW);
    if (empty($moodleuserid)) {
        auth_edwiserbridge_redirect_to_root();
    }

    // All checks are passed. Redirect to wp site for login.
    $verifycode = optional_param('verify_code', '', PARAM_RAW);
    $redirectto = strtok($wpsiteurl, '?') .'?wdmaction=login&mdl_uid=' . $moodleuserid . '&verify_code=' . $verifycode;

    redirect($redirectto);
}

auth_edwiserbridge_redirect_to_root();
