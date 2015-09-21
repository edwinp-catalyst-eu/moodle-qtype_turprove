<?php

function xmldb_qtype_turprove_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

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
