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
 * Event registration.
 * Functionality to manage event registration.
 *
 * @package    auth_edwiserbridge
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => 'core\event\user_enrolment_created',
        'callback'  => 'auth_edwiserbridge\observer::user_enrolment_created',
    ],
    [
        'eventname' => 'core\event\user_enrolment_deleted',
        'callback'  => 'auth_edwiserbridge\observer::user_enrolment_deleted',
    ],
    [
        'eventname' => 'core\event\user_created',
        'callback'  => 'auth_edwiserbridge\observer::user_created',
    ],
    [
        'eventname' => 'core\event\user_deleted',
        'callback'  => 'auth_edwiserbridge\observer::user_deleted',
    ],
    [
        'eventname' => 'core\event\user_updated',
        'callback'  => 'auth_edwiserbridge\observer::user_updated',
    ],
    [
        'eventname' => 'core\event\user_password_updated',
        'callback'  => 'auth_edwiserbridge\observer::user_password_updated',
    ],
    [
        'eventname' => 'core\event\course_created',
        'callback'  => 'auth_edwiserbridge\observer::course_created',
    ],
    [
        'eventname' => 'core\event\course_deleted',
        'callback'  => 'auth_edwiserbridge\observer::course_deleted',
    ],
    // Page view event for update check.
    [
        'eventname' => '\core\event\dashboard_viewed',
        'callback'  => 'auth_edwiserbridge\observer::dashboard_viewed',
    ],
];
