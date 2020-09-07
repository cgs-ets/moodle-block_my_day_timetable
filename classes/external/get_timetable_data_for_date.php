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
 *  Web service to get timetable data.
 *
 * @package   my_day_timetable
 * @category
 * @copyright 2020 Michael Vangelovski
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_my_day_timetable\external;

defined('MOODLE_INTERNAL') || die();

use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;

require_once($CFG->libdir.'/externallib.php');
require_once($CFG->dirroot . '/blocks/my_day_timetable/lib.php');

/**
 * Trait implementing the external function block_my_day_timetable_navigate_timetable
 */
trait get_timetable_data_for_date {


    /**
     * Returns description of method parameters
     * @return external_function_parameters
    */

    public static  function get_timetable_data_for_date_parameters(){
        return new external_function_parameters(
            array(
                  'timetableuser' => new external_value(PARAM_ALPHANUMEXT, 'id of the user'),
                  'timetablerole' => new external_value(PARAM_ALPHANUMEXT, 'role of the user'),
                  'nav' => new external_value(PARAM_INT, 'Nav direction'),
                  'date' => new external_value(PARAM_RAW, 'Date'),
                  'instanceid' => new external_value(PARAM_INT, 'Instance ID')
            )
        );
    }

    /**
     * Navigate the timetable.
     * @param  string $timetableuser represents a user.
     *         string $timetablerole represents the role of the user.
     *         int $date represents the date in timestamp format.
     *         int $nav represents a nav direction, 0: Backward, 1: Forward.
     * @return a timetable for a user.
     */
    public static function get_timetable_data_for_date($timetableuser, $timetablerole, $nav, $date, $instanceid) {
        global $USER, $PAGE;

        $context = \context_user::instance($USER->id);
        self::validate_context($context);
        //Parameters validation
        self::validate_parameters(self::get_timetable_data_for_date_parameters(),
            array(
                  'timetableuser' => $timetableuser,
                  'timetablerole'=> $timetablerole,
                  'nav'=> $nav,
                  'date'=> $date,
                  'instanceid'=> $instanceid,
            )
        );

        // Generate the new timetable
        list($props, $relateds) = navigate_timetable($timetableuser, $timetablerole, $nav, $date, $instanceid);

        $exporter = new \block_my_day_timetable\external\timetable_exporter($props, $relateds);

        $output = $PAGE->get_renderer('core');
        $data = $exporter->export($output);

        return $data;
    }

    /**
     * Describes the structure of the function return value.
     *
     * @return external_single_structure
     *
     */
    public static function get_timetable_data_for_date_returns(){
        return new external_single_structure(
            array(
                'instanceid' => new external_value(PARAM_INT,'Block instance id'),
                'role' => new external_value(PARAM_RAW,'Role of the timetable user'),
                'showprogressbar' => new external_value(PARAM_INT,'Whether to show the progress bar'),
                'user' => new external_value(PARAM_RAW,'Username'),
                'date' => new external_value(PARAM_RAW,'Date'),
                'hide' => new external_value(PARAM_INT,'Whether to hide the timetable or not'),
                'fromws' => new external_value(PARAM_BOOL,'Whether the request is from a WS or not'),
                'day' => new external_value(PARAM_RAW,'Readable date'),
                'termnumber' => new external_value(PARAM_RAW,'Term number'),
                'termweek' => new external_value(PARAM_RAW,'Term week'),
                'termday' => new external_value(PARAM_RAW,'Term day'),
                'termfinished' => new external_value(PARAM_BOOL,'Whether the term is finished or not'),
                'periods' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'period' => new external_value(PARAM_RAW, 'Period number'),
                            'sorttime' => new external_value(PARAM_RAW, 'Start datetime'),
                            'timetabledatetimeto' => new external_value(PARAM_RAW, 'End datetime'),
                            'perioddescription' => new external_value(PARAM_RAW, 'Period description'),
                            'room' => new external_value(PARAM_RAW, 'Period room'),
                            'classdescription' => new external_value(PARAM_RAW, 'Class description'),
                            'classcode' => new external_value(PARAM_RAW, 'Class code'),
                            'staffid' => new external_value(PARAM_RAW, 'Teacher username'),
                            'extrahtmlclasses' => new external_value(PARAM_RAW, 'Extra html class for period'),
                            'altdescription' => new external_value(PARAM_RAW, 'Alternative class description'),
                            'courselink' => new external_value(PARAM_RAW, 'Moodle course url'),
                            'isbreak' => new external_value(PARAM_BOOL, 'Whether the period is a break or not'),
                            'teacherphoto' => new external_value(PARAM_RAW, 'Teacher profile photo'),
                            'starttime' => new external_value(PARAM_RAW, 'Start time'),
                            'endtime' => new external_value(PARAM_RAW, 'End time'),
                            'progressstatus' => new external_value(PARAM_RAW, 'Progress status'),
                            'progressamount' => new external_value(PARAM_RAW, 'Progress percent'),
                            'classcolor' => new external_value(PARAM_RAW, 'Course color'),
                        )
                    )
                ),
                'title' => new external_value(PARAM_RAW,'Block title'),
                'numperiods' => new external_value(PARAM_INT,'Number of periods in the day'),
                'numbreaks' => new external_value(PARAM_INT,'Number of breaks in the day'),
                'isstaff' => new external_value(PARAM_BOOL,'Whether the timetable user is a staff member or not'),
                'isstudent' => new external_value(PARAM_INT,'Whether the timetable user is a student member or not'),
            )
        );
    }
}