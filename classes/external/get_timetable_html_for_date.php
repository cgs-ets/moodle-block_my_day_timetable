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

namespace block_my_day_timetable\external;

defined('MOODLE_INTERNAL') || die();

use external_function_parameters;
use external_value;
use external_single_structure;

require_once($CFG->libdir.'/externallib.php');
require_once($CFG->dirroot . '/blocks/my_day_timetable/lib.php');

/**
 * Trait implementing the external function block_my_day_timetable_navigate_timetable
 */
trait get_timetable_html_for_date {


    /**
     * Returns description of method parameters
     * @return external_function_parameters
    */

    public static  function get_timetable_html_for_date_parameters(){
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
    public static function get_timetable_html_for_date($timetableuser, $timetablerole, $nav, $date, $instanceid) {
        global $USER, $PAGE;

        $context = \context_user::instance($USER->id);
        self::validate_context($context);
        //Parameters validation
        self::validate_parameters(self::get_timetable_html_for_date_parameters(),
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

        return array(
            'html' => $output->render_from_template('block_my_day_timetable/content', $data),
        );
    }

    /**
     * Describes the structure of the function return value.
     *
     * @return external_single_structure
     *
     */
    public static function get_timetable_html_for_date_returns(){
        return new external_single_structure(
            array(
                'html' => new external_value(PARAM_RAW,'HTML of new timetable'),
            )
        );
    }
}