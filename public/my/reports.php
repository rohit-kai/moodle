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
 * Report generation endpoint (AJAX & CSV Download).
 *
 * @package    core_my
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../config.php');
require_once($CFG->dirroot . '/my/lib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$action = optional_param('action', 'fetch', PARAM_ALPHA);
$reporttype = optional_param('report_type', 'user_activity', PARAM_ALPHANUMEXT);
$category = optional_param('category', 'all', PARAM_ALPHA);
$daterange = optional_param('daterange', 0, PARAM_INT);
$roleid = optional_param('roleid', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_RAW);

$filters = [
    'category' => $category,
    'daterange' => $daterange,
    'roleid' => $roleid,
    'search' => $search,
];

if ($action === 'export') {
    \core_my\output\report_generator::export_csv($reporttype, $filters);
    exit();
}

// Default AJAX JSON response.
header('Content-Type: application/json; charset=utf-8');

try {
    $data = \core_my\output\report_generator::generate($reporttype, $filters);
    echo json_encode(['status' => 'success', 'data' => $data]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();
