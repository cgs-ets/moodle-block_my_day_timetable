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
 * Provides {@link block_my_day_timetable\external\timetable_exporter} class.
 *
 * @package   block_my_day_timetable
 * @copyright 2019 Michael Vangelovski, Canberra Grammar School <michael.vangelovski@cgs.act.edu.au>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_my_day_timetable\external;

defined('MOODLE_INTERNAL') || die();

use renderer_base;
use core\external\exporter;
use moodle_url;
use block_my_day_timetable\utils;

/**
 * Exporter of a single period
 */
class period_exporter extends exporter {

    /**
     * Return the list of standard exported properties. 
     *
     * These are properties you would read directly from a table row, 
     * or data you would save to a table to read from later. 
     * These properties are required in order to export the item.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'period' => [
                'type' => PARAM_INT,
            ],
            'sorttime' => [
                'type' => PARAM_RAW,
            ],
            'timetabledatetimeto' => [
                'type' => PARAM_RAW,
            ],
            'perioddescription' => [
                'type' => PARAM_ALPHANUMEXT,
            ],
            'room' => [
                'type' => PARAM_ALPHANUMEXT,
            ],
            'classdescription' => [
                'type' => PARAM_ALPHANUMEXT,
            ],
            'classcode' => [
                'type' => PARAM_ALPHANUMEXT,
            ],
            'staffid' => [
                'type' => PARAM_ALPHANUMEXT,
            ],
        ];
    }


    /**
     * Return the list of additional properties.
     *
     * Calculated values or properties generated on the fly based on standard properties and related data.
     *
     * @return array
     */
    protected static function define_other_properties() {
        return [
            'extrahtmlclasses' => [
                'type' => PARAM_ALPHANUMEXT,
            ],
            'altdescription' => [
                'type' => PARAM_RAW,
            ],
            'courselink' => [
                'type' => PARAM_RAW,
            ],
            'isbreak' => [
                'type' => PARAM_BOOL,
            ],
            'teacherphoto' => [
                'type' => PARAM_RAW,
            ],
            'starttime' => [
                'type' => PARAM_RAW,
            ],
            'endtime' => [
                'type' => PARAM_RAW,
            ],
            'progressstatus' => [
                'type' => PARAM_ALPHANUMEXT,
            ],
            'progressamount' => [
                'type' => PARAM_INT,
            ],
            'url' => [
                'type' => PARAM_RAW,
            ],
            'classcolor' => [
                'type' => PARAM_RAW,
            ],
        ];
    }


    /**
     * Returns a list of objects that are related.
     *
     * @return array
     */
    protected static function define_related() {
        return [
            'timetablecolours' => 'string',
            'validbreaknames' => 'string',
            'classmapping' => 'stdClass[]',
        ];
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param renderer_base $output The renderer.
     * @return array Keys are the property names, values are their values.
     */
    protected function get_other_values(renderer_base $output) {
        global $DB;

        $otherdata = array();

        $otherdata['extrahtmlclasses'] = '';
        $otherdata['altdescription'] = "";

        // Check for mapped course. 
        $otherdata['courselink'] = '';
        $classmapping = array_filter($this->related['classmapping'], function($map) {
            return $map->syncode == $this->data->classcode;
        });
        foreach ($classmapping as $classmap) {
            if ($classmap->moodlecode) {
                $course = $DB->get_record('course', array('idnumber' => $classmap->moodlecode));
                if ($course) {
                    // Use alt description for the course instead of the Synergetic timetable desc.
                    $otherdata['altdescription'] = $course->fullname;
                    $url = new \moodle_url('/course/view.php', array('id' => $course->id));
                    $otherdata['courselink'] = $url->out(false);
                }
            }
        }

        // Check for free period.
        if (empty($this->data->classdescription)) {
            $otherdata['altdescription'] = "Free period";
        }

        // Check if it is a break.
        $otherdata['isbreak'] = false;
        $breaks = array_map('trim', explode(',', $this->related['validbreaknames']));
        if (in_array($this->data->classdescription, $breaks) ) {
            $otherdata['extrahtmlclasses'] .= ' break';
            $otherdata['isbreak'] = true;
        }

        // Add period progress
        $this->calc_period_progress($otherdata, strtotime($this->data->sorttime), strtotime($this->data->timetabledatetimeto));

        // Add period background colour
        $configcolors = json_decode($this->related['timetablecolours'], true);
        $otherdata['classcolor'] = utils::array_get_by_key_in_string($configcolors, $this->data->classdescription);

        // Add teacher photo to class
        $otherdata['teacherphoto'] = '';
        if ( $this->data->staffid ) {
            $teacher = $DB->get_record('user', array('username'=>$this->data->staffid));
            if ($teacher) {
                $otherdata['teacherphoto'] = new moodle_url('/user/pix.php/'.$teacher->id.'/f2.jpg');
            }
        }
        
        // Find and add course link based on class code
        $otherdata['url'] = '';

        // Add formatted times
        $otherdata['starttime'] = date('G:i',strtotime($this->data->sorttime));
        $otherdata['endtime'] = date('G:i',strtotime($this->data->timetabledatetimeto));

        return $otherdata;
    }


    /*
    * Gets a reference to a period and updates its progress
    */
    protected function calc_period_progress(&$data, $start, $end) {
        $currtime =  time();
        $data['progressstatus'] = 'upcoming';
        $data['progressamount'] = 0;
        if ( $currtime >= $start && $currtime <= $end ) {
            $data['progressstatus'] = 'inprogress';
            $len = $end - $start;
            $done = $currtime - $start;
            $data['progressamount'] = ( $done / $len ) * 100;
        }
        if ( $currtime >= $end ) {
            $data['progressstatus'] = 'complete';
            $data['progressamount'] = 100;
        }
    }
    
   
    
    

}