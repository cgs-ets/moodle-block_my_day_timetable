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
 * Defines the global settings of the block
 *
 * @package   block_my_day_timetable
 * @copyright 2019 Michael Vangelovski, Canberra Grammar School <michael.vangelovski@cgs.act.edu.au>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_heading(
        'block_my_day_timetable_settings', 
        '', 
        get_string('pluginname_desc', 'block_my_day_timetable')
    ));

    $settings->add(new admin_setting_heading(
        'block_my_day_timetable_exdbheader', 
        get_string('settingsheaderdb', 'block_my_day_timetable'), 
        ''
    ));

    $options = array('', "mysqli", "oci", "pdo", "pgsql", "sqlite3", "sqlsrv");
    $options = array_combine($options, $options);
    $settings->add(new admin_setting_configselect(
        'block_my_day_timetable/dbtype', 
        get_string('dbtype', 'block_my_day_timetable'), 
        get_string('dbtype_desc', 'block_my_day_timetable'), 
        '', 
        $options
    ));

    $settings->add(new admin_setting_configtext('block_my_day_timetable/dbhost', get_string('dbhost', 'block_my_day_timetable'), get_string('dbhost_desc', 'block_my_day_timetable'), 'localhost'));

    $settings->add(new admin_setting_configtext('block_my_day_timetable/dbuser', get_string('dbuser', 'block_my_day_timetable'), '', ''));

    $settings->add(new admin_setting_configpasswordunmask('block_my_day_timetable/dbpass', get_string('dbpass', 'block_my_day_timetable'), '', ''));

    $settings->add(new admin_setting_configtext('block_my_day_timetable/dbname', get_string('dbname', 'block_my_day_timetable'), '', ''));

    $settings->add(new admin_setting_configtext('block_my_day_timetable/dbstaffproc', get_string('dbstaffproc', 'block_my_day_timetable'), get_string('dbstaffproc_desc', 'block_my_day_timetable'), ''));

    $settings->add(new admin_setting_configtext('block_my_day_timetable/dbstudentproc', get_string('dbstudentproc', 'block_my_day_timetable'), get_string('dbstudentproc_desc', 'block_my_day_timetable'), ''));



    // Mapping timetable class codes to Moodle courses.
    $settings->add(new admin_setting_configtext('block_my_day_timetable/mappingtable', get_string('mappingtable', 'block_my_day_timetable'), get_string('mappingtable_desc', 'block_my_day_timetable'), ''));
    $settings->add(new admin_setting_configtext('block_my_day_timetable/mappingtableid', get_string('mappingtableid', 'block_my_day_timetable'), get_string('mappingtableid_desc', 'block_my_day_timetable'), ''));
    $settings->add(new admin_setting_configtext('block_my_day_timetable/mappingtableextcode', get_string('mappingtableextcode', 'block_my_day_timetable'), get_string('mappingtableextcode_desc', 'block_my_day_timetable'), ''));
    $settings->add(new admin_setting_configtext('block_my_day_timetable/mappingtablemoocode', get_string('mappingtablemoocode', 'block_my_day_timetable'), get_string('mappingtablemoocode_desc', 'block_my_day_timetable'), ''));



    $settings->add(new admin_setting_configtextarea('block_my_day_timetable/timetablecolours', get_string('timetablecolours', 'block_my_day_timetable'), get_string('timetablecolours_desc', 'block_my_day_timetable'), ''));

    $settings->add(new admin_setting_configcheckbox('block_my_day_timetable/showprogressbar', get_string('showprogressbar', 'block_my_day_timetable'), '', ''));

    // The user's constit codes are how this plugin determines which timetable to fetch (student vs staff).
    $settings->add(new admin_setting_configtext('block_my_day_timetable/studentroles', get_string('studentroles', 'block_my_day_timetable'), get_string('studentroles_desc', 'block_my_day_timetable'), ''));

    $settings->add(new admin_setting_configtext('block_my_day_timetable/staffroles', get_string('staffroles', 'block_my_day_timetable'), get_string('staffroles_desc', 'block_my_day_timetable'), ''));

    $settings->add(new admin_setting_configtext('block_my_day_timetable/periodnames', get_string('periodnames', 'block_my_day_timetable'), '', ''));
    $settings->add(new admin_setting_configtext('block_my_day_timetable/breaknames', get_string('breaknames', 'block_my_day_timetable'), '', ''));

    $settings->add(new admin_setting_configtext('block_my_day_timetable/title', get_string('title', 'block_my_day_timetable'), '', ''));
    $settings->add(new admin_setting_configtext('block_my_day_timetable/endofday', get_string('endofday', 'block_my_day_timetable'), get_string('endofday_desc', 'block_my_day_timetable'), '1530'));

}
