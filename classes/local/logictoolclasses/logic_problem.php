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
 * Logic to create the state used to represent a problem in a problem bank.
 *
 * @package   mod_logic
 * @copyright 2023 Dan Nessett <dnessett@yahoo.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_logic\local\logictoolclasses;

defined('MOODLE_INTERNAL') || die();

/**
 * A class holding the state that represents a problem.
 *
 * @copyright  2023 Dan Nessett
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 4.0
 */

class logic_problem {
    /** @var stdClass the atomic variables of the problem */
    protected $atomicvariables;
    /** @var stdClass the set of logic expressions seperated by ','. */
    protected $logicexpressionarray;

    // Constructor =============================================================
    /**
     * Constructor
     *
     * @param object $logictool from the logic table.
	 * @param object $logicexpressions from the logic table.
     */
    public function __construct($logicexpressions, $problem_id,
    							$problem_bank_record) {
    
		// Create rows in the logic_problem table for each problem in
    	// $logicexpressions
    		
	    $this->logicexpressions = $logicexpressions;
        
	    // Burst logicexpressions into its parts: 1) the string of atomic variables,
	    // 2) an array of logic expressions to which the tool is applied.
	    // The delimiter is ','
			
		// Each element of the array is a logic expression to evaluate for a
		// problem. There are generally more than one, since the full expression
		// is broken into sub-expressions to evaluate. For example, the final
		// logic expression  might be x⊕y→(x⋀z). It has two subexpressions
		// x⊕y and x⋀z. Evaluating the two sub expressions allows the student
		// to gradually evaluate the final expression. So, the string
		// would be "xyz,x⊕y,x⋀z,x⊕y→(x⋀z)".
        
	    // Put the first array element into $atomicvariables, and the rest of the
	    // array variables into the array $logicexpressarray.
	                
		$logicexpressionparts = explode(",", $logicexpressions);
        
		$this->atomicvariables = array_shift($logicexpressionparts);
		$this->logicexpressionarray = $logicexpressionparts;
			
		return;
    }
}