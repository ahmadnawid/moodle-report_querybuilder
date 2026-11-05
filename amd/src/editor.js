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
 * AMD module for Query Builder SQL editor interactions.
 *
 * @module     report_querybuilder/editor
 * @copyright  2026 Ahmad Nawid Mustafazada <ahmadnawid.mz@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/notification'], function(Notification) {
    return {
        /**
         * Initialise the editor module.
         *
         * @param {Object} params Contains a strings object with entersql, queryrequired and confirmdelete.
         */
        init: function(params) {
            var strings = (params && params.strings) || {};
            var toggle = document.getElementById('modeswitchbtn');
            var textarea = document.getElementById('advsql');
            var hiddenquery = document.getElementById('hiddenquery');
            var saveform = document.getElementById('savequeryform');
            var editorform = document.getElementById('sqleditorform');
            var deleteform = document.getElementById('deletequeryform');

            // Carry the current SQL into builder mode.
            if (toggle && textarea) {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (!textarea.value.trim()) {
                        return;
                    }
                    var url = new URL(toggle.href, window.location.origin);
                    url.searchParams.set('sql', textarea.value);
                    window.location.href = url.toString();
                });
            }

            // Copy the live SQL into the hidden field before saving.
            if (saveform && textarea && hiddenquery) {
                saveform.addEventListener('submit', function() {
                    hiddenquery.value = textarea.value;
                });
            }

            // Block running an empty query.
            if (editorform && textarea) {
                editorform.addEventListener('submit', function(e) {
                    if (!textarea.value.trim()) {
                        e.preventDefault();
                        Notification.alert(strings.queryrequired, strings.entersql);
                    }
                });
            }

            // Ask for confirmation before deleting.
            if (deleteform) {
                deleteform.addEventListener('submit', function(e) {
                    // eslint-disable-next-line no-alert
                    if (!window.confirm(strings.confirmdelete)) {
                        e.preventDefault();
                    }
                });
            }
        }
    };
});