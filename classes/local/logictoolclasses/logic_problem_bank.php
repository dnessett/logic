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
 * Code for creating a problem bank.
 *
 * @package   mod_logic
 * @copyright 2023 Dan Nessett <dnessett@yahoo.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_logic\local\logictoolclasses;

defined('MOODLE_INTERNAL') || die();

/**
 * A class that encapsulates the state associated with a problem bank.
 *
 * @copyright  2023 Dan Nessett
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 4.0
 */
class logic_problem_bank {
    /** @var the course id. */
    public $course_id;
    /** @var course module id. */
    public $cm_id;
    /** @var stdClass the logictool for the problembank. */
    public $logictool;
    /** @var time problem bank created. */
    public $timecreated;
    /** @var time problem bank modified. */
    public $timemodified;
    /** @var The string comprising a CSV list of problem ids. */
    public $problemidstring;

    // Constructor =============================================================
    /**
     * Constructor
     *
     * @param object $logictool from the logic table.
	 * @param object $logicexpressions from the logic table.
     * @param object $cm the course_module object for this logictoolproblembank.
     * @param object $course the row from the course table for the course we belong to.
     */
    public function __construct($course_id, $cm_id, $logictool,
                                                      $problemidstring) {
    	
        $this->course_id = $course_id;
        $this->cm_id = $cm_id;
        $this->timecreated = time();
        $this->timemodified = time();
	    $this->logictool = $logictool;
        $this->problemidstring = $problemidstring;
        
        return;
    }
}