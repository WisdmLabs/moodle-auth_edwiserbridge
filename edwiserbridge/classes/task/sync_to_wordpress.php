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
 * Ad-hoc task to sync data to WordPress asynchronously.
 *
 * Instead of making synchronous HTTP calls during event handling,
 * this task queues the API call to run in the background via Moodle's
 * task system, preventing blocking delays during user/course operations.
 *
 * @package    auth_edwiserbridge
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_edwiserbridge\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Ad-hoc task that sends data to a WordPress site via the Edwiser Bridge API.
 *
 * Custom data format:
 * {
 *     "url": "https://wordpress-site.com",
 *     "requestdata": { "action": "...", ... },
 *     "retries": 0
 * }
 */
class sync_to_wordpress extends \core\task\adhoc_task {

    /** @var int Maximum number of retry attempts. */
    const MAX_RETRIES = 3;

    /**
     * Get the name of the task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_sync_to_wordpress', 'auth_edwiserbridge');
    }

    /**
     * Execute the task - send the queued data to WordPress.
     */
    public function execute() {
        global $CFG;
        require_once($CFG->dirroot . '/auth/edwiserbridge/lib.php');

        $data = $this->get_custom_data();

        if (empty($data->url) || empty($data->requestdata)) {
            mtrace('auth_edwiserbridge sync_to_wordpress: Missing URL or request data, skipping.');
            return;
        }

        $requestdata = (array) $data->requestdata;
        $action = $requestdata['action'] ?? 'unknown';
        $url = $data->url;
        $retries = isset($data->retries) ? (int) $data->retries : 0;

        mtrace("auth_edwiserbridge sync_to_wordpress: Syncing '{$action}' to {$url}");

        $apihandler = \auth_edwiserbridge\local\api_handler::instance();
        $result = $apihandler->connect_to_wp_with_args($url, $requestdata);

        if (!empty($result['error'])) {
            $errormsg = $result['msg'] ?? 'Unknown error';
            mtrace("auth_edwiserbridge sync_to_wordpress: Failed '{$action}' to {$url}: {$errormsg}");

            // Retry with exponential backoff if under max retries.
            if ($retries < self::MAX_RETRIES) {
                $retry = new self();
                $retrydata = clone $data;
                $retrydata->retries = $retries + 1;
                $retry->set_custom_data($retrydata);
                // Exponential backoff: 60s, 300s, 900s.
                $delay = pow(5, $retries) * 60;
                $retry->set_next_run_time(time() + $delay);
                \core\task\manager::queue_adhoc_task($retry);
                mtrace("auth_edwiserbridge sync_to_wordpress: Queued retry " . ($retries + 1) .
                    " for '{$action}' in {$delay}s");
            } else {
                mtrace("auth_edwiserbridge sync_to_wordpress: Max retries reached for '{$action}' to {$url}");
            }
        } else {
            mtrace("auth_edwiserbridge sync_to_wordpress: Successfully synced '{$action}' to {$url}");
        }
    }
}
