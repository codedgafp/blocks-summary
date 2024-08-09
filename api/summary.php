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
 * Summary API
 *
 * @package    block_summary
 * @copyright  2023 Edunao SAS (contact@edunao.com)
 * @author     remi <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_summary;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/lib/modinfolib.php');

class summary_api {

    private static $courses = [];

    /**
     * Get course.
     *
     * @param int $courseid
     * @return \stdClass
     * @throws \dml_exception
     */
    public static function get_course($courseid) {
        if (!isset(self::$courses[$courseid])) {
            self::$courses[$courseid] = get_course($courseid);
        }

        return self::$courses[$courseid];
    }

    /**
     * Get summary sections.
     *
     * @param int $courseid
     * @return \stdClass[]
     * @throws \dml_exception
     */
    public static function get_sections($courseid) {
        $db = \block_summary\database_interface::get_instance();
        return $db->get_sections($courseid);
    }

    /**
     * Delete section.
     *
     * @param int $courseid
     * @return void
     * @throws \dml_exception
     */
    public static function delete_section($courseid, $sectionid) {
        $db = \block_summary\database_interface::get_instance();
        $oldposition = $db->get_section_number($sectionid);
        course_delete_section($courseid, $oldposition);
    }

    /**
     * Delete section.
     *
     * @param int $courseid
     * @return bool|int
     * @throws \dml_exception
     */
    public static function add_section($courseid, $name, $position, $visible) {
        $db = \block_summary\database_interface::get_instance();
        return $db->add_section($courseid, $name, $position, $visible);
    }

    /**
     * Delete section.
     *
     * @param int $sectionid
     * @param int $position
     * @return bool
     * @throws \dml_exception
     */
    public static function set_position($sectionid, $position) {
        $db = \block_summary\database_interface::get_instance();
        return $db->set_position_section($sectionid, $position);
    }

    /**
     * Update visibility section.
     *
     * @param int $sectionid
     * @param string $visible
     * @return bool
     * @throws \dml_exception
     */
    public static function set_visibility($sectionid, $visible) {
        $db = \block_summary\database_interface::get_instance();
        return $db->set_visibility_section($sectionid, $visible);
    }

    /**
     * Set section name.
     *
     * @param $sectionid
     * @param $name
     * @return mixed
     * @throws \dml_exception
     */
    public static function set_name($sectionid, $name) {
        $db = \block_summary\database_interface::get_instance();
        return $db->set_name_section($sectionid, $name);
    }

    /**
     * Update summary data.
     *
     * @param int $courseid
     * @param string $sections
     * @return true
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public static function update($courseid, $sections) {
        // Set data.
        $oldsections = self::get_sections($courseid);
        $newsections = json_decode($sections);

        $startupdateposition = 0;

        // Call actions section sby section.
        foreach ($newsections as $key => $newsection) {
            // Set data section.
            $position = $key + 1;
            $sectionid = (int) $newsection->id;

            $newsection->updatevisible = false;

            // Is new section.
            if ($sectionid === -1) {
                $newsection->updatedepth = true;
                if (!$startupdateposition) {
                    // Start move section to this position.
                    $startupdateposition = $position;
                }
                continue;
            }

            // Check if position change.
            if (
                !$startupdateposition &&
                (int) $oldsections[$newsection->id]->section !== $position
            ) {
                // Start move section to this position.
                $startupdateposition = $position;
            }

            // Check visibility.
            if ((int) $oldsections[$newsection->id]->visible !== $newsection->visible) {
                // Change visibility.
                $newsection->updatevisible = true;
            }

            // Check name.
            $newsection->updatename = false;
            if ($oldsections[$newsection->id]->name !== $newsection->name) {
                $newsection->updatename = true;
            }

            // Check depth.
            $olddepth = (int) $oldsections[$newsection->id]->depth;
            $depth = (int) $newsection->depth;
            $newsection->updatedepth = false;
            if ($olddepth !== $depth) {
                $newsection->updatedepth = true;
            }

            unset($oldsections[$sectionid]);
        }

        // Remove remaining sections.
        foreach ($oldsections as $oldsection) {
            self::delete_section($courseid, $oldsection->id);
        }

        // Update sections data.
        self::update_sections($courseid, $newsections, $startupdateposition);

        // Reset course cache.
        rebuild_course_cache($courseid);
        return true;
    }

    /**
     * Update sections with new data to "$newsections" var.
     * - Add new section
     * - Set position (and move other section)
     * - Set visibility
     * - Set name (WIP)
     *
     * @param int $courseid
     * @param \stdClass[] $newsections
     * @param int $startupdateposition
     * @return void
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     */
    public static function update_sections($courseid, $newsections, $startupdateposition) {
        global $DB;

        $course = self::get_course($courseid);

        $transaction = $DB->start_delegated_transaction();

        foreach ($newsections as $key => $newsection) {
            $position = $key + 1;

            if ((int) $newsection->id === -1) {
                $newsection->id = self::add_section($course->id, $newsection->name, -$position, $newsection->visible);
                self::set_depth($course, $newsection->id, $newsection->depth);
                continue;
            }

            if ($startupdateposition !== 0 && $position >= $startupdateposition) {
                self::set_position($newsection->id, -$position);
            }

            if ($newsection->updatevisible) {
                self::set_visibility($newsection->id, $newsection->visible);
            }

            if ($newsection->updatename) {
                self::set_name($newsection->id, $newsection->name);
            }
        }

        foreach ($newsections as $key => $newsection) {
            $position = $key + 1;

            if ($startupdateposition !== 0 && $position >= $startupdateposition) {
                self::set_position($newsection->id, $position);
            }

            if ($newsection->updatedepth) {
                self::set_depth($course, $newsection->id, (int) $newsection->depth);
            }
        }

        $transaction->allow_commit();
    }

