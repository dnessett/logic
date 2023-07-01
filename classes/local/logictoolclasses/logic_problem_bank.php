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
 * Back-end code for handling data about logic tool problems and the current
 * user's attempt.
 *
 * @package   mod_quiz
 * @copyright 2023 Dan Nessett <dnessett@yahoo.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_logic\local\logictoolclasses;

defined('MOODLE_INTERNAL') || die();

/**
 * A class encapsulating a set of logictool evaluation problems each working on
 * a set of logic expressions and making the information available to scripts 
 * like view.php.
 *
 * @copyright  2023 Dan Nessett
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 4.0
 */
class logic_problem_bank {
    /** @var The user associated with the problem bank. */
    protected $user_id;
    /** @var the course id. */
    protected $course_id;
    /** @var course module id. */
    protected $cm_id;
    /** @var time problem bank created. */
    protected $timecreated;
    /** @var time problem bank modified. */
    protected $timemodified;
    /** @var stdClass the logictool for the problembank. */
    protected $logictool;
    /** @var The string comprising a CSV list of problem ids. */
    public $problemidstring;
    /** @var Flag indicating whether this problem bank has been submitted. */
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
    public function __construct($logictool, $logicexpressions, $cm_id, $course_id, $user_id,
    										$problemidstring,  $problem_bank_record) {
    	global $DB;
    	
    	if($problem_bank_record == false) {
        	$this->user_id = $user_id;
            $this->course_id = $course_id;
            $this->cm_id = $cm_id;
            $this->timecreated = time();
            $this->timemodified = null;
	        $this->logictool = $logictool;
	        $this->submitted = false;
        	$this->problemidstring = $problemidstring;
        
        } else {
        
        	// get problem bank data from $problem_bank_record
        
        }
    }
}