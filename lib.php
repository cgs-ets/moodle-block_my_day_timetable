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
 * Auxiliar function to get timetable data from external source.
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

        //Set the user preference to collapse or show the timetable. 
        $userpreference = get_user_preferences('block_my_day_timetable_collapsed', 0, $USER);
        
        $props = (object) [
            'instanceid' => $instanceid,
            'role' => $timetablerole,             
            'showprogressbar' => $config->showprogressbar,   
            'user' => $timetableuser,
            'date' => $nav_date,
            'hide' =>  $userpreference,
            'fromws' => ($nav == -1) ? false : true, //To remove the loading class when the tt is render. $date
            'day' => ($nav == -1) ? date('l, j F Y', time()) : date('l, j F Y', $nav_date), //Show Day, Number Month Year.
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
