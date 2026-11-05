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
 * Main entry point for the Query Builder report.
 *
 * @package    report_querybuilder
 * @copyright  2026 Ahmad Nawid Mustafazada <ahmadnawid.mz@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

require_once($CFG->dirroot . '/report/querybuilder/classes/local/query_manager.php');
require_once($CFG->dirroot . '/report/querybuilder/classes/local/sql_utils.php');
require_once($CFG->dirroot . '/report/querybuilder/classes/local/sql_validator.php');

use report_querybuilder\local\query_manager;
use report_querybuilder\local\sql_utils;
use report_querybuilder\local\sql_validator;
use report_querybuilder\form\builder_form;
use report_querybuilder\query\compiler;

require_login();

$context = context_system::instance();
$PAGE->set_context($context);

require_capability('report/querybuilder:view', $context);

$PAGE->set_url('/report/querybuilder/index.php');
$PAGE->set_title(get_string('pagetitle', 'report_querybuilder'));
$PAGE->set_heading(get_string('pageheading', 'report_querybuilder'));

$PAGE->requires->jquery(); // Moodle's built-in jQuery.

$sqlparam = optional_param('sql', '', PARAM_RAW);    // SQL text — sanitised via sql_validator before execution.
$advsql   = optional_param('advsql', '', PARAM_RAW); // SQL text — sanitised via sql_validator before execution.
$advanced = optional_param('advanced', 0, PARAM_BOOL);

