<?php

function xmldb_qtype_turprove_install() {
    global $DB;

    $dbman = $DB->get_manager();
    $table = new xmldb_table();
    $field = new xmldb_field();

    return true;
}
