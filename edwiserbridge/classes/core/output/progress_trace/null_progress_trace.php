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
 * Null progress trace.
 * A progress trace implementation that does nothing.
 *
 * @package    auth_edwiserbridge
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\output\progress_trace;

/**
 * Null progress trace class.
 * Provides no-op implementations of the progress trace methods.
 */
class null_progress_trace {
    /**
     * Outputs a progress message.
     *
     * @param string $message The message to output.
     */
    public function output($message) {
    }

    /**
     * Called when the progress is finished.
     */
    public function finished() {
    }
}
