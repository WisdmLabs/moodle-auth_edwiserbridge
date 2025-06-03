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
 * Delete cohort.
 * Functionality to delete cohort.
 *
 * @package    auth_edwiserbridge
 * @category   external
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_edwiserbridge\external;

require_once(__DIR__ . '/../../compat.php');

defined('MOODLE_INTERNAL') || die();

require_once(
    $CFG->libdir . "/externallib.php"
);
require_once($CFG->dirroot . '/enrol/cohort/locallib.php');
require_once($CFG->dirroot . '/user/externallib.php');
require_once($CFG->dirroot . '/cohort/externallib.php');
require_once($CFG->dirroot . '/enrol/externallib.php');
require_once($CFG->dirroot. '/user/lib.php');
require_once($CFG->dirroot. '/cohort/lib.php');

use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use Exception;
use core\context\system as context_system;
use core\context\user as context_user;
use core\exception\moodle_exception as moodle_exception;
use core\context as context;

/**
 * Trait implementing the external function auth_edwiserbridge_delete_cohort
 */
trait delete_cohort {
    /**
     * Returns the description of the method parameters for the auth_edwiserbridge_delete_cohort function.
     *
     * @return external_function_parameters The description of the method parameters.
     */
    public static function auth_edwiserbridge_delete_cohort_parameters() {
        return new external_function_parameters(
            [
                'cohort' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'cohortid' => new external_value(
                                PARAM_INT,
                                get_string('web_service_cohort_id', 'auth_edwiserbridge'),
                                VALUE_REQUIRED
                            ),
                        ]
                    )
                ),
            ]
        );
    }

    /**
     * Deletes cohorts and returns the status of the operation.
     *
     * @param array $cohort An array of cohort IDs to be deleted.
     * @return array An associative array containing the status of the operation. The "status" key will be:
     *               - 1 if all cohorts were successfully deleted.
     *               - 0 if there was any error during the deletion process.
     */
    public static function auth_edwiserbridge_delete_cohort($cohort) {
        global $USER, $DB;
        
        // Validation for context is needed.
        $systemcontext = context_system::instance();
        self::validate_context($systemcontext);

        require_capability('moodle/cohort:manage', $systemcontext);
        
        // Parameter validation.
        $params = self::validate_parameters(
            self::auth_edwiserbridge_delete_cohort_parameters(),
            ['cohort' => $cohort]
        );

        // Context validation.
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        // Capability checking.
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }

        $response = ["status" => 1];

        foreach ($params["cohort"] as $cohortdetails) {
            try {
                $cohort = $DB->get_record('cohort', ['id' => $cohortdetails["cohortid"]], '*', MUST_EXIST);
                if (isset($cohort->id)) {
                    $context = context::instance_by_id($cohort->contextid, MUST_EXIST);
                    cohort_delete_cohort($cohort);
                } else {
                    throw new Exception('Error');
                }
            } catch (Exception $e) {
                $response['status'] = 0;
            }
        }
        return $response;
    }

    /**
     * Returns the external structure for the connection status.
     *
     * @return external_single_structure External structure containing:
     *                                   - status (int): Operation status (1 for success, 0 for failure)
     *                                   - message (string): Status message
     */
    public static function auth_edwiserbridge_delete_cohort_returns() {
        return new external_single_structure([
            'status' => new external_value(
                PARAM_INT,
                get_string('web_service_operation_status', 'auth_edwiserbridge'),
                VALUE_REQUIRED
            ),
        ]);
    }
}
