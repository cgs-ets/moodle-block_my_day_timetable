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
 * Javascript for mobile
 *
 * @copyright   2020 Michael Vangelovski
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
(function(self) {

  /**
   * Navigate.
   *
   * @param {int} direction
   */
  self.navigate = async function(direction) {
      const modal = await self.CoreDomUtilsProvider.showModalLoading();
      var args = {
          timetableuser: self.CONTENT_OTHERDATA.timetable.user,
          timetablerole: self.CONTENT_OTHERDATA.timetable.role,
          nav: direction,
          date: self.CONTENT_OTHERDATA.timetable.date,
          instanceid: self.CONTENT_OTHERDATA.timetable.instanceid
      }
      console.log('block_my_day_timetable/mobile/js/timetable.js: Navigate timetable: ' + JSON.stringify(args));
          self.CoreSitesProvider.getCurrentSite().read('block_my_day_timetable_get_timetable_data_for_date', args).then(function(timetabledata) {
          self.CONTENT_OTHERDATA.timetable = timetabledata;
      }).catch((message) => {
          self.CoreDomUtilsProvider.showErrorModalDefault(message, 'Failed to fetch timetable');
      }).finally(() => {
          modal.dismiss();
      });
  };

})(this);