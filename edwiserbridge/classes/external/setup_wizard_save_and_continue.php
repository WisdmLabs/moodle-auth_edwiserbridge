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
 * Setup Wizard Save and Continue.
 * Functionality to save and continue setup wizard steps data.
 *
 * @package    auth_edwiserbridge
 * @category   external
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace auth_edwiserbridge\external;

use auth_edwiserbridge;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use core\context\system as context_system;

/**
 * Trait implementing the external function auth_edwiserbridge_setup_wizard_save_and_continue
 */
trait setup_wizard_save_and_continue {

    /**
     * Returns the parameter description for the auth_edwiserbridge_setup_wizard_save_and_continue external function.
     *
     * @return external_function_parameters The parameter description.
     */
    public static function auth_edwiserbridge_setup_wizard_save_and_continue_parameters() {
        return new external_function_parameters([
            'data' => new external_value(
                PARAM_RAW, 
                get_string('web_service_name', 'auth_edwiserbridge'), 
                VALUE_REQUIRED,
                null,
                NULL_NOT_ALLOWED
            )
        ]);
    }

    /**
     * Saves and continues the setup wizard steps data.
     *
     * @param string $data The data to be saved and continued.
     * @return void
     */
    public static function auth_edwiserbridge_setup_wizard_save_and_continue($data) {
        global $CFG;

        // Validation for context is needed.
        $systemcontext = context_system::instance();
        self::validate_context($systemcontext);
        require_capability('moodle/site:config', $systemcontext);
        
        global $PAGE;
        $PAGE->set_context(context_system::instance());

        $response = [
            'html_data' => '',
            'title'     => '',
        ];

        $data = json_decode( $data );

        $currentstep = $data->current_step;
        $nextstep = $data->next_step;
        $isnextsubstep = $data->is_next_sub_step;

        $setupwizardhandler = new auth_edwiserbridge\local\setup_wizard();
        $steps = $setupwizardhandler->eb_setup_wizard_get_steps();

        // Check if there are any sub steps available.
        $function = $steps[$nextstep]['function'];
        // Save progress data.
        set_config('eb_setup_progress', $currentstep, 'auth_edwiserbridge');

        switch ( $currentstep ) {
            case 'web_service':
                // Create web service and update data in EB settings.
                $settingshandler = new auth_edwiserbridge\local\settings_handler();
                // Get main admin user.
                $adminuser = get_admin();

                if ( isset( $data->service_name ) && isset($data->existing_service) && ! $data->existing_service ) {
                    $response = $settingshandler->eb_create_externle_service( $data->service_name , $adminuser->id );
                } else if (isset($data->service_name) && isset($data->existing_service) && $data->existing_service ) {
                    // Set Service. edwiser_bridge_last_created_token.
                    set_config('ebexistingserviceselect', $data->service_name, 'auth_edwiserbridge');

                    // Select token update web services and set token.
                    // If token is not created dreate token.
                    $token = $settingshandler->eb_create_token( $data->service_name, $adminuser->id );

                    // Set last created token.
                    set_config('edwiser_bridge_last_created_token', $token, 'auth_edwiserbridge');
                }
               break;
            case 'wordpress_site_details':
                if ( isset( $data->site_name ) && ! empty( $data->site_name ) && isset( $data->url ) && ! empty( $data->url ) ) {
                    // Get existing data.
                    $sites = auth_edwiserbridge_get_connection_settings();
                    $connectionsettings = $sites['eb_connection_settings'];

                    $edwiser_bridge_last_created_token = get_config('auth_edwiserbridge', 'edwiser_bridge_last_created_token');

                    $token = !empty($edwiser_bridge_last_created_token) ? $edwiser_bridge_last_created_token : ' - ';
                    // Update Moodle Wordpress site details.
                    $connectionsettings[$data->site_name] = [
                        "wp_url"   => $data->url,
                        "wp_token" => $token,
                        "wp_name"  => $data->site_name,
                    ];

                    set_config( 'eb_connection_settings', json_encode( $connectionsettings ), 'auth_edwiserbridge' );
                    set_config( 'eb_setup_wp_site_name', $data->site_name, 'auth_edwiserbridge' );
                } else if ( isset( $data->site_name ) ) {
                    set_config( 'eb_setup_wp_site_name', $data->site_name, 'auth_edwiserbridge' );
                }
               break;
            case 'user_and_course_sync':
                $eb_sync_settings = get_config('auth_edwiserbridge', 'eb_synch_settings');
                // Update Moodle Wordpress site details.
                $existingsynchsettings = !empty($eb_sync_settings) ? json_decode($eb_sync_settings, true) : [];
                $eb_setup_wp_site_name = get_config('auth_edwiserbridge', 'eb_setup_wp_site_name');
                $synchsettings = $existingsynchsettings;
                $sitename = $eb_setup_wp_site_name;

                $synchsettings[$sitename] = [
                    "course_enrollment"    => $data->user_enrollment,
                    "course_un_enrollment" => $data->user_unenrollment,
                    "user_creation"        => $data->user_creation,
                    "user_deletion"        => $data->user_deletion,
                    "course_creation"      => $data->course_creation,
                    "course_deletion"      => $data->course_deletion,
                    "user_updation"        => $data->user_update,
                ];
                set_config( 'eb_synch_settings', json_encode($synchsettings), 'auth_edwiserbridge' );
               break;
            case 'complete_details':
                set_config('eb_setup_progress', '', 'auth_edwiserbridge');
               break;
            default:
               break;
        }

        // Get next step.

        /*
        * There are multiple steps inside 1 step which are listed below.
        * 1. Web sevice
        *    a. web service
        *    b. WP site details
        *
        * 2. user and course sync
        *    a. User and course sync
        *    b. success screens
        */
        if ( 'complete_details' != $currentstep ) {
            $nextstephtml = $setupwizardhandler->$function(1);
            $title          = $setupwizardhandler->eb_get_step_title($nextstep);

            $response = [
                'html_data' => $nextstephtml,
                'title'     => $title,
            ];
        }
        return $response;
    }

    /**
     * Returns the description of the result value for the auth_edwiserbridge_setup_wizard_save_and_continue() function.
     *
     * @return external_single_structure The structure describing the result value, which contains the HTML content for the next setup wizard step and the title of that step.
     */
    public static function auth_edwiserbridge_setup_wizard_save_and_continue_returns() {
        new external_single_structure(
            [
                'html_data' => new external_value(PARAM_RAW, get_string('setup_wizard_next_step_html_data', 'auth_edwiserbridge')),
                'title'     => new external_value(PARAM_RAW, get_string('setup_wizard_next_step_title', 'auth_edwiserbridge')),
            ]
        );
    }
}
