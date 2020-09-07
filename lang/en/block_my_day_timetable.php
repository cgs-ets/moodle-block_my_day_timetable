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
 * Strings for block_my_day_timetable
 *
 * @package   block_my_day_timetable
 * @copyright 2019 Michael Vangelovski, Canberra Grammar School <michael.vangelovski@cgs.act.edu.au>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
$string['blockname'] = 'My Day (Timetable)';
$string['title'] = 'My Day (Timetable)';
$string['my_day_timetable:addinstance'] = 'Add a "My Day (Timetable)" block';
$string['my_day_timetable:edit'] = 'Edit a Timetable block';
$string['my_day_timetable:myaddinstance'] = 'Add a "My Day (Timetable)" block to the Dashboard';
$string['pluginname'] = 'My Day (Timetable)';
$string['privacy:metadata'] = 'The "My Day (Timetable)" block does not store any personal data.';
$string['nodbsettings'] = 'You need to configure the DB options for the "My Day (Timetable)" plugin.';
$string['userprofilenotsetup'] = 'The "My Day (Timetable)" block requires custom user profile field called "CampusRoles" to be configured on your Moodle instance.';
$string['pluginname_desc'] = 'This plugin depends on Synergetic for staff timetable data.';
$string['settingsheaderdb'] = 'External database connection';
$string['dbtype'] = 'Database driver';
$string['dbtype_desc'] = 'ADOdb database driver name, type of the external database engine.';
$string['dbhost'] = 'Database host';
$string['dbhost_desc'] = 'Type database server IP address or host name. Use a system DSN name if using ODBC. Use a PDO DSN if using PDO.';
$string['dbname'] = 'Database name';
$string['dbuser'] = 'Database user';
$string['dbpass'] = 'Database password';
$string['dbstaffproc'] = 'Staff timetable stored procedure';
$string['dbstaffproc_desc'] = 'Stored procedure name to retrieve staff timetable data. This plugin expects that the stored procedure accepts a single parameter for the staff id. The return data needs to be in a specific format too.';
$string['dbstudentproc'] = 'Student timetable stored procedure';
$string['dbstudentproc_desc'] = 'Stored procedure name to retrieve student timetable data. This plugin expects that the stored procedure accepts a single parameter for the student id. The return data needs to be in a specific format too.';
$string['dbtermproc'] = 'Term details stored procedure';
$string['dbtermproc_desc'] = 'Stored procedure name to retrieve term data. This Stored procedure does not accept parameters.';

$string['timetablecolours'] = 'Timetable colours';
$string['timetablecolours_desc'] = 'Configuration for period background colours. CSS Colors added in a JSON type format, e.g. <br>"math":"#547384",<br>"science":"#8A439C",<br>The colour is selected based on whether the key is found in the class description.';
$string['showprogressbar'] = 'Show progress bar on periods?';
$string['staffroles'] = 'Staff CampusRoles (csv)';
$string['staffroles_desc'] = 'Used to determine who is a "Staff" user based on the "CampusRoles" custom profile field.';
$string['studentroles'] = 'Student CampusRoles (csv)';
$string['studentroles_desc'] = 'Used to determine who is a "Student" user based on the "CampusRoles" custom profile field.';
$string['periodnames'] = 'Valid period names (csv)';
$string['breaknames'] = 'Valid break names (csv)';


$string['mappingtable'] = 'Course code mapping table';
$string['mappingtable_desc'] = 'Leave blank if no mapping from external system to moodle course idnumbers required.';
$string['mappingtableid'] = 'Sequence number';
$string['mappingtableid_desc'] = 'Name of the column containing the sequence number in the mapping table.';
$string['mappingtableextcode'] = 'External code';
$string['mappingtableextcode_desc'] = 'Name of the column containing the external course code in the mapping table.';
$string['mappingtablemoocode'] = 'Moodle idnumber';
$string['mappingtablemoocode_desc'] = 'Name of the column containing the corresponding Moodle course idnumber.';

$string['timetableunavailable'] = 'Timetable data unavailable.';
$string['endofday'] = 'End of day';
$string['endofday_desc'] = '(E.g. 1530)';

