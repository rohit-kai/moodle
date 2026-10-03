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
 * Report Generator engine for custom admin dashboard reports.
 *
 * @package    core_my
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_my\output;

defined('MOODLE_INTERNAL') || die();

class report_generator {

    /**
     * Get list of available report catalog cards.
     *
     * @return array
     */
    public static function get_catalog(): array {
        return [
            [
                'id' => 'user_activity',
                'category' => 'users',
                'category_name' => get_string('cat_users', 'my'),
                'title' => get_string('report_user_activity_title', 'my'),
                'desc' => get_string('report_user_activity_desc', 'my'),
                'icon' => 'i/users',
                'badge' => 'Users',
                'badge_class' => 'bg-primary',
            ],
            [
                'id' => 'inactive_users',
                'category' => 'users',
                'category_name' => get_string('cat_users', 'my'),
                'title' => get_string('report_inactive_users_title', 'my'),
                'desc' => get_string('report_inactive_users_desc', 'my'),
                'icon' => 'i/user',
                'badge' => 'Audit',
                'badge_class' => 'bg-warning text-dark',
            ],
            [
                'id' => 'course_enrolment',
                'category' => 'courses',
                'category_name' => get_string('cat_courses', 'my'),
                'title' => get_string('report_course_enrolment_title', 'my'),
                'desc' => get_string('report_course_enrolment_desc', 'my'),
                'icon' => 'i/course',
                'badge' => 'Courses',
                'badge_class' => 'bg-success',
            ],
            [
                'id' => 'student_enrolments',
                'category' => 'courses',
                'category_name' => get_string('cat_courses', 'my'),
                'title' => get_string('report_student_enrolments_title', 'my'),
                'desc' => get_string('report_student_enrolments_desc', 'my'),
                'icon' => 'i/enrolusers',
                'badge' => 'Enrolments',
                'badge_class' => 'bg-info text-dark',
            ],
            [
                'id' => 'gradebook_summary',
                'category' => 'activities',
                'category_name' => get_string('cat_activities', 'my'),
                'title' => get_string('report_gradebook_summary_title', 'my'),
                'desc' => get_string('report_gradebook_summary_desc', 'my'),
                'icon' => 'i/grades',
                'badge' => 'Grades',
                'badge_class' => 'bg-secondary',
            ],
            [
                'id' => 'system_logs',
                'category' => 'system',
                'category_name' => get_string('cat_system', 'my'),
                'title' => get_string('report_system_logs_title', 'my'),
                'desc' => get_string('report_system_logs_desc', 'my'),
                'icon' => 'i/log',
                'badge' => 'System',
                'badge_class' => 'bg-dark',
            ],
        ];
    }

    /**
     * Generate report dataset based on report type and filter parameters.
     *
     * @param string $type
     * @param array $filters
     * @return array
     */
    public static function generate(string $type, array $filters = []): array {
        global $DB;

        $category = $filters['category'] ?? 'all';
        $daterange = (int) ($filters['daterange'] ?? 0);
        $roleid = (int) ($filters['roleid'] ?? 0);
        $search = trim($filters['search'] ?? '');

        $now = time();

        switch ($type) {
            case 'inactive_users':
                return self::generate_inactive_users_report($daterange, $search);

            case 'course_enrolment':
                return self::generate_course_enrolment_report($daterange, $search);

            case 'student_enrolments':
                return self::generate_student_enrolments_report($daterange, $search);

            case 'gradebook_summary':
                return self::generate_gradebook_summary_report($daterange, $search);

            case 'system_logs':
                return self::generate_system_logs_report($daterange, $search);

            case 'user_activity':
            default:
                return self::generate_user_activity_report($daterange, $roleid, $search);
        }
    }

