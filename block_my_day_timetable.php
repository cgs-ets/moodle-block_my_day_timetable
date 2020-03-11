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
 * This block generates a daily timetable based on external user class data.
 *
 * @package   block_my_day_timetable
 * @copyright 2019 Michael Vangelovski, Canberra Grammar School <michael.vangelovski@cgs.act.edu.au>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

use block_my_day_timetable\utils;
require_once($CFG->dirroot . '/blocks/my_day_timetable/lib.php');

class block_my_day_timetable extends block_base {

    /**
     * Core function used to initialize the block.
     */
    public function init() {
        $this->title = get_string('title', 'block_my_day_timetable');
    }

    /**
    * We have global config/settings data.
    * @return bool
    */
    public function has_config() {
        return true;
    }

    /**
     * Controls whether multiple instances of the block are allowed on a page
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Controls whether the block is configurable
     *
     * @return bool
     */
    public function instance_allow_config() {
        return false;
    }


    /**
     * Set where the block should be allowed to be added
     *
     * @return array
     */
    public function applicable_formats() {
        return array('all' => true);
    }


    /**
     * Used to generate the content for the block.
     * @return object
     */
    public function get_content() {
        global $COURSE, $DB, $USER, $PAGE, $OUTPUT;

        if ($this->content !== null ) {
            return $this->content;
        }
        
        $this->content = new stdClass;            
                
        $this->content->text = '';
        $this->content->footer = '';
        
        $context = CONTEXT_COURSE::instance($COURSE->id);
        $config = get_config('block_my_day_timetable');
        
        // Check DB settings are available
        if( empty($config->dbtype) || 
                empty($config->dbhost) || 
                empty($config->dbuser) || 
                empty($config->dbpass) || 
                empty($config->dbname) || 
                empty($config->dbstaffproc) || 
                empty($config->dbstudentproc)  ) {
            $notification = new \core\output\notification(get_string('nodbsettings', 'block_my_day_timetable'),
                                                          \core\output\notification::NOTIFY_ERROR);
            $notification->set_show_closebutton(false);
            $this->content->text = $OUTPUT->render($notification);
            return $this->content;
        }

        // CampusRoles profile field is required by this plugin.
        if(!isset($USER->profile['CampusRoles'])) {
            $notification = new \core\output\notification(get_string('userprofilenotsetup', 'block_my_day_timetable'),
                                                          \core\output\notification::NOTIFY_ERROR);
            $notification->set_show_closebutton(false);
            $this->content->text = $OUTPUT->render($notification);
            return $this->content;
        }

        // Initialise some defaults.
        $timetableuser = $USER->username;
        $timetablerole = '';
        $userroles = array_map('trim', explode(',', $USER->profile['CampusRoles']));

        // Load in some config.
        $studentroles = array_map('trim', explode(',', $config->studentroles));
        $staffroles = array_map('trim', explode(',', $config->staffroles));

        // Determine if user is viewing this block on a profile page.
        if ( $PAGE->url->get_path() == '/user/profile.php' ) {
            // Get the profile user.
            $profileuser = $DB->get_record('user', ['id' => $PAGE->url->get_param('id')]);
            $timetableuser = $profileuser->username;
            // Load the user's custom profile fields. 
            profile_load_custom_fields($profileuser); 
            $profileroles = explode(',', $profileuser->profile['CampusRoles']);
           
            // Check whether the timetable should be displayed for this profile user.
            // E.g. Senior student's and staff.
            if (array_intersect($profileroles, $studentroles)) {
                $timetablerole = 'student';
            } 
            elseif (array_intersect($profileroles, $staffroles)) {
                $timetablerole = 'staff';
            }
            else {
                return null;
            }

            // Determine who is allowed to view this timetable.
            $allowed = false;

            // Staff are always allowed to view timetables in profiles.
            if (array_intersect($userroles, $staffroles)) { 
                $allowed = true;
            }

            // Students are allowed to see timetables in their own profiles.
            if ($profileuser->username == $USER->username) { 
                $allowed = true;
            }
           
            // Parents are allowed to view timetables in their mentee profiles.
            $mentorrole = $DB->get_record('role', array('shortname' => 'parent'));
            $sql = "SELECT ra.*, r.name, r.shortname
                    FROM {role_assignments} ra 
                    INNER JOIN {role} r ON ra.roleid = r.id
                    INNER JOIN {user} u ON ra.userid = u.id
                    WHERE ra.userid = ? 
                    AND ra.roleid = ? 
                    AND ra.contextid IN (SELECT c.id
                        FROM {context} c
                        WHERE c.contextlevel = ?
                        AND c.instanceid = ?)";
            $params = array(
                $USER->id, //Where current user
                $mentorrole->id, // is a mentor
                CONTEXT_USER, 
                $profileuser->id, // of the prfile user
            );
            $mentor = $DB->get_records_sql($sql, $params);
            if ( !empty($mentor) ) {
                $allowed = true;
            }

            if ( !$allowed ) {
                return null;
            }

        } else {
            // Check whether the timetable should be displayed for this user.
            if (array_intersect($userroles, $studentroles)) {
                $timetablerole = 'student';
            } 
            elseif (array_intersect($userroles, $staffroles)) {
                $timetablerole = 'staff';
            }
            else {
                return $timetablerole = null;
            }
        }     
       
        try {
            //Get the day depending on the time. End of day, End of week or current day.
            $finishing_time = new DateTime($config->endofday);
            $current_time = new DateTime('now');

            $isfriday = strcmp(date('D'), 'Fri') == 0;
            if (($finishing_time < $current_time) && !$isfriday) { // EOD.
                $date = utils::get_next_day(time());
            } elseif (($finishing_time < $current_time) && $isfriday) { //EOW.
                $date = utils::get_next_day(time(), 3); 
            } else { // Today.
                $date = date('Y-m-d', time());
            }

            // Generate the new timetable.
            $nav = -1;
            list($props, $relateds) = get_timetable_for_date($timetableuser, $timetablerole, $nav, $date, $this->instance->id);
            $timetable = new block_my_day_timetable\external\timetable_exporter($props, $relateds);

            $this->content->text = $OUTPUT->render_from_template('block_my_day_timetable/content', $timetable->export($OUTPUT));

        } catch (Exception $e) {
            $this->content->text .= '<h5>' . get_string('timetableunavailable', 'block_my_day_timetable') . '</h5>';
        }    
       
        return $this->content;
    }

    
    /**
     * Gets Javascript required for the widget functionality.
     */
    public function get_required_javascript() {
        global $USER;
        $config = get_config('block_my_day_timetable');
        parent::get_required_javascript();
        $this->page->requires->js_call_amd('block_my_day_timetable/control', 'init', [
            'instanceid' => $this->instance->id,
            'date' => time(),
            'preference' => get_user_preferences('block_my_day_timetable_collapsed'),
            'userid' => $USER->id,
            'title' => $config->title,
        ]);
    }
    

}
