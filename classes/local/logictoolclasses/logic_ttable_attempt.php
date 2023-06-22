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
 * A class encapsulating an attempt to solve a logictoolproblembank
 *
 * @copyright  2023 Dan Nessett
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 4.0
 */
class logic_ttable_attempt {
    /** @var stdClass the atomic variables for the problem. */
    protected $atomicvariables;
    /** @var stdClass the logicexpressions string for the problem. */
    protected $problemexpressions;
    /** @var stdClass the array of attempt data */
    protected $attempt_array;

    // Constructor =============================================================
    /**
     * Constructor
     *
     * @param object $problemexpressions.
	 * @param object $problem_id.
     * @param object $problem_bank_record.
     */
    public function __construct($problemexpressions, $problem_id,
    							$problem_bank_record) {
    	global $DB, $CFG;
    	
    	$logicexpressionparts = explode(",", $problemexpressions);
        
		$this->atomicvariables = array_shift($logicexpressionparts);
		$this->problemexpressions = $logicexpressionparts;
        
        $attempt_array = array();
              
        if($problem_bank_record == false){
			// create attempt array and then store the attempt array values in
			// the ttable attempt database.
			
			create_attempt_array($attempt_array, $problemid);
			
        } else {
        	// retrieve the attempt array values from the existing ttable attempt
        	// database.
        
        	load_array_from_database($attempt_array);
        
        }
    }
    
    private function create_attempt_array($attempt_array, $problemid) {
    
	global $CFG;

	require_once("$CFG->dirroot/mod_logic/classes/local/logictoolclasses/compute_correct_ttable_values.php");
    
    	// set up loop that will create rows for each problem expression
    	// with each row in that group keyed on an interpreation of the
    	// atomic variables.
    	
    	$interpretation_length = 2 ** $length;
    	$problemexpressions = $this->problemexpressions;
    	$problemexpressionarray = explode(",", $problemexpressions);

		$TF = array("F", "T");
		$zero_one   = array("0", "1");
		$row = array();
		
		foreach($problemexpressionarray as $problemexpression) {
			
			// Compute the rows corresponding to the problem expression.
		
			for($x = 0; $x < $interpretation_length; $x++) {
			
				// get correct evaluation values for $problemexpression
				
				$correct_table = compute_correct_ttable_values($problemexpression);
				
				$string = str_pad(decbin($x), $length, 0, STR_PAD_LEFT) . PHP_EOL;
    
				// Replace 0 with F and 1 with T
    
				$tfstring = str_replace($zero_one, $TF, $string);
				$row[$x]->problemid =  $problemid;
				$row[$x]->problemexpression = $probemexpression;
				$row[$x]->atomicvariablesvalue = $tfstring;
				$row[$x]->inputvalue = -1;

			
			}
		}
		
    	return;
    
    }
    
    private function load_array_from_database($attempt_array) {
    
    	return;
    
    }
}
