<?php
// File: auth/edwiserbridge/compat.php

// --- Exception compatibility ---
if (!class_exists('core\exception\moodle_exception') && class_exists('moodle_exception')) {
    class_alias('moodle_exception', 'core\exception\moodle_exception');
}

// --- External API compatibility ---
if (!class_exists('core_external\external_api') && class_exists('external_api')) {
    class_alias('external_api', 'core_external\external_api');
}

// --- External structure classes compatibility ---
foreach ([
    'external_function_parameters',
    'external_value',
    'external_single_structure',
    'external_multiple_structure',
] as $class) {
    if (!class_exists('core_external\\' . $class) && class_exists($class)) {
        class_alias($class, 'core_external\\' . $class);
    }
}

// --- Context classes compatibility ---
foreach ([
    'system' => 'context_system',
    'user' => 'context_user',
    'course' => 'context_course',
] as $type => $global) {
    $ns = "core\\context\\$type";
    if (!class_exists($ns) && class_exists($global)) {
        class_alias($global, $ns);
    }
}

// --- Output/Notification compatibility ---
if (!class_exists('core\output\notification')) {
    class core_output_notification {
        const NOTIFY_ERROR = 'error';
        const NOTIFY_WARNING = 'warning';
        const NOTIFY_SUCCESS = 'success';
        const NOTIFY_INFO = 'info';
    }
}
if (!class_exists('core\notification')) {
    class core_notification {
        const NOTIFY_ERROR = 'error';
        const NOTIFY_WARNING = 'warning';
        const NOTIFY_SUCCESS = 'success';
        const NOTIFY_INFO = 'info';
        public static function add($msg, $type = self::NOTIFY_INFO) {
            // Fallback: echo notification as HTML. In real use, you may want to use print_error or redirect.
            echo '<div class="notify' . htmlspecialchars($type) . '">' . htmlspecialchars($msg) . '</div>';
        }
    }
}

// --- Session manager compatibility ---
if (!class_exists('core\session\manager') && class_exists('session_manager')) {
    class_alias('session_manager', 'core\session\manager');
}

// --- Component compatibility ---
if (!class_exists('core\component') && class_exists('component')) {
    class_alias('component', 'core\component');
}

// --- HTML Writer compatibility ---
if (!class_exists('core\output\html_writer') && class_exists('html_writer')) {
    class_alias('html_writer', 'core\output\html_writer');
}

// --- Progress trace compatibility ---
// Use a file-based stub instead of eval for null_progress_trace.
if (!class_exists('core\output\progress_trace\null_progress_trace')) {
    require_once(__DIR__ . '/classes/core/output/progress_trace/null_progress_trace.php');
}

// --- Update/Remote info compatibility (stub if not present) ---
if (!class_exists('core\update\remote_info')) {
    class core_update_remote_info {
        // Add minimal properties/methods if needed for plugin logic.
    }
}

// --- Add more stubs/aliases as needed for other core classes used by the plugin ---
// (e.g., core\event\*, core\output\*, etc.)

// --- End of compat.php --- 
