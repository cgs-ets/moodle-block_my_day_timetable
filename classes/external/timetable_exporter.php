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

/**
 * Exporter of the day's periods.
 */
class timetable_exporter extends exporter {

    /**
     * Return the list of standard exported properties. The following properties simply pass in and out of the exporter without manipulation.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'instanceid' => [
                'type' => PARAM_INT,
            ],
            'role' => [
                'type' => PARAM_ALPHANUMEXT,
            ],
            'showprogressbar' => [
                'type' => PARAM_BOOL,
            ],
            'user' => [
                'type' =>PARAM_ALPHANUMEXT,
            ],
            'date' => [
                'type' =>PARAM_ALPHANUMEXT,
            ],
            'hide' => [
                'type' => PARAM_BOOL,
            ],
            'fromws' => [
                'type' => PARAM_BOOL,
            ],
            'day' => [
                'type' => PARAM_ALPHANUMEXT,
            ],
        ];
    }

    /**
     * Returns a list of objects that are related.
     *
     * Data needed to generate "other" properties.
     *
     * @return array
     */
    protected static function define_related() {
        return [
            'timetabledata' => 'stdClass[]',
            'timetablecolours' => 'string',
            'validperiodnames' => 'string',
            'validbreaknames' => 'string',
            'classmapping' => 'stdClass[]',       
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
            'periods' => [
                'type' => period_exporter::read_properties_definition(),
                'multiple' => true,
                'optional' => false,
            ],
            'title' => [
                'type' => PARAM_ALPHANUMEXT,
            ],
            'numperiods' => [
                'type' => PARAM_INT,
            ],
            'numbreaks' => [
                'type' => PARAM_INT,
            ],
            'isstaff' => [
                'type' => PARAM_BOOL,
            ],
            'isstudent' => [
                'type' => PARAM_BOOL,
            ]
        ];
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param renderer_base $output The renderer.
     * @return array Keys are the property names, values are their values.
     */
    protected function get_other_values(renderer_base $output) {
        global $USER;

        $config = get_config('block_my_day_timetable');
        $title = $config->title;

        $periods = [];
        $numbreaks = 0;

        // Build a useful and clean array of periods.
        foreach ($this->related['timetabledata'] as $ix => $class) {
            // Only include Periods, Sessions & Pastoral for now.
            $validperiodnames = array_map('trim', explode(',', $this->related['validperiodnames']));
            if (preg_match("/" . implode($validperiodnames, '|') . "/i", $class->perioddescription)) {
                $relateds = [
                    'timetablecolours' => $this->related['timetablecolours'],
                    'validbreaknames' => $this->related['validbreaknames'],
                    'classmapping' => $this->related['classmapping'],
                ];

                // if there is a previous period, check for duplicates
                if ( count($periods) ) {
                    $previousix = count($periods) - 1;
                    // Sometimes staff can teach 2 classes at the same time. 
                    // Check if this period is the same as the last period
                    if ( $class->perioddescription == $periods[$previousix]->perioddescription ) {
                        // Attempt to incorporate the meaningful defference between the two classes into the previous period, and skip over this one.
                        $differences = array_diff(explode(' ', $class->classdescription), explode(' ', $periods[$previousix]->classdescription));
                        if ($differences) {
                            $periods[$previousix]->classdescription .= ', ' . implode(' ', $differences);
                        }
                        continue;
                    }
                }

                //Synergetic timetable data is not consistent for staff. Sometimes it includes breaks, sometimes has empty periods for staff. For students, these are free periods, for staff just exlude these things.
                if ( $this->data->role == 'staff' ) {
                    if ( strpos($class->perioddescription, 'Period') !== false && empty($class->classcode) ) {
                        continue;
                    }
                }

                // Export the period
                $periodexporter = new period_exporter($class, $relateds);
                $period = $periodexporter->export($output);

                // Check if this period is a break
                if ($period->isbreak) {
                    $numbreaks++;
                }

                // Add the exported period to the list
                $periods[] = $period;
                
            }
        }

        return [
            'periods' => $periods,
            'title' => $title,
            'numperiods' => count($periods),
            'numbreaks' => $numbreaks,
            'isstaff' => ($this->data->role == 'staff'),
            'isstudent' => ($this->data->role == 'student'),
        ];
    }

    
}