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
 * Privacy Subsystem implementation for block_my_day_timetable.
 *
 * @package   block_my_day_timetable
 * @copyright 2019 Michael Vangelovski, Canberra Grammar School <michael.vangelovski@cgs.act.edu.au>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */


namespace block_my_day_timetable\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy Subsystem for block_links implementing null_provider.
 *
 * @package    block_my_day_timetable
 * @copyright  2019 Michael de Raadt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements 
    \core_privacy\local\metadata\null_provider,
    \core_privacy\local\request\user_preference_provider {
    
    /**
     * Get the language string identifier with the component's language
     * file to explain why this plugin stores no data.
     *
     * @return  string
     */
    public static function get_reason() : string {
        return 'privacy:metadata';
    }
    
    
     /**
     * Describe all the places where this plugin stores personal data.
     *
     * @param collection $collection Collection of items to add metadata to.
     * @return collection Collection with our added items.
     */
    public static function get_metadata(collection $collection) : collection {

        $collection->add_user_preference('block_my_day_timetable_collapsed',
                'privacy:metadata:preference:collapsed');
        
        return $collection;
    }
    /**
     * Export user preferences controlled by this plugin.
     *
     * @param int $userid ID of the user we are exporting data form.
     */
    public static function export_user_preferences(int $userid) {

       
        $collapsed = get_user_preferences('block_my_day_timetable_collapsed', 1, $userid);

        writer::export_user_preference('block_my_day_timetable',
                'block_my_day_timetable_collapsed', transform::yesno($collapsed),
                get_string('privacy:metadata', 'block_my_day_timetable'));
       
    }
}
