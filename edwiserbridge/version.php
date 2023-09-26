<?php
// This file is part of Moodle - http://moodle.org/
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
 * Version information
 * This version is compatible with WordPress plugin Edwiser Bridge Single Sign On (version 1.2 or higher)
 *
 * @version    3.0.0
 * @package    auth_edwiserbridge
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2023092500;    // The current plugin version (Date: YYYYMMDDXX).
$plugin->release   = '3.0.1';
$plugin->requires  = '2016052318'; // Requires this Moodle version (Moodle V3.1.0).
$plugin->maturity  = MATURITY_STABLE;
$plugin->component = 'auth_edwiserbridge'; // Full name of the plugin (used for diagnostics).
