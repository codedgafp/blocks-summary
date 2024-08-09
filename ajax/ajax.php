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
 * Ajax request dispatcher
 *
 * @package    block_summary
 * @copyright  2020 Edunao SAS (contact@edunao.com)
 * @author     Rémi Colet <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/blocks/summary/classes/controllers/front_controller.php');

require_login();

// Redirection to the login page if the user does not login.
if (!isloggedin()) {
    redirect($CFG->wwwroot . '/login/index.php');
}

// Settings params.
$format = optional_param('format', 'html', PARAM_TEXT);

// Call front controller.
$frontcontroller = new \block_summary\front_controller();

// Call the controller method, choose the format and print the result.
if (strtolower($format) == 'json') {
    echo json_encode($frontcontroller->execute());
} else {
    echo $frontcontroller->execute();
}
