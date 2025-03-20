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
 * External API functions.
 * Functionality to add external API functions.
 *
 * @package    auth_edwiserbridge
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'auth_edwiserbridge_create_service' => [
        'classname'     => 'auth_edwiserbridge\external\api',
        'methodname'    => 'auth_edwiserbridge_create_service',
        'description'   => 'Create web service',
        'type'          => 'write',
        'capabilities'  => 'moodle/webservice:createtoken',
        'ajax'          => true,
    ],
    'auth_edwiserbridge_get_course_progress' => [
        'classname'     => 'auth_edwiserbridge\external\api',
        'methodname'    => 'auth_edwiserbridge_get_course_progress',
        'description'   => 'Get course wise progress',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'report/progress:view',
    ],
    'auth_edwiserbridge_test_connection' => [
        'classname'     => 'auth_edwiserbridge\external\api',
        'methodname'    => 'auth_edwiserbridge_test_connection',
        'description'   => 'Test connection with WordPress',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'moodle/site:config',
    ],
    'auth_edwiserbridge_validate_token' => [
        'classname'     => 'auth_edwiserbridge\external\api',
        'methodname'    => 'auth_edwiserbridge_validate_token',
        'description'   => 'Validate if token is matching and user has access to the service',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'moodle/site:config',
    ],
    'auth_edwiserbridge_get_site_data' => [
        'classname'     => 'auth_edwiserbridge\external\api',
        'methodname'    => 'auth_edwiserbridge_get_site_data',
        'description'   => 'Get site wise synchronization settings',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'moodle/site:config',
    ],
    'auth_edwiserbridge_get_users' => [
        'classname'     => 'auth_edwiserbridge\external\api',
        'methodname'    => 'auth_edwiserbridge_get_users',
        'description'   => 'Get Users',
        'type'          => 'read',
        'capabilities'  => 'moodle/user:viewalldetails',
        'ajax'          => true,
    ],
    'auth_edwiserbridge_link_service' => [
        'classname'     => 'auth_edwiserbridge\external\api',
        'methodname'    => 'auth_edwiserbridge_link_service',
        'description'   => 'Link web service',
        'type'          => 'write',
        'capabilities'  => 'moodle/webservice:managealltokens',
        'ajax'          => true,
    ],
    'auth_edwiserbridge_get_service_info' => [
        'classname'     => 'auth_edwiserbridge\external\api',
        'methodname'    => 'auth_edwiserbridge_get_service_info',
        'description'   => 'Get service information',
        'type'          => 'read',
        'capabilities'  => 'moodle/webservice:managealltokens',
        'ajax'          => true,
    ],
    'auth_edwiserbridge_get_edwiser_plugins_info' => [
        'classname'     => 'auth_edwiserbridge\external\api',
        'methodname'    => 'auth_edwiserbridge_get_edwiser_plugins_info',
        'description'   => 'Get plugins information',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'moodle/site:config',
    ],
    'auth_edwiserbridge_get_course_enrollment_method' => [
        'classname'     => 'auth_edwiserbridge\external\api',
        'methodname'    => 'auth_edwiserbridge_get_course_enrollment_method',
        'description'   => 'Get course enrollment methods',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'moodle/site:config',
    ],
    'auth_edwiserbridge_update_course_enrollment_method' => [
        'classname'     => 'auth_edwiserbridge\external\api',
        'methodname'    => 'auth_edwiserbridge_update_course_enrollment_method',
        'description'   => 'Update course enrollment method',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'moodle/course:enrolconfig',
    ],
    // Setup Wizard.
    'auth_edwiserbridge_setup_wizard_save_and_continue' => [
        'classname'     => 'auth_edwiserbridge\external\api',
        'methodname'    => 'auth_edwiserbridge_setup_wizard_save_and_continue',
        'description'   => 'Setup wizard save and continue functionality',
        'type'          => 'write',
        'capabilities'  => 'moodle/site:config',
        'ajax'          => true,
    ],
    'auth_edwiserbridge_enable_plugin_settings' => [
        'classname'     => 'auth_edwiserbridge\external\api',
        'methodname'    => 'auth_edwiserbridge_enable_plugin_settings',
        'description'   => 'Enables default plugin settings.',
        'type'          => 'write',
        'capabilities'  => 'moodle/site:config',
        'ajax'          => true,
    ],
    'auth_edwiserbridge_setup_test_connection' => [
        'classname'     => 'auth_edwiserbridge\external\api',
        'methodname'    => 'auth_edwiserbridge_setup_test_connection',
        'description'   => 'Enables default plugin settings.',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'moodle/site:config',
    ],
    'auth_edwiserbridge_get_mandatory_settings' => [
        'classname'     => 'auth_edwiserbridge\external\api',
        'methodname'    => 'auth_edwiserbridge_get_mandatory_settings',
        'description'   => 'Gets all mandatory settings for edwiser bridge.',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'moodle/site:config',
    ],
    'auth_edwiserbridge_get_courses' => [
        'classname'     => 'auth_edwiserbridge\external\api',
        'methodname'    => 'auth_edwiserbridge_get_courses',
        'description'   => 'Get Courses',
        'type'          => 'read',
        'capabilities'  => 'moodle/course:view',
        'ajax'          => true,
    ],
    'auth_edwiserbridge_verify_sso_token' => [
        'classname' => 'auth_edwiserbridge\external\api',
        'methodname' => 'auth_edwiserbridge_verify_sso_token',
        'description' => 'Verify SSO token',
        'type' => 'read',
        'capabilities' => 'moodle/site:config',
    ],
    'auth_edwiserbridge_manage_cohort_enrollment' => [
        'classname'   => 'auth_edwiserbridge\external\api',
        'methodname'  => 'auth_edwiserbridge_manage_cohort_enrollment',
        'description' => 'Enroll cohort in course',
        'type'        => 'write',
        'capabilities'  => 'moodle/cohort:assign'
    ],
    'auth_edwiserbridge_delete_cohort' => [
        'classname'   => 'auth_edwiserbridge\external\api',
        'methodname'  => 'auth_edwiserbridge_delete_cohort',
        'description' => 'Delete cohort',
        'type'        => 'write',
        'capabilities'  => 'moodle/cohort:manage'
    ],
    'auth_edwiserbridge_manage_user_cohort_enrollment' => [
        'classname'   => 'auth_edwiserbridge\external\api',
        'methodname'  => 'auth_edwiserbridge_manage_user_cohort_enrollment',
        'description' => 'Enroll user in cohort',
        'type'        => 'write',
        'capabilities'  => 'moodle/cohort:assign'
    ],
];
