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
 * Code to keep track of an attempt to solve a truth table problem.
 *
 * @package   mod_logic
 * @copyright 2023 Dan Nessett <dnessett@yahoo.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_logic\local\logictoolclasses;

defined('MOODLE_INTERNAL') || die();

/**
 * A function that returns the attempt array corresponding to a logic ttable attempt
 *
 * @copyright  2023 Dan Nessett
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 4.0
 */
 
function logic_ttable_attempt($problemexpression, $problem_id) {
    	
    	$logicexpressionparts = explode(",", $problemexpression);
        
		$atomicvariables = array_shift($logicexpressionparts);
        foreach ($logicexpressionparts as $key => $problemexpression) {
        	$length = strlen($atomicvariables);
			
			$attempt_array[$key] = create_attempt_array($length, $problem_id,
								 $atomicvariables, $problemexpression);
        }
								 
        return $attempt_array;
        
    }


    function create_attempt_array($length, $problem_id,
    							  $atomicvariables, $problemexpression) {

		require_once(__DIR__ . "/compute_correct_ttable_values.php");
    
    	// set up loop that will create rows for each problem expression
    	// with each row in that group keyed on an interpreation of the
    	// atomic variables.
    	
    	$interpretation_length = 2 ** $length;

		$FT = array("F", "T");
		$zero_one   = array("0", "1");
        $false_true = array("false", "true");
			
		// Compute the rows corresponding to the problem expression.
                    
        $correct_table = compute_correct_ttable_values($atomicvariables,
        												   $problemexpression);
                        
        // Replace 0 with F and 1 with T
		$attempt_array['problemid'] =  $problem_id;
		$attempt_array['problemexpression'] = $problemexpression;
                                
		for($x = 0; $x < $interpretation_length; $x++) {
			
			// get correct evaluation values for $problemexpression
				
			$string = str_pad(decbin($x), $length, 0, STR_PAD_LEFT) . PHP_EOL;
            $tfstring = str_replace($zero_one, $FT, $string);
            $attempt_array['atomicvariablesvalue'] = $tfstring;
            $attempt_array['inputvalue'][$x] = -1;
			$attempt_array['correctvalue'][$x] = str_replace($false_true,
                                    $zero_one, $correct_table->values[$x]);	
		}
		
    return $attempt_array;
    
}