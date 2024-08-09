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
 * Summary controller.
 *
 * @package    block_summary
 * @copyright  2020 Edunao SAS (contact@edunao.com)
 * @author     remi <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_summary;

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/blocks/summary/classes/controllers/controller_base.php');
require_once($CFG->dirroot . '/blocks/summary/api/summary.php');

require_login();

defined('MOODLE_INTERNAL') || die();

class summary_controller extends controller_base {

    /**
     * Execute action
     *
     * @return array
     */
    public function execute() {

        try {
            $action = $this->get_param('action');

            switch ($action) {
                case 'update_summary' :
                    $courseid = $this->get_param('courseid', PARAM_INT);
                    $sectionslist = $this->get_param('sectionslist', PARAM_TEXT);
                    return $this->success($this->update_summary($courseid, $sectionslist));
                case 'check_lock' :
                    $courseid = $this->get_param('courseid', PARAM_INT);
                    return $this->success($this->check_lock($courseid));
                case 'delete_lock' :
                    $courseid = $this->get_param('courseid', PARAM_INT);
                    return $this->success($this->delete_lock($courseid));
                default:
                    return $this->success($this->$action());
            }

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }

    }

    /**
     * Update summary data.
     *
     * @param int $courseid
     * @param string $sectionslist
     * @return bool
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public static function update_summary($courseid, $sectionslist) {
        global $USER;

        $summarylock = \block_summary\summary_api::get_lock($courseid);

        if (!$summarylock || $summarylock->userid !== $USER->id) {
            return false;
        }

        return \block_summary\summary_api::update($courseid, $sectionslist);
    }

    /**
     * Check summary lock.
     *
     * @param int $courseid
     * @param string $sectionslist
     * @return bool
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public static function check_lock($courseid) {
        global $USER;
        return \block_summary\summary_api::check_lock($courseid, $USER->id);
    }

    /**
     * Check summary lock.
     *
     * @param int $courseid
     * @param string $sectionslist
     * @return bool
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public static function delete_lock($courseid) {
        global $USER;
        return \block_summary\summary_api::delete_lock($courseid, $USER->id);
    }
}
