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
 * Database Interface
 *
 * @package    block_summary
 * @copyright  2023 Edunao SAS (contact@edunao.com)
 * @author     remi <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_summary;

defined('MOODLE_INTERNAL') || die();

class database_interface {

    /**
     * @var \moodle_database
     */
    protected $db;

    /**
     * @var self
     */
    protected static $instance;

    public function __construct() {

        global $DB;

        $this->db = $DB;
    }

    /**
     * Create a singleton
     *
     * @return database_interface
     */
    public static function get_instance() {

        if (!(self::$instance instanceof self)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Return all section course data.
     *
     * @param int $courseid
     * @return \stdClass[]
     * @throws \dml_exception
     */
    public function get_sections($courseid) {
        return $this->db->get_records_sql('
            SELECT cs.*, cfo.value as depth
            FROM {course_sections} cs
            LEFT JOIN {course_format_options} cfo ON cfo.sectionid = cs.id
            WHERE cs.section <> 0 AND
                cs.course = :courseid AND
                ((cfo.courseid = :courseid2 AND cfo.name = \'depth\') OR
                cfo.value IS NULL)
            ORDER BY cs.section
        ', [
            'courseid' => $courseid,
            'courseid2' => $courseid,
        ]);
    }

    /**
     * Return section number.
     *
     * @param int $sectionid
     * @return int
     * @throws \dml_exception
     */
    public function get_section_number($sectionid) {
        return (int) $this->db->get_field_sql('
            SELECT section
            FROM {course_sections}
            WHERE id = :sectionid',
            ['sectionid' => $sectionid]
        );
    }

    /**
     * Return last section number course.
     *
     * @param int $courseid
     * @return int
     * @throws \dml_exception
     */
    public function get_last_section_number($courseid) {
        return (int) $this->db->get_field_sql('
            SELECT max(section)
            FROM {course_sections}
            WHERE course = :courseid',
            ['courseid' => $courseid]
        );
    }

    /**
     * Add section to course.
     *
     * @param int $courseid
     * @param string $name
     * @param int $positon
     * @param string $visible
     * @return bool|int
     * @throws \dml_exception
     */
    public function add_section($courseid, $name, $positon, $visible = '1') {
        $cw = new \stdClass();
        $cw->course = $courseid;
        $cw->section = $positon;
        $cw->summary = '';
        $cw->summaryformat = FORMAT_HTML;
        $cw->sequence = '';
        $cw->name = $name;
        $cw->visible = $visible;
        $cw->availability = null;
        $cw->timemodified = time();
        return $this->db->insert_record("course_sections", $cw);
    }

    /**
     * Set field section.
     *
     * @param int $sectionid
     * @param string $name
     * @param mixed $value
     * @return bool
     * @throws \dml_exception
     */
    private function set_field_section($sectionid, $name, $value) {
        return $this->db->set_field('course_sections', $name, $value, ['id' => $sectionid]);
    }

    /**
     * Set position to section.
     *
     * @param int $sectionid
     * @param int $position
     * @return bool
     * @throws \dml_exception
     */
    public function set_position_section($sectionid, $position) {
        return $this->set_field_section($sectionid, 'section', $position);
    }

    /**
     * Set visibility to section.
     *
     * @param int $sectionid
     * @param string $visible
     * @return bool
     * @throws \dml_exception
     */
    public function set_visibility_section($sectionid, $visible) {
        return $this->set_field_section($sectionid, 'visible', $visible);
    }

    /**
     * Set section name by section id.
     *
     * @param int $sectionid
     * @param string $name
     * @return bool
     * @throws \dml_exception
     */
    public function set_name_section($sectionid, $name) {
        return $this->set_field_section($sectionid, 'name', $name);
    }

    /**
     * Get section depth.
     *
     * @param int $courseid
     * @param int $sectionid
     * @return false|\stdClass
     * @throws \dml_exception
     */
    public function get_section_depth($courseid, $sectionid) {
        return $this->db->get_record('course_format_options', [
            'courseid' => $courseid,
            'sectionid' => $sectionid,
            'name' => 'depth',
        ]);
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
    public function set_section_depth($course, $sectionid, $depth) {
        // Check if data exist.
        if ($courseformatoption = $this->get_section_depth($course->id, $sectionid)) {
            if ($depth === 0) {
                // Remove section data if depth equal to 0.
                $this->db->delete_records('course_format_options', ['id' => $courseformatoption->id]);
                return;
            }

            // Update section depth data.
            $courseformatoption->value = $depth;
            $this->db->update_record('course_format_options', $courseformatoption);
            return;
        }

        // No saved data if depth equal to 0.
        if ($depth === 0) {
            return;
        }

        // Create section depth data.
        $courseformatoption = new \stdClass();
        $courseformatoption->courseid = $course->id;
        $courseformatoption->format = $course->format;
        $courseformatoption->sectionid = $sectionid;
        $courseformatoption->name = 'depth';
        $courseformatoption->value = $depth;
        $this->db->insert_record('course_format_options', $courseformatoption);
    }

    /**
     * Get summary lock data.
     *
     * @param int $courseid
     * @return false|mixed|\stdClass
     * @throws \dml_exception
     */
    public function get_summary_lock($courseid) {
        return $this->db->get_record('block_summary_edit_lock', ['courseid' => $courseid]);
    }

    /**
     * Create new summary lock.
     *
     * @param int $courseid
     * @param int $userid
     * @return void
     * @throws \dml_exception
     */
    public function create_summary_lock($courseid, $userid) {
        $summarylock = new \stdClass();
        $summarylock->courseid = $courseid;
        $summarylock->userid = $userid;
        $summarylock->timecreated = time();
        $summarylock->lastupdate = time();
        $this->db->insert_record('block_summary_edit_lock', $summarylock);
    }

    /**
     * Update summary lock with new time.
     *
     * @param int $courseid
     * @return void
     * @throws \dml_exception
     */
    public function update_summary_lock($courseid) {
        $summarylock = $this->get_summary_lock($courseid);
        $summarylock->lastupdate = time();
        $this->db->update_record('block_summary_edit_lock', $summarylock);
    }

    /**
     * Delete summary lock.
     *
     * @param int $courseid
     * @return void
     * @throws \dml_exception
     */
    public function delete_summary_lock($courseid) {
        $this->db->delete_records('block_summary_edit_lock', ['courseid' => $courseid]);
    }
}
