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
 * Block summary edit page.
 *
 * @package    block_summary
 * @copyright  2023 Edunao SAS (contact@edunao.com)
 * @author     remi <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->dirroot . '/blocks/summary/lib.php');
require_once($CFG->dirroot . '/blocks/summary/api/summary.php');

require_login();

$courseid = required_param('id', PARAM_INT);
$course = get_course($courseid);
$coursecontext = \context_course::instance($courseid);
$db = \block_summary\database_interface::get_instance();

// Check if the user can view the library.
if (!has_capability('block/summary:addinstance', $coursecontext)) {
    throw new \moodle_exception('summaryeditnotaccessible', 'block_summary');
}

$title = get_string('editsummarytitle', 'block_summary');
$url = new moodle_url('/blocks/summary/pages/edit.php', ['id' => $courseid]);

// Settings first element page.
$PAGE->set_url($url);
$PAGE->set_course($course);
$PAGE->set_title($title);
$PAGE->set_pagelayout('standard');

// Set navbar.
$PAGE->navbar->add($title, $url);

$params = new \stdClass();

// Call renderer.
$renderer = $PAGE->get_renderer('block_summary', 'edit');

// Set JS plugins.
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');

// Set JS string.
$PAGE->requires->strings_for_js([
    'deletedialogcontent',
    'deletedialogtitle',
    'deletedialogbutton',
    'deletedialogbuttoncancel',
    'newsectionname',
    'valid',
    'modifiedsummary',
    'impossibleedittitle',
    'impossibleeditcontent',
], 'block_summary');

// Call edit summary JS.
$params->courseid = $courseid;
$params->url = $url->out(false);
$params->lastnumbersection = $db->get_last_section_number($courseid) + 1;
$params->lock = !\block_summary\summary_api::check_lock($courseid, $USER->id);

$PAGE->requires->js_call_amd('block_summary/edit_summary', 'init', ['params' => $params]);

// Setting header page.
$PAGE->set_heading($title);
echo $OUTPUT->header();
echo $OUTPUT->skip_link_target();

// Displays renderer content.
echo $renderer->display();

// Display footer.
echo $OUTPUT->footer();