    /**
     * Set section depth.
     *
     * @param \stdClass $course
     * @param int $sectionid
     * @param int $depth
     * @return void
     * @throws \dml_exception
     */
    public static function set_depth($course, $sectionid, $depth) {
        $db = \block_summary\database_interface::get_instance();
        $db->set_section_depth($course, $sectionid, $depth);
    }

    /**
     * return summary lock data.
     *
     * @param int $courseid
     * @return false|mixed|\stdClass
     * @throws \dml_exception
     */
    public static function get_lock($courseid) {
        $db = \block_summary\database_interface::get_instance();
        return $db->get_summary_lock($courseid);
    }

    /**
     * Update summary lock time.
     * Before, check if lock is link with user.
     *
     * @param int $courseid
     * @param int $userid
     * @return bool
     * @throws \dml_exception
     */
    public static function update_lock($courseid, $userid) {
        $summarylock = self::get_lock($courseid);

        // Check user link.
        if ($summarylock->userid !== $userid) {
            return false;
        }

        $db = \block_summary\database_interface::get_instance();
        $db->update_summary_lock($courseid);
        return true;
    }

    /**
     * Create new summary lock.
     *
     * @param $courseid
     * @param $userid
     * @return false|mixed|\stdClass
     * @throws \dml_exception
     */
    public static function create_lock($courseid, $userid) {
        $summarylock = self::get_lock($courseid);

        // Check if summary lock exist.
        if ($summarylock) {
            // Check user link.
            if ($summarylock->userid !== $userid) {
                return false;
            }

            return $summarylock;
        }

        $db = \block_summary\database_interface::get_instance();
        $db->create_summary_lock($courseid, $userid);

        return self::get_lock($courseid);
    }

    /**
     * Delete summary lock.
     *
     * @param int $courseid
     * @param int $userid
     * @return bool
     * @throws \dml_exception
     */
    public static function delete_lock($courseid, $userid) {
        $summarylock = self::get_lock($courseid);

        // Check user link.
        if ($summarylock->userid !== $userid) {
            return false;
        }

        $db = \block_summary\database_interface::get_instance();
        $db->delete_summary_lock($courseid);
        return true;
    }

    /**
     * Check user link.
     * If is good, update last time lock user.
     *
     * @param int $courseid
     * @param int $userid
     * @return bool
     * @throws \dml_exception
     */
    public static function check_lock($courseid, $userid) {
        $fiveminutes = 60 * 5;
        $summarylock = self::get_lock($courseid);

        // Check if summary lock exist.
        if (!$summarylock) {
            // Create summary lock.
            self::create_lock($courseid, $userid);
            return true;
        }

        // Check user link.
        if ($summarylock->userid !== $userid) {
            $timelastupdate = time() - $summarylock->lastupdate;

            // Too much inactive time.
            if ($timelastupdate > $fiveminutes) {
                // Send lock to user.
                self::delete_lock($courseid, $summarylock->userid);
                self::create_lock($courseid, $userid);
                return true;
            }

            return false;
        }

        // Update last time lock user.
        self::update_lock($courseid, $userid);
        return true;
    }
}
