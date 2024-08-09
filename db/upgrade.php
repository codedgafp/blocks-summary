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
 * Database upgrades for the block_summary local.
 *
 * @package   block_summary
 * @copyright  2023 Edunao SAS (contact@edunao.com)
 * @author     rcolet <remi.colet@edunao.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

/**
 * Upgrade the block_summary database.
 *
 * @param int $oldversion The version number of the plugin that was installed.
 * @return boolean
 * @throws ddl_exception
 * @throws ddl_table_missing_exception
 * @throws dml_exception
 */
function xmldb_block_summary_upgrade($oldversion = 0) {
    global $DB;

    $dbman = $DB->get_manager();
    $result = true;

    if ($oldversion < 2023113000) {
        // Define table block_summary_edit_lock to be created.
        $table = new xmldb_table('block_summary_edit_lock');

        // Adding fields to table block_summary_edit_lock.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lastupdate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table block_summary_edit_lock.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding index to table block_summary_edit_lock.
        $table->add_index('course', XMLDB_INDEX_UNIQUE, ['courseid']);
        $table->add_index('course-user', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'userid']);

        // Conditionally launch create table for block_summary_edit_lock.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Progress savepoint reached.
        upgrade_block_savepoint(true, 2023113000, 'summary');
    }

    if ($oldversion < 2023120402) {
        $blocksummarytable = new xmldb_table('block_summary');
        if ($dbman->table_exists($blocksummarytable)) {
            // Data recovery.
            $summarydata = $DB->get_records_sql('
            SELECT bs.*, c.format as courseformat
            FROM {block_summary} bs
            JOIN {course} c ON c.id = bs.courseid
            WHERE parentid IS NOT NULL');

            $newsummarydata = [];

            foreach ($summarydata as $data) {
                $newsummarydata[] = [
                    'courseid' => $data->courseid,
                    'format' => $data->courseformat,
                    'sectionid' => $data->sectionid,
                    'name' => 'depth',
                    'value' => 1,
                ];
            }

            $DB->insert_records('course_format_options', $newsummarydata);
        }

        // Remove old table.
        $blocksummarycachetable = new xmldb_table('block_summary_cache');
        if ($dbman->table_exists($blocksummarycachetable)) {
            $dbman->drop_table($blocksummarycachetable);
        }
        $blocksummaryeditorstable = new xmldb_table('block_summary_editors');
        if ($dbman->table_exists($blocksummaryeditorstable)) {
            $dbman->drop_table($blocksummaryeditorstable);
        }
        $blocksummarysessiontable = new xmldb_table('block_summary_session');
        if ($dbman->table_exists($blocksummarysessiontable)) {
            $dbman->drop_table($blocksummarysessiontable);
        }
    }

    if ($oldversion < 2023120800) {
        $DB->execute('
            UPDATE {block_instances}
            SET pagetypepattern = \'*\'
            WHERE blockname = \'summary\''
        );
    }

    return $result;
}
