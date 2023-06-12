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
 * @copyright 2008 onwards Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
class logictoolproblembank {
    /** @var stdClass the logictool for the problembank. */
    protected $logictool;
    /** @var stdClass the logicexpressions string for the problembank. */
    protected $logicexpressions;
    /** @var stdClass the course_module. */
    protected $cm;
    /** @var course module id. */
    protected $cmid;
    /** @var context the course. */
    protected $course;

    // Constructor =============================================================
    /**
     * Constructor
     *
     * @param object $logictool from the logic table.
	 * @param object $logicexpressions from the logic table.
     * @param object $cm the course_module object for this quiz.
     * @param object $course the row from the course table for the course we belong to.
     */
    public function __construct($logictool, $logicexpressions, $cm, $course) {
        $this->logictool = $logictool;
        $this->logicexpressions = $logicexpressions;
        $this->cm = $cm;
        $this->cmid = $this->cm->id;
        $this->course = $course;
        
        // burst logicexpressions into its parts, one for each problem. The delimiter
        // is ';'
        
        $problemexpressions = explode(";", $logicexpressions);
        
        // for each array element in $problemexpressions, create a logictoolproblem
        // object.
        
        foreach($problemexpressions as $key => $probemexpression) {
        	$problem[$key] = new logictoolproblem($logictool, $probemexpression);
        }
    }
        
     /**
     * Create a {@link logictoolproblem_attempt} for an attempt at the problembank.
     *
     * @param object $attemptdata row from the logictoolprobembank_attempts table.
     * @return logictoolproblembank_attempt the new logictoolproblem_attempt object.
     */
    public function create_attempt_object($attemptdata) {
        return new logictoolproblembank_attempt($attemptdata, $this->logicaltool,
        										$this->logicexpressons, $this->cm,
        										$this->cmid, $this->course);
    }
}

class logictoolproblem {
    /** @var stdClass the course settings from the database. */
    protected $logictool;
    /** @var stdClass the course_module settings from the database. */
    protected $logictoolexpressiom;

    // Constructor =============================================================
    /**
     * Constructor
     *
     * @param object $logictool from the logic table.
	 * @param object $logicexpressions from the logic table.
     */
    public function __construct($logictool, $logicexpressions) {
        $this->logictool = $logictool;
        $this->logicexpressions = $logicexpressions;
        
        // burst logicexpressions into its parts: 1) the string of atomic variables,
        // 2) an array of logic expressions to which the tool is applied. The delimiter
        // is ','
        
        $logicexpressionparts = explode(",", $logicexpressions);
        
        // put the first array element into $atomicvariables, and the rest of the
        // array variables into the array $logicexpressarray.
        
		$atomicvariables = $logicexpressionparts[0];
		$logicexpressarray = array_shift($logicexpressionparts);
		
		// Now create the appropriate tool object to process the problem
		
		if($logictool == 'truthtable') {
			$problem = new truthtableproblem($atomicvariables, $logicexpressarray);
		} elseif($logictool == 'truthtree') {
			$problem = new truthtreeproblem($atomicvariables, $logicexpressarray);
		} elseif($logictool == 'derivation') {
			$problem = new derivationproblem($atomicvariables, $logicexpressarray);
		} else {
			// throw an exception for unknown logic tool problem.
			throw new Exception('attempt to create a logic tool problem for an unknown logic tool');
		}
		
		// The selected logic tool problem object modifies the appropriate database
		// table with the results of the problem and returns the generated html
		// data to display to the user.
		
		$results = $problem->return_results();
    }
    /**
     * Static function to create a new logictool problem object for a specific user.
     *
     * @param int $logictoolid is the logictool id.
     * @param int $userid is the userid.
     * @return logictool the new logictool object.
     */
    public static function create($logictoolid, $userid) {
        global $DB;

        $logictool = logic_access_manager::load_logictool_settings($logictoolid);
        $course = $DB->get_record('course', array('id' => $logic->course), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('logic', $logic->id, $course->id, false, MUST_EXIST);

        return new logictoolproblem($logictoolid, $cm, $course);
    }
}

class truthtableproblem {
     /** @var stdClass the set of atomic variables for the problem. */
    protected $atomicvariables;
    /** @var stdClass set of logical expressions to evaluate */
    protected $logicexpressionarray;

    // Constructor =============================================================
    /**
     * Constructor
     *
	 * @param object $logicexpressions from the logictoolproblem object.
     */
    public function __construct($atomicvariables, $logicexpressionarray) {
    	$this->atomicvariables = $atomicvariables;
		$this->logicexpressionarray = $logicexpressionarray;
    }
    /**
     * Static function to create a new truthtable problem object for a specific user.
     *
     * @param int $userid is the userid.
     * @return logictool the new logictool object.
     */
    public static function create($userid) {

        $atomicvariables = $this->atomicvariables;
        $logicexpressionarray = $this->logicexpressionarray;

        return new truthtableproblem($atomicvariables, $logicexpressionarray , $userid);
    }
}

class truthtreeproblem {
     /** @var stdClass the set of atomic variables for the problem. */
    protected $atomicvariables;
    /** @var stdClass set of logical expressions to evaluate */
    protected $logicexpressionarray;

    // Constructor =============================================================
    /**
     * Constructor
     *
	 * @param object $logicexpressions from the logictoolproblem object.
     */
    public function __construct($atomicvariables, $logicexpressionarray) {
    	$this->atomicvariables = $atomicvariables;
		$this->logicexpressionarray = $logicexpressionarray;
    }
    /**
     * Static function to create a new truthtable problem object for a specific user.
     *
     * @param int $userid is the userid.
     * @return logictool the new logictool object.
     */
    public static function create($userid) {

        $atomicvariables = $this->atomicvariables;
        $logicexpressionarray = $this->logicexpressionarray;

        return new truthtableproblem($atomicvariables, $logicexpressionarray , $userid);
    }
}

