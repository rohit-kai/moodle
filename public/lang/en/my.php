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
 * Strings used by My Moodle pages.
 *
 * @package   core
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addpage'] = 'Add page';
$string['alldashboardswerereset'] = 'All Dashboard pages have been reset to default.';
$string['allprofileswerereset'] = 'All profile pages have been reset to default.';
$string['defaultpage'] = 'Default My Moodle page';
$string['defaultprofilepage'] = 'Default profile page';
$string['delpage'] = 'Delete page';
$string['error:dashboardisdisabled'] = 'The Dashboard has been disabled by an administrator.';
$string['error:mycoursesisdisabled'] = 'My courses has been disabled by an administrator.';
$string['managepages'] = 'Manage pages';
$string['mymoodle'] = 'Dashboard';
$string['nocourses'] = 'No course information to show.';
$string['noguest'] = 'The Dashboard page is not available to guest users';
$string['pinblocks'] = 'Configure pinned blocks for my moodle';
$string['pinblocksexplan'] = 'Any block settings you configure here will be visible (and non-editable) for any user of moodle on their \'My Moodle\' overview page.';
$string['privacy:metadata:core_my:preference:user_home_page_preference'] = 'The user home page preference.';
$string['reseterror'] = 'There was an error resetting your page';
$string['reseteveryonesdashboard'] = 'Reset Dashboard for all users';
$string['reseteveryonesprofile'] = 'Reset profile for all users';
$string['resetpage'] = 'Reset page to default';
$string['resetpageconfirm'] = 'Are you sure you want to reset the page to default?';
$string['resettingdashboards'] = 'Resetting user dashboards to default...';

// Deprecated since Moodle 4.5.
$string['coursemanagementoptions'] = 'Course management options';

// Administration dashboard (my/admin_dashboard).
$string['dashboardpaneltitle'] = 'Administration dashboard';
$string['stat_users'] = 'Total users';
$string['stat_teachers'] = 'Teachers & managers';
$string['stat_students'] = 'Students';
$string['stat_active'] = 'Active (7 days)';
$string['stat_courses'] = 'Courses';
$string['stat_enrolments'] = 'Enrolments';
$string['group_users'] = 'Users & roles';
$string['group_courses'] = 'Courses & categories';
$string['group_activity'] = 'Activities & grades';
$string['group_system'] = 'System';
$string['act_adduser'] = 'Add a new user';
$string['act_bulkusers'] = 'Bulk user upload';
$string['act_manageusers'] = 'Manage users';
$string['act_cohorts'] = 'Cohorts';
$string['act_assignroles'] = 'Assign roles';
$string['act_addcourse'] = 'Add a new course';
$string['act_managecourses'] = 'Manage courses';
$string['act_categories'] = 'Course categories';
$string['act_uploadcourse'] = 'Upload course (CSV)';
$string['act_questions'] = 'Question bank';
$string['act_gradebook'] = 'Gradebook';
$string['act_logs'] = 'Logs & reports';
$string['act_badges'] = 'Badges';
$string['act_reports'] = 'Site reports';
$string['act_plugins'] = 'Plugins';
$string['act_security'] = 'Security overview';
$string['act_purge'] = 'Purge caches';
$string['act_cron'] = 'Run cron now';
$string['act_maintenance'] = 'Maintenance mode';
$string['table_users_title'] = 'User accounts';
$string['table_courses_title'] = 'Courses per category';
$string['table_recent_title'] = 'Recent logins';
$string['col_metric'] = 'Account status';
$string['col_count'] = 'Count';
$string['col_category'] = 'Category';
$string['col_visible'] = 'Visible';
$string['col_hidden'] = 'Hidden';
$string['col_total'] = 'Total';
$string['col_user'] = 'User';
$string['col_email'] = 'Email';
$string['col_lastaccess'] = 'Last access';
$string['status_total'] = 'Total accounts';
$string['status_confirmed'] = 'Confirmed';
$string['status_unconfirmed'] = 'Unconfirmed';
$string['status_suspended'] = 'Suspended';
$string['status_new'] = 'New (last 30 days)';
$string['noemail'] = 'No email address';
$string['norecent'] = 'Nobody has logged in yet.';
$string['coursecategoriesempty'] = 'No courses have been created yet.';

