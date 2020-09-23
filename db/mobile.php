<?php
// This file is part of the Certificate module for Moodle - http://moodle.org/
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
 * Mobile definitions
 *
 * @package    block_my_day_timetable
 * @copyright  2020 Michael Vangelovski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$addons = array(
    'block_my_day_timetable' => array(
        'handlers' => array( // Different places where the add-on will display content.
            'timetable_view' => array( // Handler unique name (can be anything)
                'displaydata' => array(
                    'title' => '',
                    'class' => 'block_my_day_timetable'
                ),
                'styles' => [
                    'url' => $CFG->wwwroot . '/blocks/my_day_timetable/mobileapp.css?v=2020092301',
                    'version' => 2020092301
                ],
                'delegate' => 'CoreBlockDelegate', // Delegate (where to display the link to the add-on)
                'method' => 'timetable_view', // Main function in \block_my_day_timetable\output\mobile
            )
        ),
        'lang' => array(
        )
    )
);
