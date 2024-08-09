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

namespace block_summary\output;

/**
 * Block summary renderer.
 *
 * @package    block_summary
 * @copyright  2023 Edunao SAS (contact@edunao.com)
 * @author     remi <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {
    /**
     * Render sections list
     *
     * @return string
     * @throws \moodle_exception
     */
    public function display() {
        global $COURSE, $CFG;

        $db = \block_summary\database_interface::get_instance();

        $data = new \stdClass();

        // Checks if the page is in edit mode.
        $data->edit = $this->page->user_is_editing() &&
                      has_capability('block/summary:addinstance', \context_course::instance($COURSE->id));

        // Get sections course.
        $sections = $db->get_sections($COURSE->id);

        // Get section data to template.
        $data->sections = block_summary_set_data_section_to_template($sections);

        // Add edit button if user has capability.
        if (has_capability('block/summary:addinstance', \context_course::instance($COURSE->id))) {
            $data->editurl = $CFG->wwwroot . '/blocks/summary/pages/edit.php?id=' . $COURSE->id;
        }

        // Call JS.
        $this->page->requires->js_call_amd('block_summary/summary', 'init');

        // Return termplate.
        return $this->render_from_template('block_summary/sections', $data);
    }
}