// Admin Dashboard Navigation & Report Generator.
$string['tab_overview'] = 'Overview';
$string['tab_reports'] = 'Reports Generation';
$string['report_center_title'] = 'Report Generation Center';
$string['report_center_desc'] = 'Select, filter, generate, and export customized reports across your site.';
$string['filter_category'] = 'Report Category';
$string['filter_daterange'] = 'Time Period';
$string['filter_role'] = 'User Role';
$string['filter_search'] = 'Search Filter';
$string['filter_apply'] = 'Filter & Generate Report';
$string['filter_reset'] = 'Reset Filters';
$string['export_csv'] = 'Export CSV';
$string['export_print'] = 'Print Report';
$string['cat_all'] = 'All Categories';
$string['cat_users'] = 'Users & Accounts';
$string['cat_courses'] = 'Courses & Enrolments';
$string['cat_activities'] = 'Activities & Grades';
$string['cat_system'] = 'System & Audit Logs';
$string['range_all'] = 'All Time';
$string['range_today'] = 'Today';
$string['range_7days'] = 'Last 7 Days';
$string['range_30days'] = 'Last 30 Days';
$string['range_90days'] = 'Last 90 Days';
$string['range_365days'] = 'Last 365 Days';
$string['report_user_activity_title'] = 'User Directory & Activity Report';
$string['report_user_activity_desc'] = 'Detailed user account list with roles, account status, registration date, and last access.';
$string['report_inactive_users_title'] = 'Inactive & Suspended Users Audit';
$string['report_inactive_users_desc'] = 'Identify dormant user accounts that have not logged in over 30+ days or are suspended.';
$string['report_course_enrolment_title'] = 'Course Enrolment & Category Breakdown';
$string['report_course_enrolment_desc'] = 'Course inventory breakdown by category, visibility, teacher count, and student enrolments.';
$string['report_student_enrolments_title'] = 'Student Enrolment Matrix';
$string['report_student_enrolments_desc'] = 'Detailed breakdown of active user course enrolments and enrolment methods.';
$string['report_gradebook_summary_title'] = 'Gradebook & Activity Participation Summary';
$string['report_gradebook_summary_desc'] = 'Overview of course activities, graded items, gradebook configuration, and student engagement.';
$string['report_system_logs_title'] = 'System Access & Audit Trail Logs';
$string['report_system_logs_desc'] = 'Comprehensive site audit log for logins, configuration edits, and site security events.';

// Quick role assignment panel (Users & roles).
$string['tab_roles'] = 'Role Assignment';
$string['act_quickrole'] = 'Quick assign roles';
$string['ra_title'] = 'Quick Role Assignment';
$string['ra_desc'] = 'Pick a role, filter the users who do not have it yet, and assign it in one click. Roles are assigned on the site (system) context, so the user can reach the matching panels straight away.';
$string['ra_role'] = 'Role to assign';
$string['ra_show'] = 'Show users';
$string['ra_search'] = 'Search';
$string['ra_search_placeholder'] = 'Name, email or username...';
$string['ra_apply'] = 'Apply Filter';
$string['ra_reset'] = 'Reset';
$string['ra_mode_notassigned'] = 'Not assigned (recommended)';
$string['ra_mode_assigned'] = 'Already assigned';
$string['ra_mode_all'] = 'All users';
$string['ra_count_notassigned'] = 'Not assigned';
$string['ra_count_assigned'] = 'Assigned';
$string['ra_col_user'] = 'User';
$string['ra_col_email'] = 'Email';
$string['ra_col_status'] = 'Role status';
$string['ra_col_lastaccess'] = 'Last access';
$string['ra_col_action'] = 'Action';
$string['ra_selectall'] = 'Select all shown';
$string['ra_status_assigned'] = 'Assigned';
$string['ra_status_notassigned'] = 'Not assigned';
$string['ra_assign'] = 'Assign';
$string['ra_remove'] = 'Remove';
$string['ra_assign_selected'] = 'Assign Selected';
$string['ra_remove_selected'] = 'Remove Selected';
$string['ra_empty'] = 'No users match the selected filters.';
$string['ra_busy'] = 'Working...';
$string['ra_done_assign'] = 'Role assigned.';
$string['ra_done_remove'] = 'Role removed.';
$string['ra_nothing_selected'] = 'Select at least one user first.';
$string['ra_badrole'] = 'That role cannot be assigned here.';
$string['ra_norights'] = 'You are not allowed to assign roles on the site context.';

