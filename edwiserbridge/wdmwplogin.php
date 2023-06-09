<?php
/**
 * This file triggers WordPress login after moodle login.
 *
 * @author  WisdmLabs
 * @version 1.2
 */

require '../../config.php';

global $CFG, $USER, $SESSION, $DB;

function wdmRedirectToRoot()
{
    global $CFG, $SESSION;
    $SESSION->wantsurl = $CFG->wwwroot;
    redirect( $SESSION->wantsurl );
}

// Requested to wp login.
$wdmaction = optional_param('wdmaction', '', PARAM_ALPHA);
if ( !empty( $wdmaction ) && $wdmaction === 'login' ) {

    // User is not logged in or is a guest user.
    if ( ! isloggedin() || isguestuser() ) {
        wdmRedirectToRoot();
    }

    $wpsiteurl = optional_param('wpsiteurl', '', PARAM_RAW);
    if ( empty( $wpsiteurl ) || ! filter_var( $wpsiteurl, FILTER_VALIDATE_URL ) ) {
        wdmRedirectToRoot();
    }

    $mdl_uid = optional_param('mdl_uid', '', PARAM_RAW);
    if ( empty( $mdl_uid ) ) {
        wdmRedirectToRoot();
    }

    // All checks are passed. Redirect to wp site for login.
    $verify_code = optional_param('verify_code', '', PARAM_RAW);
    $redirect_to = strtok( $wpsiteurl, '?' ) .'?wdmaction=login&mdl_uid=' . $mdl_uid . '&verify_code=' . $verify_code;

    redirect( $redirect_to );
}

wdmRedirectToRoot();
