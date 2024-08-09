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
 * Edit summary page renderer.
 *
 * @package    block_summary
 * @copyright  2023 Edunao SAS (contact@edunao.com)
 * @author     remi <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_renderer extends \plugin_renderer_base {
    /**
     * Render sections list
     *
     * @return string
     * @throws \moodle_exception
     */
    public function display() {
        global $COURSE;

        $db = \block_summary\database_interface::get_instance();

        $data = new \stdClass();

        // Get sections course.
        $sections = $db->get_sections($COURSE->id);

        // Set section course data for template.
        $data->sections = [];
        foreach ($sections as $section) {
            $name = $section->name ?? '(' . get_string('sectionname', "format_$COURSE->format") . " " . $section->section . ')';
            $depth = (int) $section->depth;
            $data->sections[] = [
                'id' => $section->id,
                'name' => $name,
                'hide' => $section->visible === '1',
                'depth' => $depth,
                'margindepth' => 30 * $depth,
            ];
        }

        // Return template.
        return $this->render_from_template('block_summary/edit', $data);
    }
}
