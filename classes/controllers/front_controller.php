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
 * Abstract controller class
 *
 * @package    block_summary
 * @copyright  2020 Edunao SAS (contact@edunao.com)
 * @author     remi <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_summary;

/**
 * Class front_controller
 */
class front_controller {

    /**
     * @var array|null
     */
    protected $params = [];

    /**
     * @var callable
     */
    protected $controller;

    /**
     * @var callable
     */
    protected $action;

    /**
     * front_controller constructor.
     *
     * @param null $options
     * @throws \ReflectionException
     * @throws \moodle_exception
     */
    public function __construct($options = null) {

        if (!empty($options)) {
            $this->params = $options;
        } else {
            $this->set_params();
        }

        if (isset($this->params['controller'])) {
            $this->set_controller($this->params['controller']);
        }

        if (isset($this->params['action'])) {
            $this->set_action($this->params['action']);
        }

    }

    /**
     * Set controller
     *
     * @param string $controller
     * @return $this
     * @throws \moodle_exception
     */
    public function set_controller($controller) {
        global $CFG;

        $controllerurl = $CFG->dirroot . '/blocks/summary/classes/controllers/' . $controller .
                         '_controller.php';

        if (!file_exists($controllerurl)) {
            throw new \moodle_exception('Controller file not found : ' . $controllerurl);
        }

        require_once($controllerurl);

        $controller = strtolower($controller) . "_controller";

        if (!class_exists('block_summary\\' . $controller)) {
            throw new \InvalidArgumentException("The controller '$controller' has not been defined.");
        }

        $this->controller = $controller;

        return $this;
    }

    /**
     * Set action to call
     *
     * @param string $action
     * @return $this
     * @throws \ReflectionException
     */
    public function set_action($action) {

        $reflector = new \ReflectionClass('block_summary\\' . $this->controller);

        if (!$reflector->hasMethod($action)) {
            throw new \InvalidArgumentException(
                "The controller action '$action' is undefined fot the controller '" . 'block_summary\\' . $this->controller .
                "'.");
        }

        $this->action = $action;

        return $this;

    }

    /**
     * Set params from $_GET and $_POST
     */
    public function set_params() {
        $get = filter_input_array(INPUT_GET);
        $post = filter_input_array(INPUT_POST);
        $this->params = array_merge((array) $get, (array) $post);
    }

    /**
     * Execute the controller action
     */
    public function execute() {
        $class = 'block_summary\\' . $this->controller;
        $controller = new $class($this->params);

        return $controller->execute();
    }
}
