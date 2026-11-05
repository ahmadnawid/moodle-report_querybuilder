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
 * English language strings for Query Builder.
 *
 * @package    report_querybuilder
 * @copyright  2026 Ahmad Nawid Mustafazada <ahmadnawid.mz@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['advanced_editor_heading']  = 'Advanced SQL Editor';
$string['all_categories']           = 'All categories';
$string['analyze_query']            = 'Analyze Query';
$string['baseentity']               = 'Base entity';
$string['btn_back']                 = 'Back to query form';
$string['btn_close']                = 'Close';
$string['btn_delete']               = 'Delete';
$string['btn_edit']                 = 'Edit';
$string['btn_export_csv']           = 'Export CSV';
$string['btn_export_pdf']           = 'Export PDF';
$string['btn_new']                  = 'New';
$string['btn_run_sql']              = 'Run SQL';
$string['btn_save_query']           = 'Save Query';
$string['btn_switch_advanced']      = 'Switch to Advanced SQL Mode';
$string['btn_switch_builder']       = 'Switch to Builder Mode';
$string['category']                 = 'Category';
$string['confirm_delete']           = 'Are you sure you want to delete this query?';
$string['enter_sql'] = 'Please enter a SQL query to analyze.';
$string['error_dangerous_keyword']  = 'Dangerous SQL keyword detected: ';
$string['error_multistatement']     = 'Multiple statements are not allowed.';
$string['error_name_and_query']     = 'Please enter a name and query.';
$string['error_select_to_delete']   = 'Please select a query to delete.';
$string['error_selectonly']         = 'Only SELECT statements are allowed.';
$string['explain_analyzing']        = 'Analyzing...';
$string['explain_cost']             = 'Total Cost';
$string['explain_error']            = 'Could not analyze query: ';
$string['explain_filter']           = 'Filter';
$string['explain_index']            = 'Index Used';
$string['explain_indexscan']        = 'INDEX';
$string['explain_no_warnings']      = 'No performance issues detected.';
$string['explain_node']             = 'Operation';
$string['explain_none']             = '(none)';
$string['explain_plan']             = 'Query Explain Plan';
$string['explain_running']          = 'Running EXPLAIN, please wait...';
$string['explain_relation']         = 'Table / Relation';
$string['explain_rows']             = 'Est. Rows';
$string['explain_seqscan']          = 'SEQ SCAN';
$string['explain_warnings']         = 'Performance Warnings';
$string['fields']                   = 'Fields';
$string['filterfield']              = 'Filter field';
$string['filterop']                 = 'Operator';
$string['filtervalue']              = 'Value';
$string['generatesql']              = 'Generate SQL';
$string['joins']                    = 'Joins';
$string['manage_entities']          = 'Manage Entities';
$string['no_results']               = 'No results found.';
$string['pageheading']              = 'Query Builder';
$string['pagetitle']                = 'Query Builder';
$string['pluginname']               = 'Query Builder';
$string['pluginname_desc']          = 'Build and run custom SQL reports.';
$string['privacy:metadata'] = 'The Query Builder plugin does not store any personal user data. Saved queries are shared administrative resources available to all users with the report/querybuilder:view capability.';
$string['query_deleted']            = 'Saved query deleted.';
$string['query_name_placeholder']   = 'Query name';
$string['query_saved']              = 'Query saved successfully';
$string['query_required']           = 'Query required';
$string['query_updated']            = 'Query updated successfully';
$string['querybuilder:manage'] = 'Manage Query Builder queries';
$string['querybuilder:view']        = 'View Query Builder report';
$string['select_saved_query']       = 'Select saved query';
$string['sql_editor_help']          = 'Write a SELECT query. Use {tablename} Moodle table prefix notation, e.g. {user}, {course}.';
$string['sql_error'] = 'Database error:';
$string['sql_preview_heading']      = 'SQL Preview';
$string['sql_results_heading']      = 'SQL Results';
$string['sql_too_complex']          = 'This SQL cannot be converted to the builder. Please simplify it or reset.';
$string['switch_builder_tooltip']   = 'Switch to visual builder (only for simple SELECT queries)';
