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
 * AJAX endpoint backing the quick role assignment panel.
 *
 * @package    core_my
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../config.php');

require_login();

$context = context_system::instance();

$action = optional_param('action', 'list', PARAM_ALPHA);
$roleid = optional_param('roleid', 0, PARAM_INT);
$mode = optional_param('mode', 'notassigned', PARAM_ALPHA);
$search = optional_param('search', '', PARAM_TEXT);

$mutating = in_array($action, ['assign', 'unassign'], true);

header('Content-Type: application/json; charset=utf-8');

try {
    if (!has_capability('moodle/role:assign', $context)) {
        throw new moodle_exception('ra_norights', 'my');
    }

    \core_my\output\role_assigner::require_assignable_role($roleid);

    if ($mutating) {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            throw new moodle_exception('invalidrequest', 'error');
        }
        require_sesskey();

        $userids = array_filter(array_map('intval', explode(',', (string) optional_param('userids', '', PARAM_RAW))));
        if (empty($userids)) {
            throw new moodle_exception('ra_nothing_selected', 'my');
        }

        $changed = 0;
        $assignparams = [
            'roleid' => $roleid,
            'contextid' => $context->id,
        ];
        foreach ($userids as $userid) {
            $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0]);
            if (!$user) {
                continue;
            }
            $exists = $DB->record_exists('role_assignments', $assignparams + ['userid' => $userid]);
            if ($action === 'assign') {
                if ($exists) {
                    continue;
                }
                role_assign($roleid, $userid, $context->id);
            } else {
                if (!$exists) {
                    continue;
                }
                role_unassign($roleid, $userid, $context->id);
            }
            $changed++;
        }

        $message = $action === 'assign'
            ? get_string('ra_done_assign', 'my')
            : get_string('ra_done_remove', 'my');

        $data = \core_my\output\role_assigner::fetch($roleid, $mode, $search);
        $data['changed'] = $changed;

        echo json_encode([
            'status' => 'success',
            'message' => $message,
            'changed' => $changed,
            'data' => $data,
        ]);
        exit();
    }

    echo json_encode([
        'status' => 'success',
        'data' => \core_my\output\role_assigner::fetch($roleid, $mode, $search),
    ]);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

exit();
