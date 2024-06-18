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

defined('MOODLE_INTERNAL') || die();

use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/enrol/cohort/locallib.php');
require_once($CFG->dirroot . '/user/externallib.php');
require_once($CFG->dirroot . '/cohort/externallib.php');
require_once($CFG->dirroot . '/enrol/externallib.php');
require_once($CFG->dirroot. '/user/lib.php');
require_once($CFG->dirroot. '/cohort/lib.php');

/**
 * Trait implementing the external function auth_edwiserbridge_delete_cohort
 */
trait delete_cohort {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function auth_edwiserbridge_delete_cohort_parameters() {
        return new external_function_parameters(
            [
                'cohort' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'cohortId' => new external_value(
                                PARAM_INT,
                                'Cohort Id which will be deleted in Moodle',
                                VALUE_REQUIRED
                            ),
                        ]
                    )
                ),
            ]
        );
    }

    /**
     * Function responsible for enrolling cohort in course
     * @return string welcome message
     */
    public static function auth_edwiserbridge_delete_cohort($cohort) {
        global $USER, $DB;

        // Validation for context is needed.
        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        
        // Parameter validation.
        $params = self::validate_parameters(
            self::auth_edwiserbridge_delete_cohort_parameters(),
            ['cohort' => $cohort]
        );

        // Context validation.
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        // Capability checking.
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }

        $response = ["status" => 1];

        foreach ($params["cohort"] as $cohortdetails) {
            try {
                $cohort = $DB->get_record('cohort', ['id' => $cohortdetails["cohortId"]], '*', MUST_EXIST);
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
     * Returns description of method result value
     * @return external_description
     */
    public static function auth_edwiserbridge_delete_cohort_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(
                    PARAM_TEXT,
                    'This will return 1 if successful connection and 0 on failure'
                ),
            ]
        );
    }
}
