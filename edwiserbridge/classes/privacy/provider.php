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
 * Privacy implementation for auth_edwiserbridge.
 * Privacy Subsystem implementation for auth_edwiserbridge.
 *
 * @package    auth_edwiserbridge
 * @copyright  2016 WisdmLabs (https://wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_edwiserbridge\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * The edwiserbridge plugin stores a user preference data.
 *
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    // This plugin has data.
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\user_preference_provider {

    /**
     * Returns meta data about this system.
     *
     * @param  collection $collection The initialised item collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_external_location_link('wp_site', [
            'userid'        => 'privacy:metadata:wp_site:userid',
            'username'      => 'privacy:metadata:wp_site:username',
            'firstname'     => 'privacy:metadata:wp_site:firstname',
            'lastname'      => 'privacy:metadata:wp_site:lastname',
            'email'         => 'privacy:metadata:wp_site:email',
            'password'      => 'privacy:metadata:wp_site:password',
            'country'       => 'privacy:metadata:wp_site:country',
            'city'          => 'privacy:metadata:wp_site:city',
            'phone'         => 'privacy:metadata:wp_site:phone',
            'custom_fields' => 'privacy:metadata:wp_site:custom_fields',
        ], 'privacy:metadata:wp_site');

        // This plugin stores a short-lived SSO session token as a user preference
        // (auth_edwiserbridge_set_user_session() in lib.php, preference key
        // 'eb_sso_user_session_id'). It's normally deleted immediately after each
        // login/logout completes, but the previous version of this provider never
        // declared or handled it at all.
        $collection->add_user_preference(
            'eb_sso_user_session_id',
            'privacy:metadata:preference:eb_sso_user_session_id'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // The preference is normally short-lived (deleted right after each
        // login/logout completes), so most of the time this will find nothing -
        // but if a request happens mid-handshake, it should still be reported.
        $preference = get_user_preferences('eb_sso_user_session_id', null, $userid);
        if (null !== $preference) {
            $contextlist->add_user_context($userid);
        }

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_user) {
            return;
        }

        $preference = get_user_preferences('eb_sso_user_session_id', null, $context->instanceid);
        if (null !== $preference) {
            $userlist->add_user($context->instanceid);
        }
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_user || $context->instanceid != $user->id) {
                continue;
            }

            $preference = get_user_preferences('eb_sso_user_session_id', null, $user->id);
            if (null === $preference) {
                continue;
            }

            $data = (object) [
                // Deliberately not exporting the raw encrypted payload itself -
                // it's an opaque, short-lived bearer token, not meaningful
                // personal data on its own.
                'description' => get_string('privacy:metadata:preference:eb_sso_user_session_id', 'auth_edwiserbridge'),
                'present'     => true,
            ];

            writer::with_context($context)->export_data(
                [get_string('pluginname', 'auth_edwiserbridge')],
                $data
            );
        }
    }

    /**
     * Export all user preferences for the specified user.
     *
     * @param int $userid The user to export preferences for.
     */
    public static function export_user_preferences(int $userid) {
        $preference = get_user_preferences('eb_sso_user_session_id', null, $userid);
        if (null === $preference) {
            return;
        }

        writer::export_user_preference(
            'auth_edwiserbridge',
            'eb_sso_user_session_id',
            $preference,
            get_string('privacy:metadata:preference:eb_sso_user_session_id', 'auth_edwiserbridge')
        );
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        if (!$context instanceof \context_user) {
            return;
        }

        unset_user_preference('eb_sso_user_session_id', $context->instanceid);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_user || $context->instanceid != $user->id) {
                continue;
            }

            unset_user_preference('eb_sso_user_session_id', $user->id);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_user) {
            return;
        }

        foreach ($userlist->get_userids() as $userid) {
            unset_user_preference('eb_sso_user_session_id', $userid);
        }
    }
}
