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
 * Data provider for the administration dashboard shown on the my moodle page.
 *
 * @package    my
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_my\output;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/report_generator.php');
require_once(__DIR__ . '/role_assigner.php');


/**
 * Builds the template context for my/admin_dashboard.
 */
class admin_dashboard {

    /**
     * Number of distinct users holding at least one of the given roles.
     *
     * @param int[] $roleids
     * @return int
     */
    protected static function count_users_with_roles(array $roleids): int {
        global $DB;

        if (empty($roleids)) {
            return 0;
        }

        [$insql, $params] = $DB->get_in_or_equal($roleids);
        $sql = "SELECT COUNT(DISTINCT ra.userid)
                  FROM {role_assignments} ra
                  JOIN {user} u ON u.id = ra.userid AND u.deleted = 0
                 WHERE ra.roleid $insql";

        return (int) $DB->count_records_sql($sql, $params);
    }

    /**
     * Collect every value the dashboard template needs.
     *
     * @return array
     */
    public static function fetch(): array {
        global $CFG, $DB;

        $now = time();

        // Resolve role ids by shortname so renamed or reordered roles still work.
        $roles = $DB->get_records_menu('role', [], '', 'shortname, id');
        $teachingids = array_values(array_intersect_key($roles,
            array_flip(['manager', 'coursecreator', 'editingteacher', 'teacher'])));
        $studentids = array_values(array_intersect_key($roles, array_flip(['student'])));

        $stats = [
            [
                'label' => get_string('stat_users', 'my'),
                'value' => number_format($DB->count_records_select('user', 'deleted = 0')),
                'colour' => 'primary',
            ],
            [
                'label' => get_string('stat_teachers', 'my'),
                'value' => number_format(self::count_users_with_roles($teachingids)),
                'colour' => 'success',
            ],
            [
                'label' => get_string('stat_students', 'my'),
                'value' => number_format(self::count_users_with_roles($studentids)),
                'colour' => 'info',
            ],
            [
                'label' => get_string('stat_active', 'my'),
                'value' => number_format($DB->count_records_select('user', 'deleted = 0 AND lastaccess > :since',
                    ['since' => $now - 7 * DAYSECS])),
                'colour' => 'warning',
            ],
            [
                'label' => get_string('stat_courses', 'my'),
                'value' => number_format($DB->count_records_select('course', 'id <> :siteid', ['siteid' => SITEID])),
                'colour' => 'secondary',
            ],
            [
                'label' => get_string('stat_enrolments', 'my'),
                'value' => number_format($DB->count_records('user_enrolments')),
                'colour' => 'dark',
            ],
        ];

        $groups = [
            [
                'title' => get_string('group_users', 'my'),
                'icon' => 'i/users',
                'actions' => [
                    ['label' => get_string('act_adduser', 'my'), 'icon' => 't/add',
                        'url' => '/user/editadvanced.php?id=-1'],
                    ['label' => get_string('act_bulkusers', 'my'), 'icon' => 'i/cloudupload',
                        'url' => '/admin/tool/uploaduser/index.php'],
                    ['label' => get_string('act_manageusers', 'my'), 'icon' => 'i/user',
                        'url' => '/admin/user.php'],
                    ['label' => get_string('act_cohorts', 'my'), 'icon' => 'i/cohort',
                        'url' => '/cohort/index.php'],
                    ['label' => get_string('act_assignroles', 'my'), 'icon' => 't/assignroles',
                        'url' => '/admin/roles/manage.php'],
                    ['label' => get_string('act_quickrole', 'my'), 'icon' => 'i/user',
                        'url' => '#dash-roles'],
                ],
            ],
            [
                'title' => get_string('group_courses', 'my'),
                'icon' => 'i/course',
                'actions' => [
                    ['label' => get_string('act_addcourse', 'my'), 'icon' => 't/add',
                        'url' => '/course/edit.php?category=0'],
                    ['label' => get_string('act_managecourses', 'my'), 'icon' => 'i/course',
                        'url' => '/course/management.php'],
                    ['label' => get_string('act_categories', 'my'), 'icon' => 'i/viewcategory',
                        'url' => '/course/editcategory.php?parent=0'],
                    ['label' => get_string('act_uploadcourse', 'my'), 'icon' => 'i/cloudupload',
                        'url' => '/admin/tool/uploadcourse/index.php'],
                ],
            ],
            [
                'title' => get_string('group_activity', 'my'),
                'icon' => 'i/grades',
                'actions' => [
                    ['label' => get_string('act_questions', 'my'), 'icon' => 'i/file_plus',
                        'url' => '/question/edit.php?courseid=' . SITEID],
                    ['label' => get_string('act_gradebook', 'my'), 'icon' => 'i/grades',
                        'url' => '/grade/report/grader/index.php?id=' . SITEID],
                    ['label' => get_string('act_logs', 'my'), 'icon' => 'i/log',
                        'url' => '/report/log/index.php?id=0'],
                    ['label' => get_string('act_badges', 'my'), 'icon' => 'i/badge',
                        'url' => '/badges/index.php?type=1'],
                    ['label' => get_string('act_reports', 'my'), 'icon' => 'i/report',
                        'url' => '/admin/reports.php'],
                ],
            ],
            [
                'title' => get_string('group_system', 'my'),
                'icon' => 'i/settings',
                'actions' => [
                    ['label' => get_string('act_plugins', 'my'), 'icon' => 't/block',
                        'url' => '/admin/plugins.php'],
                    ['label' => get_string('act_security', 'my'), 'icon' => 'i/checkpermissions',
                        'url' => '/report/security/index.php'],
                    ['label' => get_string('act_purge', 'my'), 'icon' => 'i/trash',
                        'url' => '/admin/purgecaches.php'],
                    ['label' => get_string('act_cron', 'my'), 'icon' => 'i/reload',
                        'url' => '/admin/cron.php'],
                    ['label' => get_string('act_maintenance', 'my'), 'icon' => 't/lock',
                        'url' => '/admin/settings.php?section=maintenancemode'],
                ],
            ],
        ];

        foreach ($groups as $gi => $group) {
            foreach ($group['actions'] as $ai => $action) {
                if (isset($action['url'][0]) && $action['url'][0] !== '#') {
                    $groups[$gi]['actions'][$ai]['url'] = $CFG->wwwroot . $action['url'];
                }
            }
        }

        // User account status table.
        $userrows = [
            [get_string('status_total', 'my'), number_format($DB->count_records_select('user', 'deleted = 0'))],
            [get_string('status_confirmed', 'my'),
                number_format($DB->count_records_select('user', 'deleted = 0 AND confirmed = 1'))],
            [get_string('status_unconfirmed', 'my'),
                number_format($DB->count_records_select('user', 'deleted = 0 AND confirmed = 0'))],
            [get_string('status_suspended', 'my'),
                number_format($DB->count_records_select('user', 'deleted = 0 AND suspended = 1'))],
            [get_string('status_new', 'my'),
                number_format($DB->count_records_select('user', 'deleted = 0 AND timecreated > :since',
                    ['since' => $now - 30 * DAYSECS]))],
        ];
        $usershtml = '';
        foreach ($userrows as [$label, $value]) {
            $usershtml .= '<tr><td>' . s($label) . '</td>'
                . '<td class="text-end fw-semibold">' . s($value) . '</td></tr>';
        }

        // Courses per category table.
        $sql = "SELECT cc.id, cc.name,
                       COUNT(c.id) AS total,
                       SUM(CASE WHEN c.visible = 1 THEN 1 ELSE 0 END) AS numvisible,
                       SUM(CASE WHEN c.visible = 0 THEN 1 ELSE 0 END) AS numhidden
                  FROM {course_categories} cc
             LEFT JOIN {course} c ON c.category = cc.id AND c.id <> :siteid
              GROUP BY cc.id, cc.name
                 HAVING COUNT(c.id) > 0
              ORDER BY total DESC, cc.name ASC";
        $courseshtml = '';
        $coursecount = 0;
        foreach ($DB->get_records_sql($sql, ['siteid' => SITEID]) as $category) {
            $coursecount++;
            $courseshtml .= '<tr><td>' . format_string($category->name) . '</td>'
                . '<td class="text-end">' . (int) $category->numvisible . '</td>'
                . '<td class="text-end">' . (int) $category->numhidden . '</td>'
                . '<td class="text-end fw-semibold">' . (int) $category->total . '</td></tr>';
        }

        // Recent logins table.
        $recenthtml = '';
        $recentusers = $DB->get_records_select('user', 'deleted = 0 AND lastaccess > 0',
            [], 'lastaccess DESC', '*', 0, 8);
        foreach ($recentusers as $user) {
            $recenthtml .= '<tr><td>' . s(fullname($user)) . '</td>'
                . '<td class="text-muted">' . ($user->email ? s($user->email) : s(get_string('noemail', 'my'))) . '</td>'
                . '<td class="text-end">' . s(format_time(max(0, $now - $user->lastaccess))) . '</td></tr>';
        }

        // Roles list for user report filter.
        $allroles = $DB->get_records('role', [], 'sortorder ASC', 'id, name, shortname');
        $roleoptions = [];
        foreach ($allroles as $r) {
            $roleoptions[] = [
                'id' => $r->id,
                'name' => role_get_name($r),
            ];
        }

        $reportcatalog = report_generator::get_catalog();
        $defaultreport = report_generator::generate('user_activity');

        $rolepanel = role_assigner::fetch();

        return [
            'paneltitle' => get_string('dashboardpaneltitle', 'my'),
            'sesskey' => sesskey(),
            'tab_overview_title' => get_string('tab_overview', 'my'),
            'tab_reports_title' => get_string('tab_reports', 'my'),
            'tab_roles_title' => get_string('tab_roles', 'my'),
            'roles_url' => $CFG->wwwroot . '/my/roleassign.php',
            'ra_title' => get_string('ra_title', 'my'),
            'ra_desc' => get_string('ra_desc', 'my'),
            'ra_role' => get_string('ra_role', 'my'),
            'ra_show' => get_string('ra_show', 'my'),
            'ra_search' => get_string('ra_search', 'my'),
            'ra_search_placeholder' => get_string('ra_search_placeholder', 'my'),
            'ra_apply' => get_string('ra_apply', 'my'),
            'ra_reset' => get_string('ra_reset', 'my'),
            'ra_count_notassigned' => get_string('ra_count_notassigned', 'my'),
            'ra_count_assigned' => get_string('ra_count_assigned', 'my'),
            'ra_col_user' => get_string('ra_col_user', 'my'),
            'ra_col_email' => get_string('ra_col_email', 'my'),
            'ra_col_status' => get_string('ra_col_status', 'my'),
            'ra_col_lastaccess' => get_string('ra_col_lastaccess', 'my'),
            'ra_col_action' => get_string('ra_col_action', 'my'),
            'ra_selectall' => get_string('ra_selectall', 'my'),
            'ra_status_assigned' => get_string('ra_status_assigned', 'my'),
            'ra_status_notassigned' => get_string('ra_status_notassigned', 'my'),
            'ra_assign' => get_string('ra_assign', 'my'),
            'ra_remove' => get_string('ra_remove', 'my'),
            'ra_busy' => get_string('ra_busy', 'my'),
            'ra_nothing_selected' => get_string('ra_nothing_selected', 'my'),
            'ra_roleid' => $rolepanel['roleid'],
            'ra_role_options' => $rolepanel['role_options'],
            'role_modes' => $rolepanel['role_modes'],
            'ra_search_value' => $rolepanel['search'],
            'ra_count_notassigned_value' => $rolepanel['count_notassigned'],
            'ra_count_assigned_value' => $rolepanel['count_assigned'],
            'ra_shown' => $rolepanel['shown'],
            'ra_bulk_label' => $rolepanel['bulk_label'],
            'ra_rows' => $rolepanel['rows'],
            'ra_hasrows' => $rolepanel['hasrows'],
            'ra_emptymessage' => $rolepanel['emptymessage'],
            'report_center_title' => get_string('report_center_title', 'my'),
            'report_center_desc' => get_string('report_center_desc', 'my'),
            'reports_url' => $CFG->wwwroot . '/my/reports.php',
            'report_catalog' => $reportcatalog,
            'role_options' => $roleoptions,
            'default_report' => $defaultreport,
            'quickadd' => [
                ['label' => get_string('act_adduser', 'my'), 'icon' => 'i/user',
                    'url' => $CFG->wwwroot . '/user/editadvanced.php?id=-1'],
                ['label' => get_string('act_addcourse', 'my'), 'icon' => 'i/course',
                    'url' => $CFG->wwwroot . '/course/edit.php?category=0'],
                ['label' => get_string('act_manageusers', 'my'), 'icon' => 'i/users',
                    'url' => $CFG->wwwroot . '/admin/user.php'],
                ['label' => get_string('act_quickrole', 'my'), 'icon' => 't/assignroles',
                    'url' => '#dash-roles'],
            ],
            'stats' => $stats,
            'groups' => $groups,

            'siteadmin_url' => $CFG->wwwroot . '/admin/search.php',
            'siteadmin_title' => get_string('siteadministration'),
            'system_status' => [
                'phpversion' => PHP_VERSION,
                'moodleversion' => $CFG->release ?? '4.5+',
                'dbtype' => strtoupper($CFG->dbtype ?? 'mysqli'),
                'maintenancemode' => !empty($CFG->maintenancemode) ? 'Active' : 'Disabled',
                'cronlastrun' => format_time(max(0, $now - (int)($DB->get_field('config', 'value', ['name' => 'lastcronrun']) ?: $now - 3600))),
                'purgeurl' => $CFG->wwwroot . '/admin/purgecaches.php?sesskey=' . sesskey(),
                'cronurl' => $CFG->wwwroot . '/admin/cron.php',
                'securityurl' => $CFG->wwwroot . '/report/security/index.php',
                'logsurl' => $CFG->wwwroot . '/report/log/index.php?id=0',
            ],
            'tableusers' => [
                'title' => get_string('table_users_title', 'my'),
                'colmetric' => get_string('col_metric', 'my'),
                'colcount' => get_string('col_count', 'my'),
                'rowshtml' => $usershtml,
            ],

            'tablecourses' => [
                'title' => get_string('table_courses_title', 'my'),
                'colcategory' => get_string('col_category', 'my'),
                'colvisible' => get_string('col_visible', 'my'),
                'colhidden' => get_string('col_hidden', 'my'),
                'coltotal' => get_string('col_total', 'my'),
                'hasrows' => $coursecount > 0,
                'emptymessage' => get_string('coursecategoriesempty', 'my'),
                'rowshtml' => $courseshtml,
            ],

            'tablerecent' => [
                'title' => get_string('table_recent_title', 'my'),
                'coluser' => get_string('col_user', 'my'),
                'colemail' => get_string('col_email', 'my'),
                'collastaccess' => get_string('col_lastaccess', 'my'),
                'hasrows' => !empty($recenthtml),
                'emptymessage' => get_string('norecent', 'my'),
                'rowshtml' => $recenthtml,
            ],
        ];
    }
}

