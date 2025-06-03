<?php
namespace auth_edwiserbridge\external;

require_once(__DIR__ . '/../../compat.php');
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
 * Extends the external API of the Edwiser Bridge plugin.
 * This file aggregates all the external functions.
 *
 * @package    auth_edwiserbridge
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use core_external\external_api;

/**
 * Provides an external API for the Edwiser Bridge plugin.
 * This class aggregates all the external functions of the plugin.
 *
 * @package    auth_edwiserbridge
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api extends external_api {
    use create_service;
    use get_course_progress;
    use get_edwiser_plugins_info;
    use get_service_info;
    use get_site_data;
    use get_users;
    use get_courses;
    use link_service;
    use test_connection;
    use get_course_enrollment_method;
    use update_course_enrollment_method;
    use setup_wizard_save_and_continue;
    use enable_plugin_settings;
    use setup_test_connection;
    use get_mandatory_settings;
    use validate_token;

    // SSO functions.
    use verify_sso_token;

    // Bulk purchase functions.
    use delete_cohort;
    use manage_cohort_enrollment;
    use manage_user_cohort_enrollment;
}
