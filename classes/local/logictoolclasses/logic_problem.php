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
 * A class 
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
    public function __construct($logicexpressions, problem_id,
    							$problem_bank_record) {
        global $DB;
    
		if($problem_bank_record ==  false) {
    	
    		// There is no problem bank record for the corresponding problem bank.
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
			
			// fill out the problem data object and insert it into the logic_problem
			// table.
			
			$problem_dataobj->atomicvariables = $this->atomicvariables;
			$problem_dataobj->logicexpressions = $this->logicexpressionarray;
			
			try {
				try {
                    $transaction = $DB->start_delegated_transaction();
                    $DB->insert_record('logic_problem', $problem_dataobj);
                    $transaction->allow_commit();
                } catch (Exception $e) {
                    // Make sure transaction is valid.
                    if (!empty($transaction) && !$transaction->is_disposed()) {
                        $transaction->rollback($e);
                    }
                                    }
                } catch (Exception $e) {
					// if the rollback fails, throw fatal error exception.
					$message = 'Internal error occured in class logic_problem, method ' .
						'constructor, action insert into logic problem table ' .
						'in mod/logic/classses/local/logictoolclasses/logic_problem.php.';
					throw new coding_exception($message);
                }

		} else {
		
			// Use the data accessible through the problem_bank_record to create the
			// logic_problem class
		
		}
		
		return;
    }
}