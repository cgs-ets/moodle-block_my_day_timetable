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
 *  External Web Service Template
 *
 * @package   my_day_timetable
 * @category
 * @copyright 2020 Veronica Bermegui, Michael Vangelovski
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

/** Include required files */
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->libdir.'/filelib.php');

use block_my_day_timetable\utils;

/**
 * Callback to define user preferences.
 *
 * @return array
 */
function block_my_day_timetable_user_preferences() {
    $preferences = array();
    $preferences['block_my_day_timetable_collapsed'] = array(
        'type' => PARAM_INT,
        'null' => NULL_NOT_ALLOWED,
        'default' => 1,
        'choices' => array(0, 1),
        'permissioncallback' => function($user, $preferencename) {
            global $USER;
            return $user->id == $USER->id;
        }
    );

    return $preferences;
}

/**
 * Initial timetable
 *
 */
function init_timetable($instanceid) {
    global $COURSE, $DB, $USER, $PAGE, $OUTPUT;

    $data = null;

    $context = CONTEXT_COURSE::instance($COURSE->id);
    $config = get_config('block_my_day_timetable');

    // Initialise some defaults.
    $timetableuser = $USER->username;
    $timetablerole = '';
    profile_load_custom_fields($USER);
    $userroles = array_map('trim', explode(',', $USER->profile['CampusRoles']));

    // Load in some config.
    $studentroles = array_map('trim', explode(',', $config->studentroles));
    $staffroles = array_map('trim', explode(',', $config->staffroles));

    // Determine if user is viewing this block on a profile page.
    if ( $PAGE->url->get_path() == '/user/profile.php' ) {
        $uid = $PAGE->url->get_param('id');
        if (empty($uid)) {
            $uid = $USER->id;
        }
        // Get the profile user.
        $profileuser = $DB->get_record('user', ['id' => $uid]);
        $timetableuser = $profileuser->username;
        // Load the user's custom profile fields.
        profile_load_custom_fields($profileuser);
        $profileroles = explode(',', $profileuser->profile['CampusRoles']);
        // Get the timetable user.
        if ( !$timetablerole = get_timetable_user($profileroles, $studentroles, $staffroles) ) {
            return null;
        }
        // Check whether the current user can view the profile timetable.
        if ( !can_view_on_profile($profileuser, $userroles, $staffroles) ) {
            return null;
        }
    } else {
        // Get the timetable user.
        if ( !$timetablerole = get_timetable_user($userroles, $studentroles, $staffroles) ) {
            return null;
        }
    }

    $date = date('Y-m-d', time());
    // Check if it is the end of the day.
    $endofday = new DateTime($config->endofday);
    $current_time = new DateTime('now');
    if ($current_time > $endofday) {
        $date = utils::get_next_day($date);
    }

    // Generate the new timetable.
    $nav = -1;
    list($props, $relateds) = navigate_timetable($timetableuser, $timetablerole, $nav, $date, $instanceid);

    if (!empty($props)) {
        $timetable = new block_my_day_timetable\external\timetable_exporter($props, $relateds);
        $data = $timetable->export($OUTPUT);
    }

    return $data;
}

function get_timetable_user($userroles, $studentroles, $staffroles) {
    // Check whether the timetable should be displayed for this profile user.
    // E.g. Senior student's and staff.
    if (array_intersect($userroles, $studentroles)) {
        return 'student';
    } elseif (array_intersect($userroles, $staffroles)) {
        return 'staff';
    }

    return null;
}

function can_view_on_profile($profileuser, $userroles, $staffroles) {
    global $DB, $USER;

    // Staff are always allowed to view timetables in profiles.
    if (array_intersect($userroles, $staffroles)) {
        return true;
    }

    // Students are allowed to see timetables in their own profiles.
    if ($profileuser->username == $USER->username) {
        return true;
    }

    // Parents are allowed to view timetables in their mentee profiles.
    $mentorrole = $DB->get_record('role', array('shortname' => 'parent'));
    $sql = "SELECT ra.*, r.name, r.shortname
            FROM {role_assignments} ra
            INNER JOIN {role} r ON ra.roleid = r.id
            INNER JOIN {user} u ON ra.userid = u.id
            WHERE ra.userid = ?
            AND ra.roleid = ?
            AND ra.contextid IN (SELECT c.id
                FROM {context} c
                WHERE c.contextlevel = ?
                AND c.instanceid = ?)";
    $params = array(
        $USER->id, //Where current user
        $mentorrole->id, // is a mentor
        CONTEXT_USER,
        $profileuser->id, // of the prfile user
    );
    $mentor = $DB->get_records_sql($sql, $params);
    if ( !empty($mentor) ) {
        return true;
    }

    return false;
}

/**
 * Navigate timetable.
 *
 */
