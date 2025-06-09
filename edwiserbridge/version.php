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
 * Version information
 * This version is compatible with WordPress plugin Edwiser Bridge Single Sign On (version 3.0.7 or higher)
 *
 * @package    auth_edwiserbridge
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2025060900;    // The current plugin version (Date: YYYYMMDDXX).
$plugin->release   = '4.1.0';
// $plugin->requires  = 2023100900; // Requires this Moodle version (Moodle V4.3).
$plugin->requires  = 2020061500; // Requires this Moodle version (Moodle V3.9).
$plugin->maturity  = MATURITY_STABLE;
$plugin->component = 'auth_edwiserbridge'; // Full name of the plugin (used for diagnostics).
