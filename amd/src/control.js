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
 * Provides the block_my_day_timetable/control module
 *
 * @package   block_my_day_timetable
 * @category  output
 * @copyright 2019 Michael Vangelovski, Canberra Grammar School <michael.vangelovski@cgs.act.edu.au>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module block_my_day_timetable/control
 */
define(['jquery', 'core/log', 'core/pubsub', 'core/ajax', 'core/str'], function($, Log, PubSub, Ajax, Str) {
    'use strict';

    /**
     * Initializes the block controls.
     */
    function init(instanceid, date, displaypreference, userid, title) {
        Log.debug('block_my_day_timetable/control: initializing controls of the my_day_timetable block instance ' + instanceid);
        
        var region = $('[data-region="block_my_day_timetable-instance-' + instanceid +'"]').first();
        
        if (!region.length) {
            Log.debug('block_my_day_timetable/control: wrapping region not found!');
            return;
        }

        var control = new DailyTimetableControl(region, date, instanceid, displaypreference, userid, title); 
        control.main();
    }

    /**
     * Controls a single my_day_timetable block instance contents.
     *
     * @constructor
     * @param {jQuery} region
     */
    function DailyTimetableControl(region, date, instanceid, displaypreference, userid, title) {
        var self = this;        
        self.date = date;               
        self.region = region;
        self.userid = userid;
        self.instanceid = instanceid;
        self.displaypreference = displaypreference;
        self.title = title;
        self.role = region.data("timetablerole");
        self.username = region.data("timetableuser");
    }

    /**
     * Run the controller.
     *
     */
   DailyTimetableControl.prototype.main = function () {
        var self = this;    
    
        // Initialise width
        self.refreshPeriodLayout();
        self.region.removeClass('isloading');

        self.navigatePreviousDay();
        self.navigateNextDay();
        self.timetableVisibility();
        
        // Watch resize to adjust width
        $(window).on('resize', function(){
            self.refreshPeriodLayout();
        });

        //Subscribe to nav drawer event and resize when it completes
        PubSub.subscribe('nav-drawer-toggle-end', function(el){
            Log.debug('resizing timetable');
            self.refreshPeriodLayout();
        });
    };

    /**
     * Determine period widths.
     *
     * @method
     */
    DailyTimetableControl.prototype.refreshPeriodLayout = function () {
        var self = this;
           
        var breakWidth = 70;
        var numBreaks = self.region.data('num-breaks');
        var timetableWidth = self.region.outerWidth() - (numBreaks*breakWidth);
        var numPeriods = self.region.data('num-periods') - numBreaks;
        var potentialWidth = timetableWidth / numPeriods;

        self.region.removeClass('view-stacked');
        self.region.removeClass('view-minimal');
        self.region.removeClass('view-wrapped');
        self.region.removeClass('view-full');

        if ( timetableWidth < 520 || potentialWidth < 65 ) {
            // At this point, it really needs to be stacked
            self.setPeriodWidth('view-stacked',"100%","100%");
        } else if ( potentialWidth < 100 ) {
            var evenPeriods = 2 * Math.round(numPeriods / 2); // it would work well over 2 lines, so wrap it
            var potentialWidth = timetableWidth / evenPeriods;
            self.setPeriodWidth('view-minimal view-wrapped',potentialWidth*2 + "px",breakWidth + "px");
        } else if ( potentialWidth < 180 ) {
            // Works nicely on one line if minimised
            self.setPeriodWidth('view-minimal', potentialWidth + "px",breakWidth + "px");
        } else {
            // Works nicely on one line
            self.setPeriodWidth('view-full', potentialWidth + "px",breakWidth + "px");
        }
    };

    /**
     * Set period widths.
     *
     * @method
     */
    DailyTimetableControl.prototype.setPeriodWidth = function (classes, periodWidth, breakWidth) {
        var self = this;
        self.region.addClass(classes);
        self.region.find('.period').css({"width": periodWidth});
        self.region.find('.period.break').css({"width": breakWidth});
    };
    
     /**
     * Navigate to previous day.
     *
     * @method
     */
    DailyTimetableControl.prototype.navigatePreviousDay = function () {
        var self = this;    
        var instanceid = parseInt(self.instanceid);
      
        $('#inst' + instanceid + '.block_my_day_timetable').on('click', '.timetable-prev', function () {
            self.region.addClass('fetchingdata');
            Ajax.call([{
                methodname: 'block_my_day_timetable_get_timetable_for_date',
                args: {
                    timetableuser: self.username, 
                    timetablerole: self.role, 
                    nav: 0,
                    date: self.date, 
                    instanceid: instanceid
                },
                done:function(response){
                    Log.debug(('Timetable values retrieved successfuly.')); 
                    self.refreshLayout(response.html); 
                },
                fail: function(reason) {
                    Log.error('block_my_day_timetable. NavigatePreviousDay: Unable to get timetable.');
                    Log.debug(reason);
                    self.timetableUnavailable();
                }
            }]);     
        });
    };
    
    /**
     * Navigate to Next day.
     *
     * @method
     */
    DailyTimetableControl.prototype.navigateNextDay = function () {
        var self = this;
        var instanceid = parseInt(self.instanceid);

        $('#inst' + instanceid + '.block_my_day_timetable').on('click', '.timetable-next', function () {
            self.region.addClass('fetchingdata');
            Log.debug('Nav next day user: ' + self.user + ' Role: ' + self.role + ' Date: ' + self.date + 'instanceid' + instanceid);
            Ajax.call([{
                methodname: 'block_my_day_timetable_get_timetable_for_date',
                args: {
                    timetableuser: self.username, 
                    timetablerole: self.role,
                    nav: 1, 
                    date: self.date, 
                    instanceid: self.instanceid
                },
                done:function(response){
                    Log.debug(('Timetable values retrieved successfuly.'));   
                    self.refreshLayout(response.html);
                },
               fail: function(reason) {
                    Log.error('block_my_day_timetable. NavigateNextDay: Unable to get timetable.');
                    Log.debug(reason);
                    self.timetableUnavailable();
                }
            }]);  
        });
    };
    
    /**
     * Hide/show Timetable
     *
     * @method
     */
    DailyTimetableControl.prototype.timetableVisibility = function(){
        var self = this;
        
        // Close
        $('#inst' + self.instanceid + '.block_my_day_timetable').on('click', '.timetable-invisible', function () {
            self.region.removeClass('timetable-maximised');
            self.region.addClass('timetable-minimised');

            //Title
            self.region.find('.timetable-title').text(self.title);
            
            self.savePreferences(1);
        });
        
        // Open
        $('#inst' + self.instanceid + '.block_my_day_timetable').on('click', '.timetable-visible', function () {
            self.region.removeClass('timetable-minimised');
            self.region.addClass('timetable-maximised');
            
            //Title
            self.region.find('.timetable-title').text(self.region.data('timetableday'));
            
            self.savePreferences(0);
            self.refreshPeriodLayout();
        });
      
    };
    
    DailyTimetableControl.prototype.savePreferences = function(value)    {
        var self = this;

        var preferences = [{
            'name': 'block_my_day_timetable_collapsed',
            'value': value,
            'userid': self.userid,
        }];

        Ajax.call([{
            methodname: 'core_user_set_user_preferences',
            args: {preferences: preferences},
            done: function () {
                Log.debug('Preference saved');                     
            }
        }]);
    };

    /**
     * This refresh is use when navigating the timetable
     *
     * @method
     **/
    DailyTimetableControl.prototype.refreshLayout = function(htmlResult){
        var self = this;

        self.region.fadeOut(300, function() {
            self.region.replaceWith(htmlResult);
            self.region.show();
            self.region =  $('[data-region="block_my_day_timetable-instance-' + self.instanceid +'"]');
            self.date = self.region.data("timetabledate");
            self.region.removeClass('isloading');
            self.region.removeClass('fetchingdata');
            self.refreshPeriodLayout();
        });
    };

    DailyTimetableControl.prototype.timetableUnavailable = function() {
        var self = this;
        
        var unavailablestr = Str.get_string('timetableunavailable', 'block_my_day_timetable');
        $.when(unavailablestr).done(function(localizedString) {
            self.region.replaceWith('<h5>' + localizedString + '</h5>'); 
        });
    };

    return {
        init: init
    };
});