function navigate_timetable($timetableuser, $timetablerole, $nav, $date, $instanceid) {
    global $USER;

    switch($nav) {
       case 0: //backwards
            $date = utils::get_prev_day($date);
            break;
       case 1: //Forward
            $date = utils::get_next_day($date);
            break;
    }

    try {
        //Get  config of this block.
        $config = get_config('block_my_day_timetable');

        // Get our prefered database driver.
        // Last parameter (external = true) means we are not connecting to a Moodle database.
        $externalDB = moodle_database::get_driver_instance($config->dbtype, 'native', true);
        // Connect to external DB
        $externalDB->connect($config->dbhost, $config->dbuser, $config->dbpass, $config->dbname, '');

        $sql = 'EXEC ' . $config->{'db' . $timetablerole . 'proc'} . ' :id, :date';

        $params = array(
            'id' => $timetableuser,
            'date' => $date,
        );

        $timetabledata = $externalDB->get_records_sql($sql, $params);

        // For staff that don't have timetables, hide block on initial load.
        if ($nav == -1 && empty($timetabledata)) {
            return;
        }

        // If data is empty and attempting to navigate cal, look for next available timetable day.
        if ($nav == 0 || $nav == 1) {
            $days = 0;
            while(empty($timetabledata) && $days <= 30) { // Look ahead a max of 30 days.
                $days++;
                if ($nav == 1) {
                    $date = utils::get_next_day($date);
                } else {
                    $date = utils::get_prev_day($date);
                }
                $params = array(
                    'id' => $timetableuser,
                    'date' => $date,
                );
                $timetabledata = $externalDB->get_records_sql($sql, $params);
            }
        }

        if (empty($timetabledata)) {
            return;
        }

        // Get Moodle class mappings
        $classmapping = array();
        if (!empty($config->mappingtable)) {
            $classcodes = array_filter(array_column($timetabledata, 'classcode'));
            if ($classcodes) {
                list($idsql, $params) = $externalDB->get_in_or_equal($classcodes);
                $sql = "SELECT  $config->mappingtableid,
                                $config->mappingtableextcode,
                                $config->mappingtablemoocode
                          FROM  $config->mappingtable
                         WHERE  SynCode $idsql";
                $classmapping = $externalDB->get_records_sql($sql, $params);
            }
        }

        // Get current term details. Only for staff.
        if ($timetablerole == 'staff') {
            $termdetails = get_term_information($externalDB, $date, $timetabledata);
            $termfinished = false;
            if ($termdetails == null){
                $termfinished = true;
            }
        }

        // Set the user preference to collapse or show the timetable.
        $userpreference = get_user_preferences('block_my_day_timetable_collapsed', 0, $USER);
        $props = (object) [
            'instanceid' => $instanceid,
            'role' => $timetablerole,
            'showprogressbar' => $config->showprogressbar,
            'user' => $timetableuser,
            'date' =>  $date,
            'hide' =>  $userpreference,
            'fromws' => ($nav == -1) ? false : true, // To remove the loading class when the tt is render.
            'day' => date('l, j F Y', strtotime($date)), //Show Day, Number Month Year.
            'termnumber' => ($timetablerole == 'staff') ? $termdetails['termnumber'] : '',
            'termweek'   => ($timetablerole == 'staff') ? $termdetails['termweek'] : '',
            'termday'    => ($timetablerole == 'staff') ? $termdetails['termday'] : '',
            'termfinished' => ($timetablerole == 'staff') ? $termfinished : true,  // For students always set to true. This way the template doesnt display term details.
        ];
        $relateds = [
            'timetabledata' => $timetabledata,
            'timetablecolours' => '{' . rtrim($config->timetablecolours, ',') . '}',
            'validperiodnames' => $config->periodnames,
            'validbreaknames' => $config->breaknames,
            'classmapping' => $classmapping,
            'timetablerole'=> $timetablerole,
            'timetableuser'=> $timetableuser,
        ];
        $timetabledata = array($props, $relateds);

    } catch (Exception $ex) {
        //throw new Exception($ex->getMessage());
    }

    return $timetabledata;


}

/**
 * Calculates the week and day of the terms.
 * @param type $externalDB
 * @param DateTime $processday
 * @return int
 */
/**
 * Calculates the week and day of the terms.
 * @param type $externalDB
 * @param string $currentday
 * @return int
 */
function get_term_information($externalDB, $currentday, $timetabledata){
    $config = get_config('block_my_day_timetable');
    $sql = 'EXEC ' . $config->dbtermproc;
    $r = $externalDB->get_record_sql($sql);

    // Check if the term finished. If that is the case, then return empty values.
    if(is_term_finished(strtotime($currentday), strtotime($r->startdate), strtotime($r->enddate))) {
        return null;
    }

    // Determine the week.
    $startday = date('w', strtotime($r->startdate));
    $firstmonday = ($startday > 1) ? date('Y-m-d', strtotime('previous monday', strtotime($r->startdate)))
        : $r->startdate;
    $dayssincestart = date_diff(new DateTime($firstmonday), new DateTime($currentday));
    $week = (int) floor($dayssincestart->days / 7 +1);

    $terminfo = [
        'termnumber' => $r->filesemester,
        'termweek' =>  $week,
        'termday' => current($timetabledata)->definitionday,
    ];

    return $terminfo;
}

/**
 * Helper function checks if the day that is being processed is after the date
 * a term finishes or earlier than the starting day of a term.
 *
 * @param int $processday
 * @param int $term_start
 * @param int $term_finished
 * @return boolean
 */
function is_term_finished($processday, $term_start, $term_finished){
    if($processday > $term_finished || $processday < $term_start){
        return true;
    }
    return false;
}
