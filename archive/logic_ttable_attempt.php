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
    public $attempt_array;

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
        
        $this->attempt_array = array(array(array()));
        $length = strlen($this->atomicvariables);
              
        if($problem_bank_record == false){
			// create attempt array and then store the attempt array values in
			// the ttable attempt database.
			
			$this->create_attempt_array($this->attempt_array, $length, $problem_id);
			
        } else {
        	// retrieve the attempt array values from the existing ttable attempt
        	// database.
        
        	load_array_from_database($this->attempt_array);
        
        }
    }
    
    private function create_attempt_array(&$attempt_array, $length, $problem_id) {
    
	global $CFG;

	require_once(__DIR__ . "/compute_correct_ttable_values.php");
    
    	// set up loop that will create rows for each problem expression
    	// with each row in that group keyed on an interpreation of the
    	// atomic variables.
    	
    	$interpretation_length = 2 ** $length;
        $atomicvariables = $this->atomicvariables;
    	$problemexpressions = $this->problemexpressions;

		$FT = array("F", "T");
		$zero_one   = array("0", "1");
                $false_true = array("false", "true");
                
		foreach($problemexpressions as $key => $problemexpression) {
			
			// Compute the rows corresponding to the problem expression.
                    
                    	$correct_table = compute_correct_ttable_values($atomicvariables, $problemexpression);
                        
                        // Replace 0 with F and 1 with T
			$tfstring = str_replace($zero_one, $FT, $string);
			$attempt_array[$key]['problemid'] =  $problem_id;
			$attempt_array[$key]['problemexpression'] = $problemexpression;
			$attempt_array[$key]['atomicvariablesvalue'] = $tfstring;
                                
			for($x = 0; $x < $interpretation_length; $x++) {
			
				// get correct evaluation values for $problemexpression
				
				$string = str_pad(decbin($x), $length, 0, STR_PAD_LEFT) . PHP_EOL;
    
                                $attempt_array[$key]['inputvalue'][$x] = -1;
                                $attempt_array[$key]['correctvalue'][$x] =
                                                  (str_replace($false_true,
                                                  $FT, $correct_table->values[$x]) ? "T" : "F");
			
			}
		}
		
  	return;
    
    }
    
    private function load_array_from_database($attempt_array) {
    
    	return;
    
    }
}
