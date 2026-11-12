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
 * Compatibility shims for the Edwiser Bridge authentication plugin.
 *
 * Provides class aliases and stub classes for core Moodle classes that may
 * not exist in older Moodle versions supported by this plugin.
 *
 * @package    auth_edwiserbridge
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Exception compatibility.
if (!class_exists('core\exception\moodle_exception') && class_exists('moodle_exception')) {
    class_alias('moodle_exception', 'core\exception\moodle_exception');
}

// External API compatibility.
if (!class_exists('core_external\external_api') && class_exists('external_api')) {
    class_alias('external_api', 'core_external\external_api');
}

// External structure classes compatibility.
$externalclasses = ['external_function_parameters', 'external_value', 'external_single_structure', 'external_multiple_structure'];
foreach ($externalclasses as $class) {
    if (!class_exists('core_external\\' . $class) && class_exists($class)) {
        class_alias($class, 'core_external\\' . $class);
    }
}

// Context classes compatibility.
$contextclasses = ['system' => 'context_system', 'user' => 'context_user', 'course' => 'context_course'];
foreach ($contextclasses as $type => $global) {
    $ns = "core\\context\\$type";
    if (!class_exists($ns) && class_exists($global)) {
        class_alias($global, $ns);
    }
}

// Output/Notification compatibility.
if (!class_exists('core\output\notification')) {
    /**
     * Stub for the core output notification class.
     *
     * @package    auth_edwiserbridge
     * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    class core_output_notification {
        /** @var string Notification type for errors. */
        const NOTIFY_ERROR = 'error';
        /** @var string Notification type for warnings. */
        const NOTIFY_WARNING = 'warning';
        /** @var string Notification type for success messages. */
        const NOTIFY_SUCCESS = 'success';
        /** @var string Notification type for informational messages. */
        const NOTIFY_INFO = 'info';
    }
}
if (!class_exists('core\notification')) {
    // phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses -- Intentional: conditional stub class.
    /**
     * Stub for the core notification class.
     *
     * @package    auth_edwiserbridge
     * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    class core_notification {
        /** @var string Notification type for errors. */
        const NOTIFY_ERROR = 'error';
        /** @var string Notification type for warnings. */
        const NOTIFY_WARNING = 'warning';
        /** @var string Notification type for success messages. */
        const NOTIFY_SUCCESS = 'success';
        /** @var string Notification type for informational messages. */
        const NOTIFY_INFO = 'info';

        /**
         * Adds a notification message.
         *
         * Fallback: echo notification as HTML. In real use, you may want to use print_error or redirect.
         *
         * @param string $msg The notification message.
         * @param string $type The notification type.
         */
        public static function add($msg, $type = self::NOTIFY_INFO) {
            echo '<div class="notify' . htmlspecialchars($type) . '">' . htmlspecialchars($msg) . '</div>';
        }
    }
    // phpcs:enable PSR1.Classes.ClassDeclaration.MultipleClasses
}

// Session manager compatibility.
if (!class_exists('core\session\manager') && class_exists('session_manager')) {
    class_alias('session_manager', 'core\session\manager');
}

// Component compatibility.
if (!class_exists('core\component') && class_exists('component')) {
    class_alias('component', 'core\component');
}

// HTML Writer compatibility.
if (!class_exists('core\output\html_writer') && class_exists('html_writer')) {
    class_alias('html_writer', 'core\output\html_writer');
}

// Progress trace compatibility.
// Use a file-based stub instead of eval for null_progress_trace.
if (!class_exists('core\output\progress_trace\null_progress_trace')) {
    require_once(__DIR__ . '/classes/core/output/progress_trace/null_progress_trace.php');
}

// Update/Remote info compatibility (stub if not present).
if (!class_exists('core\update\remote_info')) {
    // phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses -- Intentional: conditional stub class.
    /**
     * Stub for the core update remote info class.
     *
     * Add minimal properties/methods if needed for plugin logic.
     *
     * @package    auth_edwiserbridge
     * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    class core_update_remote_info {
    }
    // phpcs:enable PSR1.Classes.ClassDeclaration.MultipleClasses
}
