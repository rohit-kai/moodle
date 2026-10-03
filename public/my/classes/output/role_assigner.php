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
 * Data provider for the quick role assignment panel on the admin dashboard.
 *
 * @package    core_my
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_my\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Builds the template context and the AJAX payload for the role assignment panel.
 */
class role_assigner {

    /** Only show users that do not hold the role yet. */
    public const MODE_NOTASSIGNED = 'notassigned';

    /** Only show users that already hold the role. */
    public const MODE_ASSIGNED = 'assigned';

    /** Show every user regardless of the role. */
    public const MODE_ALL = 'all';

    /** Hard cap so a huge site cannot render an unbounded table. */
    public const PAGE_LIMIT = 200;

    /**
     * All roles that may be assigned on the site context.
     *
     * @param int $selectedroleid role to flag as selected, 0 picks the first one.
     * @return array
     */
    public static function get_role_options(int $selectedroleid = 0): array {
        global $DB;

        $systemcontext = \context_system::instance();
        $selected = null;
        $fallback = 0;
        $options = [];

        foreach ($DB->get_records('role', [], 'sortorder ASC', 'id, name, shortname, archetype') as $role) {
            if ($role->archetype === 'guest') {
                continue;
            }
            if ($fallback === 0) {
                $fallback = (int) $role->id;
            }
            $options[] = [
                'id' => (int) $role->id,
                'name' => role_get_name($role, $systemcontext),
                'shortname' => $role->shortname,
            ];
        }

        $valid = array_column($options, 'id');
        $selected = ($selectedroleid > 0 && in_array($selectedroleid, $valid, true)) ? $selectedroleid : $fallback;

        foreach ($options as &$option) {
            $option['selected'] = ($option['id'] === $selected);
        }
        unset($option);

        return $options;
    }

    /**
     * The role id that the option list ended up selecting.
     *
     * @param int $requested
     * @return int
     */
    public static function resolve_role_id(int $requested = 0): int {
        foreach (self::get_role_options($requested) as $option) {
            if (!empty($option['selected'])) {
                return (int) $option['id'];
            }
        }
        return 0;
    }

    /**
     * Guard used by the web service endpoint.
     *
     * @param int $roleid
     * @return \stdClass
     * @throws \moodle_exception
     */
    public static function require_assignable_role(int $roleid): \stdClass {
        global $DB;

        $role = $roleid > 0 ? $DB->get_record('role', ['id' => $roleid]) : false;
        if (!$role || $role->archetype === 'guest') {
            throw new \moodle_exception('ra_badrole', 'my');
        }
        return $role;
    }

    /**
     * @param string $mode
     * @return string
     */
    public static function normalise_mode(string $mode): string {
        return in_array($mode, [self::MODE_NOTASSIGNED, self::MODE_ASSIGNED, self::MODE_ALL], true)
            ? $mode
            : self::MODE_NOTASSIGNED;
    }

    /**
     * How many users sit on one side of the assignment for a role, on the site context.
     *
     * @param int $roleid
     * @param bool $assigned true counts holders, false counts everyone without it.
     * @return int
     */
    protected static function count_for_role(int $roleid, bool $assigned): int {
        global $DB;

        if ($roleid <= 0) {
            return 0;
        }

        $context = \context_system::instance();
        $op = $assigned ? '' : 'NOT ';

        $sql = "SELECT COUNT(1)
                  FROM {user} u
                 WHERE u.deleted = 0
                   AND u.id > 1
                   AND {$op}EXISTS (SELECT 1
                                      FROM {role_assignments} ra
                                     WHERE ra.userid = u.id
                                       AND ra.roleid = :roleid
                                       AND ra.contextid = :ctxid)";

        return (int) $DB->count_records_sql($sql, [
            'roleid' => $roleid,
            'ctxid' => $context->id,
        ]);
    }

    /**
     * One page of users matching the role / mode / search filter.
     *
     * @param int $roleid
     * @param string $mode
     * @param string $search
     * @return array
     */
    public static function list_users(int $roleid, string $mode, string $search): array {
        global $DB;

        $mode = self::normalise_mode($mode);
        $search = trim($search);

        if ($roleid <= 0) {
            return [];
        }

        $context = \context_system::instance();
        $wheres = ['u.deleted = 0', 'u.id > 1'];
        $params = [];

        if ($search !== '') {
            $wheres[] = "(" . $DB->sql_like('u.firstname', ':s1', false) . " OR "
                . $DB->sql_like('u.lastname', ':s2', false) . " OR "
                . $DB->sql_like('u.email', ':s3', false) . " OR "
                . $DB->sql_like('u.username', ':s4', false) . ")";
            $params['s1'] = "%$search%";
            $params['s2'] = "%$search%";
            $params['s3'] = "%$search%";
            $params['s4'] = "%$search%";
        }

        $holdsexpression = "EXISTS (SELECT 1
                                      FROM {role_assignments} ra
                                     WHERE ra.userid = u.id
                                       AND ra.roleid = :aroleid
                                       AND ra.contextid = :actxid)";
        $params['aroleid'] = $roleid;
        $params['actxid'] = $context->id;

