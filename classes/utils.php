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
 * Plugin utils.
 *
 * @package   block_my_day_timetable
 * @copyright 2019 Michael Vangelovski, Canberra Grammar School <michael.vangelovski@cgs.act.edu.au>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_my_day_timetable;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir .'\filelib.php');
require_once($CFG->libdir .'\accesslib.php');

/**
 * Provides utility functions for this plugin.
 *
 * @package   block_my_day_timetable
 * @copyright 2019 Michael Vangelovski, Canberra Grammar School <michael.vangelovski@cgs.act.edu.au>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

class utils {

    /**
     * Iterate through an array until one of the array keys is found in a string and return the corresponding value.
     *
     * @param string $haystack The array to match against a string
     * @param string $string The string that you want to search against
     * @return mixed Returns the value for the key found to be contained in the string, FALSE otherwise.
     */
    public static function array_get_by_key_in_string($haystack, $string) {
        foreach ($haystack as $key => $value) {
            if (stripos($string, $key) !== false) {
                return $value;
            }
        }
        return false;
    }

    /**
     * Get the previous day.
     *
     * @param int $daytimestamp The day timestamp.
     * @param int $days. Number of days to subtract.
     * @param int $astimestamp. Whether to return the date as a timestamp.
     * @return date|int. Date in Y-m-d or timestamp.
     */
    public static function  get_prev_day($daytimestamp, $days = 1, $astimestamp = false) {
        $date = new \DateTime();
        $date->setTimestamp($daytimestamp);
        $date->modify('-' . $days . ' day');

        if ($astimestamp) {
            return $date->getTimestamp();
        }

        return date('Y-m-d', $date->getTimestamp());
    }

    /**
     * Get the next day.
     * If $days > 0 get the next business day.
     * @param int $daytimestamp The day timestamp.
     * @param int $days. Number of days to add.
     * @param int $astimestamp. Whether to return the date as a timestamp.
     * @return date|int. Date in Y-m-d or timestamp.
     */
      public static function get_next_day($daytimestamp, $days = 1, $astimestamp = false) {
        $date = new \DateTime();
        $date->setTimestamp($daytimestamp);
        $date->modify('+' . $days . ' day');

        if ($astimestamp) {
            return $date->getTimestamp();
        }

        return date('Y-m-d', $date->getTimestamp());
    }
    /**
     * Return an array with all the 2 weeks intervals in a given term
     *
     * @param type $term_start
     * @param type $term_finish
     * @return array.
     */
    public static function getintervals($term_start, $term_finish) {
        $day = new \DateTime('now');
        $start = $term_start;
        $intervals = array();
        $weeksinterm = utils::getweeksinaterm($term_start, $term_finish);


        while ($weeksinterm > 0) {
            $finish = utils::get_next_day($start->getTimestamp(), 14);
            $intervals[date('Y-m-d', $start->getTimestamp())] = $finish;
            $start = new \Datetime($finish);
            $day = $finish;
            $weeksinterm -= 2.5;
        }

        // There are some cases where the two week cycle ends before the end of the term
        // In that case add an extra interval
        if (end($intervals) < $term_finish) {
            $ws = end($intervals);
            $intervals[$ws] = date('Y-m-d', $term_finish->getTimestamp());
        }
       
        return $intervals;
    }

    /**
     * Returns the number of weeks in a term.
     * @param type $term_start
     * @param type $term_finish
     * @return int
     */
    public static function getweeksinaterm($term_start, $term_finish){
        $countweeks = 1;
        $week = $term_start;
        $cw = 0;

        while($week < $term_finish) {
            $week = new \DateTime(utils::get_next_day($term_start->getTimestamp(), (7 * $countweeks)));
            $countweeks++;
            $cw++ ;
        }

       return $cw;
    }

    /**
     * Return the number of days from a two weeks cycle, skipping weekends.     *
     * @param type $intervals
     * @return int
     */
    public static function gettermday($intervals,$day){
        $processday = date('Y-m-d', $day);
        $begincicle = '';
        $endcycle = '';

        // Get the week range to look at.
        foreach($intervals as $start => $finish){
            if ($processday >= $start && $processday < $finish){
                $begincicle = $start;
                $endcycle = $finish;
                break;
            }
        }

        $begincicle = new \DateTime($begincicle);
        $endcycle = new \DateTime($endcycle);

        $countdays= 1;
        $cont = true;
        $processday = new \DateTime($processday);

        // Count the days. Skip Saturday and Sunday.
        while($cont && $begincicle < $endcycle) {
            if( $processday > $begincicle){
                $begincicle = new  \DateTime(utils::get_next_day($begincicle->getTimestamp(),$cont));
                if(strcmp(date('D',$begincicle->getTimestamp()),'Sat') != 0 &&
                    strcmp(date('D',$begincicle->getTimestamp()),'Sun') != 0 ){
                    $countdays++;

                }
            }else{
                $cont = false;
            }
        }
        return $countdays;
    }
}