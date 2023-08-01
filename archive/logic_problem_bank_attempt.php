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
 * Code for keeping track of a user's atempt to solve a problem bank.
 *
 * @package   mod_logic
 * @copyright 2023 Dan Nessett <dnessett@yahoo.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_logic\local\logictoolclasses;

defined('MOODLE_INTERNAL') || die();

/**
 * A class used to keep track of an attempt by a student to solve a problem bank.
 *
 * @copyright  2023 Dan Nessett
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 4.0
 */
class logic_problem_bank_attempt {
    /** @var the id of the problem bank associated with this attempt. */
    protected $problembank_id;
    /** @var the id of the user associated with this attempt. */
    protected $user_id;
    /** @var Flag indicating whether this problem bank attempt has been submitted. */
    protected $submitted;

    // Constructor =============================================================
    /**
     * Constructor
     *
     * @param object $logictool from the logic table.
	 * @param object $logicexpressions from the logic table.
     * @param object $cm the course_module object for this logictoolproblembank.
     * @param object $course the row from the course table for the course we belong to.
     */
    public function __construct($logictool, $logicexpressions, $cm_id, $course_id,
    										$problemidstring,  $problem_bank_record) {
    	global $DB;
    	
    	if($problem_bank_record == false) {
            $this->course_id = $course_id;
            $this->cm_id = $cm_id;
            $this->timecreated = time();
            $this->timemodified = null;
	        $this->logictool = $logictool;
	        $this->submitted = false;
        	$this->problemidstring = $problemidstring;
        }
    }
}