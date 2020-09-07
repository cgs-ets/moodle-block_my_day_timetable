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
 * Mobile output class
 *
 * @package    block_my_day_timetable
 * @copyright  2020 Michael Vangelovski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_my_day_timetable\output;

defined('MOODLE_INTERNAL') || die();

use context_module;
require_once($CFG->dirroot . '/blocks/my_day_timetable/lib.php');

class mobile {

    /**
     * Returns the timetable view for the mobile app.
     *
     * @param array $args Web service args, courseid is required.
     * @return array Web service response: template, javascript and initial page of messages.
     */
    public static function timetable_view(array $args) {
        global $OUTPUT, $PAGE, $DB, $CFG;
        if ($args['contextlevel'] != 'course') {
            return;
        }
        $course = get_course($args['instanceid']);
        $PAGE->set_course($course);

        // The block instance is not passed in as an arg which sucks. 
        // Borrowing from block_news, get all of the juicy block instance data.
        $sql = "SELECT bi.id
                  FROM {block_instances} bi
                       JOIN {block} b ON bi.blockname = b.name
                       LEFT JOIN {block_positions} bp ON bp.blockinstanceid = bi.id
                           AND bp.contextid = ?
                           AND bp.pagetype = ?
                           AND bp.subpage = ?
                 WHERE parentcontextid = ?
                       AND blockname = ?
              ORDER BY COALESCE(bp.region, bi.defaultregion),
                       COALESCE(bp.weight, bi.defaultweight),
                       bi.id";
        $context = \context_course::instance($course->id);
        $params = [$context->id, 'course-view-' . $course->format, '', $context->id, 'my_day_timetable'];
        $blockinstanceid = $DB->get_field_sql($sql, $params, IGNORE_MULTIPLE);

        $OUTPUT = $PAGE->get_renderer('core');
        $data = init_timetable($blockinstanceid);

        $html = $OUTPUT->render_from_template('block_my_day_timetable/mobile', array());
        return [
            'templates' => [
                [
                    'id' => 'timetable',
                    'html' => $html
                ]
            ],
            'javascript' => file_get_contents($CFG->dirroot . '/blocks/my_day_timetable/mobile/js/timetable.js'),
            'otherdata' => [
                'timetable' => json_encode($data),
            ]
        ];
    }
}
