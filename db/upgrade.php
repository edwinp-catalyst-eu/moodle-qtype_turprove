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
 * Multiple choice question type upgrade code.
 *
 * @package    qtype
 * @subpackage turprove
 * @copyright  1999 onwards Martin Dougiamas {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Upgrade code for the TUR Prove question type.
 * @param int $oldversion the version we are upgrading from.
 */

function xmldb_qtype_turprove_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Moodle v2.2.0 release upgrade line
    // Put any upgrade step following this.

    // Moodle v2.3.0 release upgrade line
    // Put any upgrade step following this.

    // Moodle v2.4.0 release upgrade line
    // Put any upgrade step following this.

    // Moodle v2.5.0 release upgrade line.
    // Put any upgrade step following this.

    // Moodle v2.6.0 release upgrade line.
    // Put any upgrade step following this.

    // Moodle v2.7.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2015081100) {

        $table = new xmldb_table('question_turprove');
        $field = new xmldb_field('questionimage', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'question');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2015081100, 'qtype', 'turprove');
    }

    if ($oldversion < 2015082700) {

        $table = new xmldb_table('question_turprove');

        $field = new xmldb_field('questionimage', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        $field = new xmldb_field('questionsound', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2015082700, 'qtype', 'turprove');
    }

    if ($oldversion < 2015092100) {

        // Define table question_turprove to be renamed to qtype_turprove_options.
        $table = new xmldb_table('question_turprove');

        // Launch rename table for question_turprove.
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'qtype_turprove_options');
        }

        upgrade_plugin_savepoint(true, 2015092100, 'qtype', 'turprove');
    }

    if ($oldversion < 2015092101) {

        // Rename field question on table qtype_turmultichoice_options to questionid.
        $table = new xmldb_table('qtype_turprove_options');
        $field = new xmldb_field('question', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'id');

        // Launch rename field question.
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'questionid');
        }

        upgrade_plugin_savepoint(true, 2015092101, 'qtype', 'turprove');
    }

    if ($oldversion < 2015092102) {

        // Define key questionid (foreign-unique) to be added to qtype_multichoice_options.
        $table = new xmldb_table('qtype_turprove_options');
        $key = new xmldb_key('questionid', XMLDB_KEY_FOREIGN_UNIQUE, array('questionid'), 'question', array('id'));

        // Launch add key questionid.
        $dbman->add_key($table, $key);

        upgrade_plugin_savepoint(true, 2015092102, 'qtype', 'turprove');
    }

    if ($oldversion < 2015092103) {

        // Define field answers to be dropped from qtype_turprove_options.
        $table = new xmldb_table('qtype_turprove_options');
        $field = new xmldb_field('answers');

        // Conditionally launch drop field answers.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2015092103, 'qtype', 'turprove');
    }

    if ($oldversion < 2015092104) {

        // Define field answers to be dropped from qtype_turprove_options.
        $table = new xmldb_table('qtype_turprove_options');
        $field = new xmldb_field('answers');

        // Conditionally launch drop field answers.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2015092104, 'qtype', 'turprove');
    }

    return true;
}
