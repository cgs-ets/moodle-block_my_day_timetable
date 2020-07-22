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
 * @copyright 2020 Veronica Bermegui, Canberra Grammar School <veronica.bermegui@cgs.act.edu.au>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

/** Include required files */
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
 * Navigate timetable.
 *
 */
function get_timetable_for_date($timetableuser, $timetablerole, $nav, $date, $instanceid) {
    if ($nav != -1) {
        $day = date('D', $date);
    }

    $nav_date = $date;

    switch($nav) {
       case 0: //backwards
            //Check if the previous day is a working day
            if (strcmp($day,'Mon') == 0) {
                $date = utils::get_prev_day($nav_date, 3);
                $nav_date = utils::get_prev_day($nav_date, 3, true);
            } else {
                $date = utils::get_prev_day($nav_date);
                $nav_date = utils::get_prev_day($nav_date, 1, true);
            }
            break;

       case 1: //Forward
            if (strcmp($day,'Fri') == 0) {
                $date = utils::get_next_day($nav_date, 3);
                $nav_date= utils::get_next_day($nav_date, 3, true);
            } else {
                $date = utils::get_next_day($nav_date);
                $nav_date= utils::get_next_day($nav_date, 1, true);
            }
            break;
    }

    return $timetabledata = get_timetable_aux($timetableuser, $timetablerole, $date, $nav_date, $nav, $instanceid);

}

/**
 * Auxiliary function to get timetable data from external source.
 *
 */
function get_timetable_aux($timetableuser, $timetablerole, $date, $nav_date, $nav, $instanceid) {
    global $USER;

    try{
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

        //Only look for available days when navigating the timetable.
        if ($nav == 0 || $nav == 1) {
            $days = 0;
            while(empty($timetabledata) && $days <= 30) {
                $days++;

                if ($nav == 1) {
                   $date = utils::get_next_day($nav_date);
                   $nav_date= utils::get_next_day($nav_date, 1, true);
                } else {
                    $date = utils::get_prev_day($nav_date);
                    $nav_date = utils::get_prev_day($nav_date, 1, true);
                }

                $params = array(
                    'id' => $timetableuser,
                    'date' => $date,
                );
                $timetabledata = $externalDB->get_records_sql($sql, $params);
            }
        }

        // For staff that don't have timetables, hide block on initial load.
        if ($nav == -1 && empty($timetabledata)) {
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
            $daytoprocess;

            if (!is_string($nav_date)) {
                $daytoprocess = date('Y-m-d',$nav_date);
            }else{
                $daytoprocess = $nav_date;
            }

            $termdetails = getterminformationdetails($externalDB, $daytoprocess, $timetabledata);
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
            'date' => $nav_date,
            'hide' =>  $userpreference,
            'fromws' => ($nav == -1) ? false : true, // To remove the loading class when the tt is render.
            'day' => ($nav == -1) ? daytodisplay() : date('l, j F Y', $nav_date), //Show Day, Number Month Year.
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

        throw new Exception($ex->getMessage());
    }

    return $timetabledata;
}

/**
 * Calculates the week and day of the terms.
 * @param type $externalDB
 * @param DateTime $processday
 * @return int
 */
function getterminformationdetails($externalDB, $processday, $timetabledata){
    $config = get_config('block_my_day_timetable');
    $sql = 'EXEC ' . $config->dbtermproc;
    $r = $externalDB->get_records_sql($sql);
    $r = ($r[(key($r))]);

    $term_start = new DateTime($r->startdate);
    $term_finish = new DateTime($r->enddate);

    $processday = new DateTime($processday);

    // Check if the term finished. If that is the case, then return empty values.
    if(istermfinished($processday, $term_start, $term_finish)){
        return null;
    }else{

        $intervals = utils::getintervals($term_start, $term_finish);
        $termday = (current($timetabledata)->definitionday);

        $weeks = date_diff($processday, $term_start, true);

        $weeks = (floor(($weeks->days / 6)) == 0) ? 1 : floor($weeks->days / 6);

        $weeksinterm = utils::getweeksinaterm($term_start, $term_finish);

        if ($weeks > $weeksinterm ){
            $weeks = $weeksinterm;
        }

        $terminfo = ['termnumber' => $r->filesemester,
            'termweek' =>  $weeks,
            'termday' => $termday,
        ];
    }

    return $terminfo;

}
/**
 * Helper function checks if the day that is being processed is after the date
 * a term finishes or earlier than the starting day of a term.
 *
 * @param type $processday
 * @param type $term_start
 * @param type $term_finished
 * @return boolean
 */
function istermfinished($processday, $term_start, $term_finished){
    $processday = date('Y-m-d',$processday->getTimestamp());
    $term_start = date('Y-m-d',$term_start->getTimestamp());
    $term_finished = date('Y-m-d',$term_finished->getTimestamp());

    if($processday > $term_finished || $processday < $term_start){
        return true;
    }
    return false;
}

/**
 * At the end of the day, refresh the Date displayed at the top of the table
 * to the next day. To avoid confusion of showing next day TT and today date.
 */
function daytodisplay(){
    $config = get_config('block_my_day_timetable');

    $finishing_time = new DateTime($config->endofday);
    $current_time = new DateTime('now');
    $isfriday = strcmp(date('D'), 'Fri') == 0;

    if (($finishing_time < $current_time) && !$isfriday) { // EOD.
        $date = date('l, j F Y', utils::get_next_day(time(), 1, true));
    } elseif (($finishing_time < $current_time) && $isfriday) { //EOW.
        $date = date('l, j F Y', utils::get_next_day(time(), 3, true));
    } else { // Today.
        $date = date('l, j F Y', time());
    }
    return $date;

}