// This file is part of Moodle - http://moodle.org/.
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
 * AMD module for Query Builder explain plan analyzer.
 *
 * @module     report_querybuilder/analyze
 * @copyright  2026 Ahmad Nawid Mustafazada <ahmadnawid.mz@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/str', 'core/notification', 'core/templates'], function(Ajax, Str, Notification, Templates) {

    var STRINGS = [
        'explain_error', 'explain_warnings', 'explain_no_warnings', 'explain_node',
        'explain_relation', 'explain_index', 'explain_rows', 'explain_cost',
        'explain_filter', 'explain_running', 'enter_sql', 'query_required'
    ];
    var NOINDEX = ['sort', 'hash', 'nested loop', 'merge join', 'hash join', 'aggregate'];

    /**
     * Render a template into a container.
     *
     * @param {String} template Template name.
     * @param {HTMLElement} container Target element.
     * @param {Object} context Template context.
     * @returns {Promise}
     */
    function render(template, container, context) {
        return Templates.render(template, context)
            .then(function(html, js) {
                Templates.replaceNodeContents(container, html, js);
                return;
            })
            .catch(Notification.exception);
    }

    /**
     * Build the context for the warnings template.
     *
     * @param {Object} data Response from the external function.
     * @param {Object} str Loaded language strings.
     * @returns {Object}
     */
    function buildWarnings(data, str) {
        var warnings = data.warnings || [];
        return {
            'has_warnings': warnings.length > 0,
            'warnings_heading': str.explain_warnings,
            'no_warnings_message': str.explain_no_warnings,
            warnings: warnings.map(function(w) {
                return {message: w.message, 'is_danger': w.level === 'danger'};
            })
        };
    }

    /**
     * Build the context for the plan table template.
     *
     * @param {Object} data Response from the external function.
     * @param {Object} str Loaded language strings.
     * @returns {Object}
     */
    function buildTable(data, str) {
        var steps = (data.steps || []).map(function(step) {
            var lnode = (step.node_type || '').toLowerCase();
            var rowclass = '';
            if (step.warnings && step.warnings.length > 0) {
                rowclass = step.warnings.some(function(w) {
                    return w.level === 'danger';
                }) ? 'table-danger' : 'table-warning';
            }
            var indent = [];
            for (var i = 0; i < step.depth; i++) {
                indent.push({});
            }
            var isseq = lnode.indexOf('seq scan') !== -1;
            var isindex = !isseq && lnode.indexOf('index') !== -1;
            var label = step.node_type || '';
            if (isseq) {
                label = '';
            } else if (isindex) {
                label = label.replace(/index scan|index only scan|bitmap index scan/i, 'Scan').trim();
            }
            return {
                'row_class': rowclass,
                indent: indent,
                'is_seqscan': isseq,
                'is_indexscan': isindex,
                'node_label': label,
                relation: step.relation || '',
                'has_index': !!step.index,
                'index_name': step.index || '',
                'no_index_needed': NOINDEX.some(function(t) {
                    return lnode.indexOf(t) !== -1;
                }),
                'rows_formatted': step.rows_est !== null ? Number(step.rows_est).toLocaleString() : '',
                'cost_total': step.cost_total !== null ? step.cost_total : '',
                filter: step.filter || ''
            };
        });
        return {
            'col_node': str.explain_node,
            'col_relation': str.explain_relation,
            'col_index': str.explain_index,
            'col_rows': str.explain_rows,
            'col_cost': str.explain_cost,
            'col_filter': str.explain_filter,
            steps: steps
        };
    }

    return {
        /**
         * Initialise the analyze module.
         */
        init: function() {
            var btn = document.getElementById('analyzebtn');
            var closebtn = document.getElementById('explainclosebtn');
            var panel = document.getElementById('explainpanel');
            var warnbox = document.getElementById('explainwarnings');
            var tablebox = document.getElementById('explaintable');
            var spinner = document.getElementById('analyzespinner');
            var textarea = document.getElementById('advsql');

            if (!btn || !textarea) {
                return;
            }

            if (closebtn) {
                closebtn.addEventListener('click', function() {
                    panel.classList.add('d-none');
                });
            }

            Str.get_strings(STRINGS.map(function(key) {
                return {key: key, component: 'report_querybuilder'};
            })).then(function(strings) {
                var str = {};
                STRINGS.forEach(function(key, i) {
                    str[key] = strings[i];
                });

                btn.addEventListener('click', function() {
                    var sql = textarea.value.trim();
                    if (!sql) {
                        Notification.alert(str.query_required, str.enter_sql);
                        return;
                    }

                    btn.disabled = true;
                    spinner.classList.remove('d-none');
                    panel.classList.remove('d-none');
                    warnbox.textContent = '';
                    render('report_querybuilder/explain_loading', tablebox, {message: str.explain_running});

                    Ajax.call([{
                        methodname: 'report_querybuilder_analyze_query',
                        args: {sql: sql}
                    }])[0].then(function(data) {
                        btn.disabled = false;
                        spinner.classList.add('d-none');
                        return Promise.all([
                            render('report_querybuilder/explain_warnings', warnbox, buildWarnings(data, str)),
                            render('report_querybuilder/explain_table', tablebox, buildTable(data, str))
                        ]);
                    }).then(function() {
                        panel.scrollIntoView({behavior: 'smooth', block: 'nearest'});
                        return;
                    }).catch(function(err) {
                        btn.disabled = false;
                        spinner.classList.add('d-none');
                        render('report_querybuilder/explain_loading', tablebox, {
                            message: str.explain_error + ' ' + (err.message || err.error || '')
                        });
                    });
                });
                return;
            }).catch(Notification.exception);
        }
    };
});