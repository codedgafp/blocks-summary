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
 * Summary Block page.
 *
 * @package    block_summary
 * @copyright  2023 Edunao SAS (contact@edunao.com)
 * @author     remi <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/summary/lib.php');

class block_summary extends block_base {

    /**
     * Initialises the block
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_summary');
    }

    /**
     * Are you going to allow multiple instances of each block?
     * If yes, then it is assumed that the block WILL USE per-instance configuration
     *
     * @return boolean
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * If overridden and set to false by the block it will not be hidable when
     * editing is turned on.
     *
     * @return bool
     */
    public function instance_can_be_hidden() {
        return false;
    }

    /**
     * Which page types this block may appear on.
     *
     * The information returned here is processed by the
     * {@link blocks_name_allowed_in_format()} function. Look there if you need
     * to know exactly how this works.
     *
     * Default case: everything except mod and tag.
     *
     * @return array page-type prefix => true/false.
     */
    public function applicable_formats() {
        return ['all' => true, 'my' => false, 'tag' => false];
    }

    /**
     * Delete everything related to this instance if you have been using persistent storage other than the configdata field.
     *
     * @return boolean
     */
    public function instance_delete() {
        global $DB;

        // TODO: A voir si on ne peut pas récupérer le cours sans fonction.
        list($context, $course, $cm) = get_context_info_array($this->context->id);

        if ($course != null) {
            $DB->delete_records('block_summary', ['courseid' => $course->id]);
            $DB->delete_records('block_summary_edit_lock', ['courseid' => $course->id]);
            $DB->delete_records('course_format_options', ['courseid' => $course->id, 'name' => 'depth']);
        }

        return true;
    }

    /**
     * Parent class version of this function simply returns NULL
     * This should be implemented by the derived class to return
     * the content object.
     *
     * @return \stdClass
     */
    public function get_content() {
        // Get instance of page renderer.
        $renderer = $this->page->get_renderer('block_summary');

        // Set block content.
        $this->content = new \stdClass();
        $this->content->text = $renderer->display();

        return $this->content;
    }

    /**
     * Do any additional initialization you may need at the time a new block instance is created
     *
     * @return boolean
     */
    public function instance_create() {
        global $DB, $COURSE;

        $sqlparams = ['blockname' => 'summary', 'parentcontextid' => context_course::instance($COURSE->id)->id];

        $blockinstancerecord = $DB->get_record('block_instances', $sqlparams);

        if ($blockinstancerecord != null) {
            $do = new stdclass();
            $do->pagetypepattern = '*';
            $do->id = $blockinstancerecord->id;

            $DB->update_record('block_instances', $do);

            $this->instance = $DB->get_record('block_instances', $sqlparams);
        }

        return true;
    }
}