        if ($mode === self::MODE_NOTASSIGNED) {
            $wheres[] = "NOT $holdsexpression";
        } else if ($mode === self::MODE_ASSIGNED) {
            $wheres[] = $holdsexpression;
        }

        $sql = "SELECT u.id, u.firstname, u.lastname, u.username, u.email,
                       u.suspended, u.confirmed, u.lastaccess
                  FROM {user} u
                 WHERE " . implode(' AND ', $wheres) . "
              ORDER BY u.lastname ASC, u.firstname ASC, u.id ASC";

        $records = $DB->get_records_sql($sql, $params, 0, self::PAGE_LIMIT);
        if (empty($records)) {
            return [];
        }

        // Resolve the real assignment flag so "All users" can badge both sides.
        $assignedids = [];
        if ($mode !== self::MODE_NOTASSIGNED) {
            $userids = array_map('intval', array_keys($records));
            [$insql, $iparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'uid');
            $assignedids = $DB->get_fieldset_select(
                'role_assignments',
                'userid',
                "contextid = :ctxid AND roleid = :roleid AND userid $insql",
                ['ctxid' => $context->id, 'roleid' => $roleid] + $iparams
            );
        }

        $now = time();
        $rows = [];
        foreach ($records as $user) {
            $assigned = $mode === self::MODE_NOTASSIGNED
                ? false
                : in_array((int) $user->id, array_map('intval', (array) $assignedids), true);

            if ($user->suspended) {
                $statushtml = '<span class="badge bg-danger">' . s(get_string('status_suspended', 'my')) . '</span>';
            } else if (!$user->confirmed) {
                $statushtml = '<span class="badge bg-warning text-dark">' . s(get_string('status_unconfirmed', 'my')) . '</span>';
            } else {
                $statushtml = '<span class="badge bg-success">' . s(get_string('status_confirmed', 'my')) . '</span>';
            }

            $rolebadge = $assigned
                ? '<span class="badge bg-success">' . s(get_string('ra_status_assigned', 'my')) . '</span>'
                : '<span class="badge bg-secondary">' . s(get_string('ra_status_notassigned', 'my')) . '</span>';

            $rows[] = [
                'id' => (int) $user->id,
                'name' => fullname($user),
                'username' => $user->username,
                'email' => $user->email ?: '-',
                'accountstatus' => $statushtml,
                'rolebadge' => $rolebadge,
                'assigned' => $assigned,
                'lastaccess' => $user->lastaccess
                    ? format_time($now - $user->lastaccess) . ' ago'
                    : 'Never',
            ];
        }

        return $rows;
    }

    /**
     * Everything the role assignment tab needs, flattened for Mustache.
     *
     * @param int $roleid 0 selects the default role.
     * @param string $mode
     * @param string $search
     * @return array
     */
    public static function fetch(int $roleid = 0, string $mode = self::MODE_NOTASSIGNED, string $search = ''): array {
        $mode = self::normalise_mode($mode);
        $search = trim($search);

        $options = self::get_role_options($roleid);
        $roleid = self::resolve_role_id($roleid);

        $modes = [
            ['value' => self::MODE_NOTASSIGNED, 'name' => get_string('ra_mode_notassigned', 'my')],
            ['value' => self::MODE_ASSIGNED, 'name' => get_string('ra_mode_assigned', 'my')],
            ['value' => self::MODE_ALL, 'name' => get_string('ra_mode_all', 'my')],
        ];
        foreach ($modes as &$entry) {
            $entry['selected'] = ($entry['value'] === $mode);
        }
        unset($entry);

        $rows = $roleid > 0 ? self::list_users($roleid, $mode, $search) : [];

        return [
            'roleid' => $roleid,
            'role_options' => $options,
            'role_modes' => $modes,
            'mode' => $mode,
            'search' => $search,
            'count_notassigned' => number_format(self::count_for_role($roleid, false)),
            'count_assigned' => number_format(self::count_for_role($roleid, true)),
            'shown' => number_format(count($rows)),
            'bulk_label' => $mode === self::MODE_ASSIGNED
                ? get_string('ra_remove_selected', 'my')
                : get_string('ra_assign_selected', 'my'),
            'rows' => $rows,
            'hasrows' => !empty($rows),
            'emptymessage' => get_string('ra_empty', 'my'),
        ];
    }
}
