<?php
defined('MOODLE_INTERNAL') || die();

$settings->add(new admin_setting_configcheckbox("qtype_turprove/show", get_string('settingsShow', 'qtype_turprove'), null, True));

?>