if (!empty($advsql) && !$advanced) {
    $download = optional_param('download', '', PARAM_ALPHA);

    if (!$download) {
        require_sesskey();
    }

    // Validate first — redirect before any output if invalid.
    $error = sql_validator::validate($advsql);
    if ($error) {
        redirect(
            new moodle_url('/report/querybuilder/index.php', [
                'advanced' => 1,
                'advsql'   => $advsql,
            ]),
            $error,
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    // Test the query before outputting any HTML.
    try {
        global $DB;

        // Strip existing LIMIT/OFFSET before adding our test LIMIT 1.
        $testsql = sql_utils::clean_sql($advsql);
        if (!sql_utils::has_limit_or_offset($testsql)) {
            $testrecordset = $DB->get_recordset_sql($testsql, null, 0, 1);
        } else {
            // SQL already has LIMIT — run as-is, Moodle won't add another.
            $testrecordset = $DB->get_recordset_sql($testsql);
        }

        $columns = [];
        if ($testrecordset->valid()) {
            $first   = $testrecordset->current();
            $columns = array_keys((array)$first);
        }
        $testrecordset->close();
    } catch (Exception $e) {
        redirect(
            new moodle_url('/report/querybuilder/index.php', [
                'advanced' => 1,
                'advsql'   => $advsql,
            ]),
            get_string('sql_error', 'report_querybuilder') . ' ' . s($e->getMessage()),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
    // Only reach here if query is valid — safe to output header now.
    if (!$download) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(
            get_string(
                'sql_results_heading',
                'report_querybuilder'
            ),
            3
        );
    }

    $renderer = $PAGE->get_renderer('report_querybuilder');
    if ($columns) {
        echo $renderer->render_results_table($columns, $advsql, $download);
    } else {
        echo $OUTPUT->notification(
            get_string(
                'no_results',
                'report_querybuilder'
            ),
            'info'
        );
    }

    if (!$download) {
        echo $renderer->back_button();
    }

    if (!$download) {
        echo $OUTPUT->footer();
    }
    exit;
}

if ($advanced) {
    $saved = query_manager::get_all();
    $loadid = optional_param('loadquery', 0, PARAM_INT);
    $newquery = optional_param('newbutton', null, PARAM_ALPHA);
    $prefill = null;
    $shownewform = false;
    if ($newquery !== null) {
        $shownewform = true;
    }
    if ($shownewform) {
        $sqlcontent = '';
    }

    // Only set $prefill when Edit is clicked.
    if (optional_param('editbutton', null, PARAM_ALPHA) && $loadid) {
        $prefill = query_manager::get($loadid);
    }

    // Delete.
    if (optional_param('deletebutton', null, PARAM_ALPHA)) {
        require_sesskey();
        $deleteid = $loadid ?: optional_param('savedqueryid', 0, PARAM_INT);
        if ($deleteid) {
            query_manager::delete($deleteid);
            redirect(
                new moodle_url('/report/querybuilder/index.php', ['advanced' => 1]),
                get_string('query_deleted', 'report_querybuilder'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        }
    }

    // Save.
    if (optional_param('savequery', null, PARAM_ALPHA)) {
        require_sesskey();
        $savename = required_param('savename', PARAM_TEXT);
        $query = required_param('query', PARAM_RAW); // SQL text — PARAM_RAW required; validated below before storage.
        $savedqueryid = optional_param('savedqueryid', 0, PARAM_INT);
        $category = optional_param('category', '', PARAM_TEXT);

        $error = sql_validator::validate($query);
        if ($error) {
            redirect(
                new moodle_url('/report/querybuilder/index.php', ['advanced' => 1]),
                $error,
                null,
                \core\output\notification::NOTIFY_ERROR
            );
        }
        if ($savename && $query) {
            if ($savedqueryid) {
                query_manager::update($savedqueryid, $savename, $query, $category);
                redirect(
                    new moodle_url('/report/querybuilder/index.php', [
                        'advanced' => 1,
                    ]),
                    get_string('query_updated', 'report_querybuilder'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
            } else {
                query_manager::insert($savename, $query, $category);
                redirect(
                    new moodle_url('/report/querybuilder/index.php', [
                        'advanced' => 1,
                    ]),
                    get_string('query_saved', 'report_querybuilder'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
            }
        } else {
            echo $OUTPUT->notification(get_string('error_name_and_query', 'report_querybuilder'), 'error');
        }
    }

    echo $OUTPUT->header();

    $categories = [];
    if ($saved) {
        foreach ($saved as $s) {
            if (!empty($s->category)) {
                $categories[$s->category] = $s->category;
            }
        }
    }

    $selectedcategory = optional_param('categoryfilter', '', PARAM_TEXT);

    // Filter saved queries by category.
    $filtered = [];
    if ($saved) {
        foreach ($saved as $s) {
            if ($selectedcategory === '' || $s->category === $selectedcategory) {
                $filtered[] = $s;
            }
        }
    }

    // Build options for saved query dropdown.
    $options = ['' => get_string('select_saved_query', 'report_querybuilder')];
    foreach ($filtered as $s) {
        $options[$s->id] = $s->name . (!empty($s->category) ? ' (' . $s->category . ')' : '');
    }

    // Build JS object keyed by ID.
    $filteredbyid = [];
    foreach ($filtered as $s) {
        $filteredbyid[$s->id] = $s;
    }

    // Toolbar.
    $categoryurl = new moodle_url('/report/querybuilder/index.php', [
        'advanced'  => 1,
        'loadquery' => $loadid,
    ]);
    $categoryselectobj = new single_select(
        $categoryurl,
        'categoryfilter',
        ['' => get_string('all_categories', 'report_querybuilder')] + $categories,
        $selectedcategory,
        null
    );
    $categoryselectobj->set_label(get_string('category', 'report_querybuilder'));
    echo html_writer::start_div('d-flex align-items-center gap-3 mb-2 flex-wrap');
    echo $OUTPUT->render($categoryselectobj);

    // Saved query selector.
    echo html_writer::start_div('d-flex align-items-center gap-2');
    echo html_writer::tag(
        'label',
        get_string('select_saved_query', 'report_querybuilder'),
        ['class' => 'me-1']
    );
    echo html_writer::select(
        $options,
        'loadquery',
        $loadid,
        null,
        ['id' => 'loadqueryselect', 'class' => 'form-select d-inline-block w-auto me-2']
    );
    echo html_writer::end_div(); // Saved query row.
    echo html_writer::end_div(); // Toolbar top row.

    echo html_writer::start_div('d-flex gap-2 mb-3');

    $newurl = new moodle_url('/report/querybuilder/index.php', [
        'advanced'  => 1,
        'newbutton' => 1,
        'sesskey'   => sesskey(),
    ]);
    echo $OUTPUT->single_button($newurl, get_string('btn_new', 'report_querybuilder'), 'get');

    // Edit form. Action URL updated by JS when query selected.
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'id'     => 'editqueryform',
        'action' => (new moodle_url('/report/querybuilder/index.php'))->out(false),
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'advanced',
        'value' => 1,
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'loadquery',
        'id'   => 'editloadquery',
        'value' => $loadid,
    ]);
    echo html_writer::empty_tag('input', [
        'type'     => 'submit',
        'name'     => 'editbutton',
        'value'    => get_string('btn_edit', 'report_querybuilder'),
        'class'    => 'btn btn-outline-secondary',
        'id'       => 'editquerybtn',
        'disabled' => $loadid ? null : 'disabled',
    ]);
    echo html_writer::end_tag('form');

    // Delete form.
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'id'     => 'deletequeryform',
        'action' => (new moodle_url('/report/querybuilder/index.php'))->out(false),
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'advanced',
        'value' => 1,
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'loadquery',
        'id'   => 'deleteloadquery',
        'value' => $loadid,
    ]);
    echo html_writer::empty_tag('input', [
        'type'     => 'submit',
        'name'     => 'deletebutton',
        'value'    => get_string('btn_delete', 'report_querybuilder'),
        'class'    => 'btn btn-outline-danger',
        'id'       => 'deletequerybtn',
        'disabled' => $loadid ? null : 'disabled',
    ]);
    echo html_writer::end_tag('form');

    echo html_writer::end_div();

    // End toolbar.

    $queriesforjs = new stdClass();
    foreach ($filteredbyid as $id => $q) {
        $key = (string)$id;
        $queriesforjs->$key = ['id' => $q->id, 'querytext' => $q->querytext];
    }
    $PAGE->requires->js_call_amd('report_querybuilder/toolbar', 'init', [
        ['queries' => $queriesforjs],
    ]);

    // Decide what SQL to show in the textarea.
    if ($prefill) {
        $sqlcontent = $prefill->querytext;
    } else if (!empty($advsql)) {
        $sqlcontent = $advsql;
    } else if (!empty($sqlparam)) {
        $sqlcontent = $sqlparam;
    } else {
        $sqlcontent = '';
    }

    // Show Save Query Form only if editing.
    if ($prefill || $shownewform) {
        echo $OUTPUT->box_start('generalbox mb-3');
        echo $OUTPUT->heading(
            $prefill
                ? get_string('btn_edit', 'report_querybuilder') . ': ' . s($prefill->name)
                : get_string('btn_new', 'report_querybuilder'),
            5
        );
        echo html_writer::start_tag('form', [
            'method' => 'post',
            'class'  => 'd-flex align-items-center gap-2 flex-wrap',
            'id'     => 'savequeryform',
        ]);
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey(),
        ]);
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'advanced',
            'value' => 1,
        ]);
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'savedqueryid',
            'value' => !empty($prefill->id) ? $prefill->id : '',
        ]);
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'query',
            'id'   => 'hiddenquery',
            'value' => s($sqlcontent),
        ]);
        echo html_writer::empty_tag('input', [
            'type'        => 'text',
            'name'        => 'category',
            'placeholder' => get_string('category', 'report_querybuilder'),
            'class'       => 'form-control qb-input-category',
            'value'       => !empty($prefill->category) ? $prefill->category : '',
        ]);
        echo html_writer::empty_tag('input', [
            'type'        => 'text',
            'name'        => 'savename',
            'placeholder' => get_string('query_name_placeholder', 'report_querybuilder'),
            'class'       => 'form-control qb-input-savename',
            'value'       => !empty($prefill->name) ? $prefill->name : '',
        ]);
        echo html_writer::empty_tag('input', [
            'type'  => 'submit',
            'name'  => 'savequery',
            'value' => get_string('btn_save_query', 'report_querybuilder'),
            'class' => 'btn btn-primary',
        ]);
        echo html_writer::link(
            new moodle_url('/report/querybuilder/index.php', ['advanced' => 1]),
            get_string('cancel', 'core'),
            ['class' => 'btn btn-outline-secondary ms-1']
        );
        echo html_writer::end_tag('form');
        echo $OUTPUT->box_end();
    }

    // Toggle button.
    $togglesql = '';
    if (!empty($advsql)) {
        $togglesql = $advsql;
    } else if (!empty($sqlcontent)) {
        $togglesql = $sqlcontent;
    }

    echo $OUTPUT->box_start('generalbox');

    // SQL editor form.
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'class'  => 'mb-3',
        'id'     => 'sqleditorform',
        'action' => (new moodle_url('/report/querybuilder/index.php'))->out(false),
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]);

    echo html_writer::start_div('d-flex justify-content-between align-items-center mb-2');
    echo html_writer::tag(
        'label',
        get_string('advanced_editor_heading', 'report_querybuilder'),
        ['for' => 'advsql', 'class' => 'form-label fw-semibold mb-1']
    );
    echo html_writer::link(
        new moodle_url('/report/querybuilder/index.php', ['sql' => $togglesql]),
        get_string('btn_switch_builder', 'report_querybuilder'),
        [
            'class' => 'btn btn-outline-secondary btn-sm',
            'id' => 'modeswitchbtn',
            'title' => get_string('switch_builder_tooltip', 'report_querybuilder'),
        ]
    );
    echo html_writer::end_div();

    echo html_writer::tag('textarea', s($sqlcontent), [
        'id'           => 'advsql',
        'name'         => 'advsql',
        'class'        => 'form-control font-monospace',
        'rows'         => 14,
        'autocomplete' => 'off',
        'spellcheck'   => 'false',
    ]);

    // Button row.
    echo html_writer::start_div('mt-3 d-flex gap-2 align-items-center');
    echo html_writer::tag(
        'button',
        get_string('btn_run_sql', 'report_querybuilder'),
        [
            'type'    => 'submit',
            'class'   => 'btn btn-primary',
        ]
    );
    echo html_writer::tag(
        'button',
        get_string('analyze_query', 'report_querybuilder'),
        ['type' => 'button', 'class' => 'btn btn-outline-secondary', 'id' => 'analyzebtn']
    );
    echo html_writer::tag('span', '', [
        'id'    => 'analyzespinner',
        'class' => 'spinner-border spinner-border-sm ms-1 d-none',
        'style' => 'display:none;',
    ]);
    echo html_writer::end_div();
    echo html_writer::tag(
        'small',
        get_string('sql_editor_help', 'report_querybuilder'),
        ['class' => 'text-muted mt-1 d-block']
    );

    echo html_writer::end_tag('form');

    // Explain plan panel (shown below editor after analyze).
    echo html_writer::start_div('mt-3 d-none', ['id' => 'explainpanel', 'style' => 'display:none;']);
    echo html_writer::start_div('card');

    // Card header.
    echo html_writer::start_div('card-header d-flex justify-content-between align-items-center');
    echo html_writer::tag('strong', get_string('explain_plan', 'report_querybuilder'));
    echo html_writer::tag('button', get_string('btn_close', 'report_querybuilder'), [
        'type'    => 'button',
        'class'   => 'btn btn-sm btn-outline-secondary',
        'id'      => 'explainclosebtn',
    ]);
    echo html_writer::end_div();

    // Card body: warnings then plan table.
    echo html_writer::start_div('card-body');
    echo html_writer::tag('div', '', ['id' => 'explainwarnings']);
    echo html_writer::tag('div', '', ['id' => 'explaintable', 'class' => 'mt-3']);
    echo html_writer::end_div(); // Card-body.

    echo html_writer::end_div(); // Card.
    echo html_writer::end_div(); // Explainpanel.
    // End explain panel.

    echo $OUTPUT->box_end();

    $PAGE->requires->js_call_amd('report_querybuilder/analyze', 'init');

    $PAGE->requires->js_call_amd('report_querybuilder/editor', 'init', [[
        'strings' => [
            'entersql'      => get_string('enter_sql', 'report_querybuilder'),
            'queryrequired' => get_string('query_required', 'report_querybuilder'),
            'confirmdelete' => get_string('confirm_delete', 'report_querybuilder'),
        ],
    ]]);

    echo $OUTPUT->footer();
    exit;
}