    /**
     * Generate User Directory & Activity Report.
     */
    protected static function generate_user_activity_report(int $daterange, int $roleid, string $search): array {
        global $DB;

        $conditions = ["u.deleted = 0", "u.id > 1"];
        $params = [];

        if ($daterange > 0) {
            $conditions[] = "u.timecreated >= :since";
            $params['since'] = time() - ($daterange * DAYSECS);
        }

        if (!empty($search)) {
            $conditions[] = "(" . $DB->sql_like('u.firstname', ':s1', false) . " OR " .
                $DB->sql_like('u.lastname', ':s2', false) . " OR " .
                $DB->sql_like('u.email', ':s3', false) . " OR " .
                $DB->sql_like('u.username', ':s4', false) . ")";
            $params['s1'] = "%$search%";
            $params['s2'] = "%$search%";
            $params['s3'] = "%$search%";
            $params['s4'] = "%$search%";
        }

        $join = "";
        if ($roleid > 0) {
            $join = " JOIN {role_assignments} ra ON ra.userid = u.id AND ra.roleid = :roleid ";
            $params['roleid'] = $roleid;
        }

        $where = implode(" AND ", $conditions);

        $sql = "SELECT u.id, u.firstname, u.lastname, u.username, u.email, u.confirmed, u.suspended, u.timecreated, u.lastaccess
                  FROM {user} u
                $join
                 WHERE $where
              ORDER BY u.id DESC";

        $records = $DB->get_records_sql($sql, $params, 0, 100);

        $headers = ['ID', 'Full Name', 'Username', 'Email', 'Status', 'Registered Date', 'Last Access'];
        $rows = [];

        foreach ($records as $u) {
            $statusStr = 'Active';
            $statusClass = 'badge bg-success';
            if ($u->suspended) {
                $statusStr = 'Suspended';
                $statusClass = 'badge bg-danger';
            } else if (!$u->confirmed) {
                $statusStr = 'Unconfirmed';
                $statusClass = 'badge bg-warning text-dark';
            }

            $rows[] = [
                'id' => $u->id,
                'col1' => s(fullname($u)),
                'col2' => s($u->username),
                'col3' => s($u->email ?: '-'),
                'col4' => '<span class="' . $statusClass . '">' . $statusStr . '</span>',
                'col5' => $u->timecreated ? userdate($u->timecreated, '%d %b %Y') : '-',
                'col6' => $u->lastaccess ? format_time(time() - $u->lastaccess) . ' ago' : 'Never',
                'csv' => [
                    $u->id,
                    fullname($u),
                    $u->username,
                    $u->email ?: '-',
                    $statusStr,
                    $u->timecreated ? date('Y-m-d H:i:s', $u->timecreated) : '-',
                    $u->lastaccess ? date('Y-m-d H:i:s', $u->lastaccess) : 'Never',
                ],
            ];
        }

        return [
            'report_id' => 'user_activity',
            'title' => get_string('report_user_activity_title', 'my'),
            'total_count' => count($rows),
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * Generate Inactive Users Audit Report.
     */
    protected static function generate_inactive_users_report(int $daterange, string $search): array {
        global $DB;

        $days = $daterange > 0 ? $daterange : 30;
        $cutoff = time() - ($days * DAYSECS);

        $conditions = ["u.deleted = 0", "u.id > 1", "(u.lastaccess < :cutoff OR u.suspended = 1)"];
        $params = ['cutoff' => $cutoff];

        if (!empty($search)) {
            $conditions[] = "(" . $DB->sql_like('u.firstname', ':s1', false) . " OR " .
                $DB->sql_like('u.email', ':s2', false) . ")";
            $params['s1'] = "%$search%";
            $params['s2'] = "%$search%";
        }

        $sql = "SELECT u.id, u.firstname, u.lastname, u.username, u.email, u.suspended, u.timecreated, u.lastaccess
                  FROM {user} u
                 WHERE " . implode(" AND ", $conditions) . "
              ORDER BY u.lastaccess ASC";

        $records = $DB->get_records_sql($sql, $params, 0, 100);

        $headers = ['User ID', 'Full Name', 'Email', 'Account Status', 'Inactive Duration', 'Last Login'];
        $rows = [];

        foreach ($records as $u) {
            $statusStr = $u->suspended ? 'Suspended' : 'Inactive';
            $statusClass = $u->suspended ? 'badge bg-danger' : 'badge bg-warning text-dark';
            $inactiveTime = $u->lastaccess ? format_time(time() - $u->lastaccess) : 'Never Logged In';

            $rows[] = [
                'id' => $u->id,
                'col1' => s(fullname($u)),
                'col2' => s($u->email ?: '-'),
                'col3' => '<span class="' . $statusClass . '">' . $statusStr . '</span>',
                'col4' => $inactiveTime,
                'col5' => $u->lastaccess ? userdate($u->lastaccess, '%d %b %Y, %H:%M') : 'Never',
                'col6' => '-',
                'csv' => [
                    $u->id,
                    fullname($u),
                    $u->email ?: '-',
                    $statusStr,
                    $inactiveTime,
                    $u->lastaccess ? date('Y-m-d H:i:s', $u->lastaccess) : 'Never',
                ],
            ];
        }

        return [
            'report_id' => 'inactive_users',
            'title' => get_string('report_inactive_users_title', 'my'),
            'total_count' => count($rows),
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * Generate Course Enrolment Breakdown Report.
     */
    protected static function generate_course_enrolment_report(int $daterange, string $search): array {
        global $DB;

        $conditions = ["c.id <> :siteid"];
        $params = ['siteid' => SITEID];

        if ($daterange > 0) {
            $conditions[] = "c.timecreated >= :since";
            $params['since'] = time() - ($daterange * DAYSECS);
        }

        if (!empty($search)) {
            $conditions[] = "(" . $DB->sql_like('c.fullname', ':s1', false) . " OR " .
                $DB->sql_like('c.shortname', ':s2', false) . ")";
            $params['s1'] = "%$search%";
            $params['s2'] = "%$search%";
        }

        $sql = "SELECT c.id, c.fullname, c.shortname, c.visible, c.format, cc.name AS categoryname,
                       (SELECT COUNT(ue.id)
                          FROM {enrol} e
                          JOIN {user_enrolments} ue ON ue.enrolid = e.id
                         WHERE e.courseid = c.id) AS total_enrolled
                  FROM {course} c
             LEFT JOIN {course_categories} cc ON cc.id = c.category
                 WHERE " . implode(" AND ", $conditions) . "
              ORDER BY c.fullname ASC";

        $records = $DB->get_records_sql($sql, $params, 0, 100);

        $headers = ['Course ID', 'Course Name', 'Category', 'Visibility', 'Format', 'Total Enrolled'];
        $rows = [];

        foreach ($records as $c) {
            $visStr = $c->visible ? 'Visible' : 'Hidden';
            $visClass = $c->visible ? 'badge bg-success' : 'badge bg-secondary';

            $rows[] = [
                'id' => $c->id,
                'col1' => s(format_string($c->fullname)),
                'col2' => s($c->categoryname ?: 'Uncategorized'),
                'col3' => '<span class="' . $visClass . '">' . $visStr . '</span>',
                'col4' => s(strtoupper($c->format)),
                'col5' => '<span class="fw-bold text-primary">' . (int) $c->total_enrolled . '</span>',
                'col6' => '-',
                'csv' => [
                    $c->id,
                    format_string($c->fullname),
                    $c->categoryname ?: 'Uncategorized',
                    $visStr,
                    strtoupper($c->format),
                    (int) $c->total_enrolled,
                ],
            ];
        }

        return [
            'report_id' => 'course_enrolment',
            'title' => get_string('report_course_enrolment_title', 'my'),
            'total_count' => count($rows),
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * Generate Student Enrolments Matrix Report.
     */
    protected static function generate_student_enrolments_report(int $daterange, string $search): array {
        global $DB;

        $conditions = ["c.id <> :siteid", "u.deleted = 0"];
        $params = ['siteid' => SITEID];

        if ($daterange > 0) {
            $conditions[] = "ue.timecreated >= :since";
            $params['since'] = time() - ($daterange * DAYSECS);
        }

        if (!empty($search)) {
            $conditions[] = "(" . $DB->sql_like('u.firstname', ':s1', false) . " OR " .
                $DB->sql_like('c.fullname', ':s2', false) . ")";
            $params['s1'] = "%$search%";
            $params['s2'] = "%$search%";
        }

        $sql = "SELECT ue.id, u.firstname, u.lastname, u.email, c.fullname AS coursename, e.enrol AS enrolmethod, ue.timecreated
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                  JOIN {user} u ON u.id = ue.userid
                  JOIN {course} c ON c.id = e.courseid
                 WHERE " . implode(" AND ", $conditions) . "
              ORDER BY ue.id DESC";

        $records = $DB->get_records_sql($sql, $params, 0, 100);

        $headers = ['Enrolment ID', 'Student Name', 'Email', 'Course Name', 'Enrolment Method', 'Enrolled Date'];
        $rows = [];

        foreach ($records as $r) {
            $rows[] = [
                'id' => $r->id,
                'col1' => s(fullname($r)),
                'col2' => s($r->email ?: '-'),
                'col3' => s(format_string($r->coursename)),
                'col4' => '<span class="badge bg-outline-info text-dark border">' . s(strtoupper($r->enrolmethod)) . '</span>',
                'col5' => $r->timecreated ? userdate($r->timecreated, '%d %b %Y') : '-',
                'col6' => '-',
                'csv' => [
                    $r->id,
                    fullname($r),
                    $r->email ?: '-',
                    format_string($r->coursename),
                    strtoupper($r->enrolmethod),
                    $r->timecreated ? date('Y-m-d H:i:s', $r->timecreated) : '-',
                ],
            ];
        }

        return [
            'report_id' => 'student_enrolments',
            'title' => get_string('report_student_enrolments_title', 'my'),
            'total_count' => count($rows),
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * Generate Gradebook & Activity Participation Summary Report.
     */
    protected static function generate_gradebook_summary_report(int $daterange, string $search): array {
        global $DB;

        $conditions = ["c.id <> :siteid"];
        $params = ['siteid' => SITEID];

        if (!empty($search)) {
            $conditions[] = $DB->sql_like('c.fullname', ':s1', false);
            $params['s1'] = "%$search%";
        }

        $sql = "SELECT c.id, c.fullname, cc.name AS categoryname,
                       (SELECT COUNT(cm.id) FROM {course_modules} cm WHERE cm.course = c.id) AS module_count,
                       (SELECT COUNT(gi.id) FROM {grade_items} gi WHERE gi.courseid = c.id AND gi.itemtype = 'mod') AS graded_count
                  FROM {course} c
             LEFT JOIN {course_categories} cc ON cc.id = c.category
                 WHERE " . implode(" AND ", $conditions) . "
              ORDER BY c.fullname ASC";

        $records = $DB->get_records_sql($sql, $params, 0, 100);

        $headers = ['Course ID', 'Course Name', 'Category', 'Activity Modules', 'Graded Assessment Items', 'Status'];
        $rows = [];

        foreach ($records as $g) {
            $rows[] = [
                'id' => $g->id,
                'col1' => s(format_string($g->fullname)),
                'col2' => s($g->categoryname ?: 'Uncategorized'),
                'col3' => '<span class="badge bg-secondary">' . (int) $g->module_count . ' Activities</span>',
                'col4' => '<span class="badge bg-primary">' . (int) $g->graded_count . ' Graded Items</span>',
                'col5' => '<span class="badge bg-success">Configured</span>',
                'col6' => '-',
                'csv' => [
                    $g->id,
                    format_string($g->fullname),
                    $g->categoryname ?: 'Uncategorized',
                    (int) $g->module_count,
                    (int) $g->graded_count,
                    'Configured',
                ],
            ];
        }

        return [
            'report_id' => 'gradebook_summary',
            'title' => get_string('report_gradebook_summary_title', 'my'),
            'total_count' => count($rows),
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * Generate System Audit & Access Logs Report.
     */
    protected static function generate_system_logs_report(int $daterange, string $search): array {
        global $DB;

        $hasLogTable = $DB->get_manager()->table_exists('logstore_standard_log');
        $headers = ['Time', 'User', 'Action / Event', 'Component', 'IP Address'];
        $rows = [];

        if ($hasLogTable) {
            $conditions = ["1=1"];
            $params = [];

            if ($daterange > 0) {
                $conditions[] = "l.timecreated >= :since";
                $params['since'] = time() - ($daterange * DAYSECS);
            }

            if (!empty($search)) {
                $conditions[] = "(" . $DB->sql_like('l.action', ':s1', false) . " OR " .
                    $DB->sql_like('l.component', ':s2', false) . ")";
                $params['s1'] = "%$search%";
                $params['s2'] = "%$search%";
            }

            $sql = "SELECT l.id, l.timecreated, l.action, l.target, l.component, l.ip, u.firstname, u.lastname
                      FROM {logstore_standard_log} l
                 LEFT JOIN {user} u ON u.id = l.userid
                     WHERE " . implode(" AND ", $conditions) . "
                  ORDER BY l.id DESC";

            $logs = $DB->get_records_sql($sql, $params, 0, 100);
            foreach ($logs as $l) {
                $rows[] = [
                    'id' => $l->id,
                    'col1' => userdate($l->timecreated, '%d %b %Y, %H:%M:%S'),
                    'col2' => s($l->firstname ? fullname($l) : 'System / Guest'),
                    'col3' => '<span class="badge bg-info text-dark">' . s($l->action . ' ' . $l->target) . '</span>',
                    'col4' => s($l->component),
                    'col5' => s($l->ip ?: 'Local'),
                    'col6' => '-',
                    'csv' => [
                        date('Y-m-d H:i:s', $l->timecreated),
                        $l->firstname ? fullname($l) : 'System / Guest',
                        $l->action . ' ' . $l->target,
                        $l->component,
                        $l->ip ?: 'Local',
                    ],
                ];
            }
        } else {
            // Fallback user logins audit log if standard log table isn't populated
            $recent = $DB->get_records_select('user', 'deleted = 0 AND lastaccess > 0', [], 'lastaccess DESC', '*', 0, 50);
            foreach ($recent as $u) {
                $rows[] = [
                    'id' => $u->id,
                    'col1' => userdate($u->lastaccess, '%d %b %Y, %H:%M:%S'),
                    'col2' => s(fullname($u)),
                    'col3' => '<span class="badge bg-success">user_login</span>',
                    'col4' => 'core_auth',
                    'col5' => s($u->lastip ?: '127.0.0.1'),
                    'col6' => '-',
                    'csv' => [
                        date('Y-m-d H:i:s', $u->lastaccess),
                        fullname($u),
                        'user_login',
                        'core_auth',
                        $u->lastip ?: '127.0.0.1',
                    ],
                ];
            }
        }

        return [
            'report_id' => 'system_logs',
            'title' => get_string('report_system_logs_title', 'my'),
            'total_count' => count($rows),
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * Download CSV report directly.
     *
     * @param string $type
     * @param array $filters
     * @return void
     */
    public static function export_csv(string $type, array $filters = []): void {
        $report = self::generate($type, $filters);

        $filename = 'moodle_report_' . $type . '_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // Write UTF-8 BOM for Excel compatibility
        fputs($output, "\xEF\xBB\xBF");

        // Write Headers
        fputcsv($output, $report['headers']);

        // Write Data Rows
        foreach ($report['rows'] as $row) {
            fputcsv($output, $row['csv']);
        }

        fclose($output);
        exit();
    }
}