if (!$advanced) {
    echo $OUTPUT->header();

    $base = optional_param('base', null, PARAM_TEXT);
    $joins = [];
    if (!empty($_POST['joins']) && is_array($_POST['joins'])) {
        $joins = clean_param_array($_POST['joins'], PARAM_TEXT);
    } else if (!empty($_GET['joins']) && is_array($_GET['joins'])) {
        $joins = clean_param_array($_GET['joins'], PARAM_TEXT);
    }
    $filterfield = optional_param('filter_field', '', PARAM_TEXT);
    $filterop = optional_param('filter_op', '', PARAM_TEXT);
    $filtervalue = optional_param('filter_value', '', PARAM_TEXT);

    // Parse SQL param (if coming from Advanced mode).
    $builderstate = null;
    if (!empty($sqlparam)) {
        $builderstate = sql_utils::parse_sql_to_builder_state($sqlparam);
    }

    // Optional: Show a warning if SQL is too complex.
    if (!empty($sqlparam) && !$builderstate) {
        echo $OUTPUT->notification(
            get_string('sql_too_complex', 'report_querybuilder'),
            'warning'
        );
    }

    $form = new builder_form(null, [
        'base' => $builderstate['base'] ?? $base,
        'fields' => $builderstate['fields'] ?? [],
        'joins' => $builderstate['joins'] ?? $joins,
        'filter_field' => $builderstate['filter_field'] ?? $filterfield,
        'filter_op' => $builderstate['filter_op'] ?? $filterop,
        'filter_value' => $builderstate['filter_value'] ?? $filtervalue,
    ]);

    // Always get current form values.
    $formdata = $form->get_data();
    if (!$formdata) {
        $formvalues = $form->get_submitted_data();
        if ($formvalues) {
            $formdata = $formvalues;
        } else {
            $formdata = (object)[
                'base' => $builderstate['base'] ?? $base,
                'fields' => $builderstate['fields'] ?? [],
                'joins' => $builderstate['joins'] ?? $joins,
                'filter_field' => $builderstate['filter_field'] ?? $filterfield,
                'filter_op' => $builderstate['filter_op'] ?? $filterop,
                'filter_value' => $builderstate['filter_value'] ?? $filtervalue,
            ];
        }
    }

    // Always generate SQL if possible.
    if (!empty($formdata->base) && !empty($formdata->fields)) {
        $ast = compiler::build_ast(
            $formdata->base,
            $formdata->fields,
            $formdata->joins,
            $formdata->filter_field,
            $formdata->filter_op,
            $formdata->filter_value
        );
        $sql = compiler::compile($ast);
    } else {
        $sql = '';
    }

    // Toggle button.
    $generatedsql = $sql ?? '';
    echo html_writer::start_div('mb-2');
    echo html_writer::link(
        new moodle_url('/report/querybuilder/index.php', ['advanced' => 1, 'sql' => $generatedsql]),
        get_string('btn_switch_advanced', 'report_querybuilder'),
        ['class' => 'btn btn-outline-secondary btn-sm']
    );
    echo html_writer::end_div();

    // Show SQL preview only if form was submitted.
    if ($formdata && !empty($sql)) {
        echo html_writer::tag('h3', get_string('sql_preview_heading', 'report_querybuilder'));
        $highlighted = '<pre><code class="language-sql">' . htmlspecialchars($sql) . '</code></pre>';
        echo $OUTPUT->box_start('generalbox');
        echo format_text($highlighted, FORMAT_HTML);
        echo $OUTPUT->box_end();
    }

    $form->display();
}
