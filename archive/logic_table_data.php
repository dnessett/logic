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
 * Back-end code for handling data about a logic tool problem bank and the current
 * user's attempt.
 *
 * @package   mod_logic
 * @copyright 2023 Dan Nessett <dnessett@yahoo.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_logic\local\logictoolclasses;

defined('MOODLE_INTERNAL') || die();

/**
 * A class that creates or, if they already exist, uses the mod_logic db tables:
 * logic, logic_problem_bank, logic_problem_set, logic_problem, and 
 * logic_<logictool>_attempt to get the data that drives the html for the view.
 *
 * @copyright  2023 Dan Nessett
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 4.0
 */
class logic_table_data {
    /** @var stdClass the mod_logic table id. */
    protected $logic;
    /** @var information about the course. */
    protected $course;
    /** @var stdClass the course_module. */
    protected $cm;
    /** @var course module id. */
    protected $cmid;
    /** @var stdClass the text ldentifier of the logic tool. */
    protected $logictool;
    /** @var stdClass the text name of the course. */
    protected $name;
    /** @var stdClass the time the course module was created. */
    protected $timecreated;
    /** @var stdClass the time the course module was last modified. */
    protected $timemodified;
    /** @var stdClass the introductory text for a problembank. */
    protected $intro;
    /** @var stdClass the logicexpressions string for the problembank. */
    protected $logicexpressions;
    /** @var stdClass the id of the user for the problembank. */
    protected $user_id;
    /** @var The problem array id. */
    protected $table_data;

    // Constructor =============================================================
    /**
     * Constructor
     *
     * @param object $logic - the id of the logic table for mod_logic.
     * @param object $course - the row from the course table for the course we belong to.
     * @param object $cm - the course_module object for this logic problem bank.
     */

    public function __construct($logic, $course, $cm) {
        global $DB, $USER;
        
        require_once(__DIR__ . "/logic_ttable_attempt.php");
        require_once(__DIR__ . "/logic_ttree_attempt.php");
        require_once(__DIR__ . "/logic_derivation_attempt.php");

        $this->logic = $logic;
        $this->course = $course;
        $this->cm = $cm;
        $this->cmid = $this->cm->id;
        
        // get the other logic table values.
    
        $logic_record = $DB->get_records('logic', array('id'=>$logic->id));
        
        if(!$logic_record) {
            // Whoopse. We have an internal coding error.
            $message = 'Internal error found in class logic_tables constructor ' . 
                       'in mod/logic/classses/logictoolclasses/logic_tables.php.';
            throw new coding_exception($message);
        }
        
        // fill in remaining class variables except $table_data, which will hold
        // an array of class instances, one for each table associated with the
        // problem bank.

        $this->logictool = $logic_record[$logic->id]->logictool;
        $this->name = $logic_record[$logic->id]->name;
        $this->timecreated = $logic_record[$logic->id]->timecreated;
        $this->timemodified = strval(time());
        $this->intro = $logic_record[$logic->id]->intro;
        $this->logicexpressions = $logic_record[$logic->id]->logicexpressions;
        $this->user_id = $USER->id;
         
        // Create the $table_data array.

		$this->get_class_instances($logic, $course, $cm);
		
    }        

    protected function get_class_instances($logic, $course, $cm) {
 		global $DB;
 		
		// See if there is a problem_bank_record for this course module instance.
		
		$problem_bank_record = $DB->get_records('logic_problem_bank',
        						array('id'=>$logic->id,
        						'course_id'=>$course->id));
        						
        // If the problem bank record does not exist, create the objects that
        // comprise it, fill in $table_data with them and insert rows into
        // the problem_bank, problem and attempt tables.
        
        if($problem_bank_record == false) {
        
        	// burst logicexpressions into its parts, one for each problem. The delimiter
			// is ';'
        
	        $problemexpressions = explode(";", $this->logicexpressions);
	        
	        // How many problems in this problem bank? Use this to compute
	        // the problemidstring with information from the problem table.
	        
	        $numberofproblems = count($problemexpressions);
	        
	        $problem_next_id = $DB->get_field('logic_problem', 'MAX(id)', array());
	        if($problem_next_id === NULL) {$problem_next_id = 1;}
	        else {$problem_next_id = $problem_next_id + 1;}
	        									
	        $problemidstring = strval($problem_next_id);
	        
	        for ($i = $problem_next_id+1; $i <= $numberofproblems; $i++) {
				$problemidstring = $problemidstring . ',' . strval($i);
			}
        
	        // create problem bank object
        
            $this->table_data['problembank'] = new logic_problem_bank($this->course->id,
            														  $this->cm->id,
            														  $this->logictool,                                                                    
                                                                      $problemidstring);
                                                                      
			// Fill in the problem bank attempt data
                
			$problem_bank_next_id = $DB->get_field('problem_bank', 'MAX(id)', array());
			
			if($problem_bank_next_id === NULL) {$problem_bank_next_id = 1;}
			else {$problem_bank_next_id = $problem_bank_next_id + 1;}
                
			$this->table_data['problembankattempt']['problembankid'] =
                												$problem_bank_next_id;
			$this->table_data['problembankattempt']['userid'] = $this->user_id;
			$this->table_data['problembankattempt']['submitted'] = false;
			
			// Burst logicexpressions into its parts, one for each problem.
			// The delimiter is ';'
	
			$problemstrings = explode(";", $this->logicexpressions);
		
			// For each problem create an instance of the logic_problem object
			// and an attempt object instance.
		
			$problemidarray = str_getcsv($problemidstring);
            $attemptarray = array(array());
            $attemptarrayelement = array(array());
            $FT = array("F", "T");
            $zero_one   = array("0", "1");
            $x = 0;
		
			foreach($problemstrings as $key => $problemexpression) {
                            
            	// Fill in problem array data
                            
            	$problem_id = array_shift($problemidarray);
                $logicexpressionparts = explode(",", $problemexpression);
                                
                $atomicvariables = array_Shift($logicexpressionparts);
                $logicexpressionstring = implode(',',$logicexpressionparts);
                $this->table_data['problemarray'][$key]['problem_id'] = $problem_id;
                $this->table_data['problemarray'][$key]['atomicvariables'] =
                												$atomicvariables;
                $this->table_data['problemarray'][$key]['logicexpressions'] =
                												$logicexpressionstring;		

                // Then fill in logic attempt data
                                
            	switch ($this->logictool) {
					case "truthtable":
						$attemptdata[$key] = logic_ttable_attempt(
                                                    $problemexpression,
                                                    $problem_id,
                                                    $problem_bank_record);
                                                    				
						$attemptarrayflat = array_merge($attemptdata[$key]);
						$length = strlen($atomicvariables);
                        
						foreach($attemptarrayflat as $index => $attemptarrayelement) {
							for($i = $x;
									$i < count($attemptarrayelement['inputvalue'])+$x;
									$i++) {
									
									$problem_bank_attempt_next_id =
											$DB->get_field('problem_bank',
														   'MAX(id)', array());
									if($problem_bank_attempt_next_id === NULL)
										 {$problem_bank_attempt_next_id = 1;}
									else {$problem_bank_attempt_next_id =
													$problem_bank_attempt_next_id + 1;}
														
								$attemptarray[$i]['problembankattemptid'] = 
													$problem_bank_attempt_next_id;
                    			$attemptarray[$i]['problemid'] =
                    							$attemptarrayelement['problemid'];
                    			$attemptarray[$i]['problemexpression'] =
                    							$attemptarrayelement['problemexpression'];
                    			$string = str_pad(decbin($i-$x), $length, 0,
                    											STR_PAD_LEFT) . PHP_EOL;
                    			$attemptarray[$i]['atomicvariablesvalue'] =
                    								str_replace($zero_one, $FT, $string);
                    			$attemptarray[$i]['inputvalue'] =
                    							$attemptarrayelement['inputvalue'][$i-$x];
                    			$attemptarray[$i]['correctvalue'] =
                    						$attemptarrayelement['correctvalue'][$i-$x];
							}
					
                    	$x += count($attemptarrayelement['inputvalue']);
				}
						break;
					case "truthtree":
						$attemptdata[$key] = logic_ttree_attempt(
                                                    $problemexpression,
                                                    $problem_id,
                                                    $problem_bank_record);
						break;
					case "derivation":
						$attemptdata[$key] = logic_deriviation_attempt(
                                                    $problemexpression,
                                                    $problem_id,
                                                    $problem_bank_record);
						break;
					default:
						$message = 'Internal error found in get_record_class_instances ' . 
                                    'in mod/logic/classses/logictoolclasses/logic_tables.php. '
                                     . 'Invalid logictool type';
						throw new coding_exception($message);
            	}

			}
		
			// record the problem and attempt arrays in the table data array.

			$this->table_data['attemptarray'] = $attemptarray;
                        
			// Insert the Problem Bank Object into the logic problem bank table

			try {
				try {
                	$transaction = $DB->start_delegated_transaction();
                	$DB->insert_record('logic_problem_bank', $this->table_data['problembank']);
                	$transaction->allow_commit();
            	} catch (Exception $e) {
            		// Make sure transaction is valid.
            		if (!empty($transaction) && !$transaction->is_disposed()) {
                	$transaction->rollback($e);
            		}
				}
			} catch (Exception $e) {
				// if the rollback fails, throw fatal error exception.
				$message = 'Internal error occured in class logic_tables, method ' .
					   'create records, action insert into logic problem bank table ' .
                       'in mod/logic/classses/local/logictoolclasses/logic_tables.php.';
				throw new coding_exception($message);
			}
			
			// Insert the problem bank attempt object into the logic problem bank attempt
			// table

			try {
				try {
                	$transaction = $DB->start_delegated_transaction();
                	$DB->insert_record('logic_problem_bank_attempt',
                						$this->table_data['problembankattempt']);
                	$transaction->allow_commit();
            	} catch (Exception $e) {
            		// Make sure transaction is valid.
            		if (!empty($transaction) && !$transaction->is_disposed()) {
                	$transaction->rollback($e);
            		}
				}
			} catch (Exception $e) {
				// if the rollback fails, throw fatal error exception.
				$message = 'Internal error occured in class logic_tables, method ' .
					   'create records, action insert into logic problem bank table ' .
                       'in mod/logic/classses/local/logictoolclasses/logic_tables.php.';
				throw new coding_exception($message);
			}
 
            // Insert the problem array data into the logic problem table
                                
            try {
                try {
                	$transaction = $DB->start_delegated_transaction();
                	$DB->insert_records('logic_problem', $this->table_data['problemarray']);
                	$transaction->allow_commit();
                } catch (Exception $e) {
            		// Make sure transaction is valid.
            		if (!empty($transaction) && !$transaction->is_disposed()) {
                	$transaction->rollback($e);
            		}
				}
			} catch (Exception $e) {
				// if the rollback fails, throw fatal error exception.
				$message = 'Internal error occured in class logic_tables, method ' .
					   	'create records, action insert into logic problem table ' .
                        'in mod/logic/classses/local/logictoolclasses/logic_tables.php.';
				throw new coding_exception($message);
			}

            // Insert the problem array data in to the appropriate attempt table
            
			switch ($this->logictool) {
				case "truthtable":
					try {
                		try {
                			$transaction = $DB->start_delegated_transaction();
                			$DB->insert_records('logic_ttable_attempt',
                								$this->table_data['attemptarray']);
                			$transaction->allow_commit();
                		} catch (Exception $e) {
            				// Make sure transaction is valid.
            				if (!empty($transaction) && !$transaction->is_disposed()) {
                			$transaction->rollback($e);
            				}
						}
					} catch (Exception $e) {
						// if the rollback fails, throw fatal error exception.
						$message = 'Internal error occured in class logic_tables, method ' .
					   	'create records, action insert into logic problem table ' .
                        'in mod/logic/classses/local/logictoolclasses/logic_tables.php.';
						throw new coding_exception($message);
				}
					break;
				case "truthtree":
					break;
				case "derivation":
					break;
				default:
					$message = 'Internal error found in get_record_class_instances ' . 
                                'in mod/logic/classses/logictoolclasses/logic_tables.php. ' .
                                'Invalid logictool type';
					throw new coding_exception($message);
            	}
	
		} else {
		 
			// read the data in the problem_bank, problem_bank_attempt, problem and
			// attempt tables in order to create the correspondig objects to store in
			// the table_data array.
		
			$problem_bank_record = $DB->get_records('logic_problem_bank',
													array('course_id' => $this->course->id,
														  'cm_id' => $this->cmid));
														  
			// Do a consistency check on problem bank record data
                
        	if(count($problem_bank_record) != 1
           		or $problem_bank_row->logictool != $this->logictool) {
           		// throw internal coding error exception
           		$message = 'Internal error occured in class logic_tables, method ' .
					   	'_consructor, action retrieve problem bank record ' .
                        'in mod/logic/classses/local/logictoolclasses/logic_tables.php.';
				throw new coding_exception($message);
			}
                
        	// if the consistency check passed, put the record data in the table data
        	// array.
        	
        	$this->table_data['problembank'] = $problem_bank_record ;
                
       		$problemidstring = $problem_bank_row->problemidstring;
       		
            // There is a problem here. $problem_bank_record is indexed
            // by the id of the row returned. That index is unknown
            // at this point. Therefore, I have to get the row without
            // knowing its index. So, I use array_shift.
                        
            $problem_bank_row = array_shift(array_values($problem_bank_record));
       		
       		// Now get the problem bank attempt data
       		
       		$problem_bank_attempt_record = $DB->get_records('logic_problem_bank_attempt',
								array('problembankid' =>
								$this->table_data['problembankattempt']['problembankid'],
								'userid' => $this->user_id));
								
			// record it in table_data
			
			$this->table_data['problembankattempt'] = $problem_bank_attempt_record;
       
       		// now get the problem array data
       
       		$problemidarray = str_getcsv($problemidstring);
       
       		foreach($problemidarray as $key => $problemid) {
				$problemarray[$key] = $DB->get_record('logic_problem',
										array('id' => $problemid));
       		}
       		
       		// Finally, get the attempt table data
       		
       		switch ($this->logictool) {
				case "truthtable":
					foreach($problemidarray as $key => $problemid) {
							$attemptsubarray[$key] = $DB->get_records('logic_ttable_attempt',
							array('problemid' => $problemid));
       				}
       					
       				//flatten $attemptsubarray into a single array
       				
       				$attemptarray = call_user_func_array('array_merge', $attemptsubarray);
       				
					break;
				case "truthtree":
					break;
				case "derivation":
					break;
				default:
					$message = 'Internal error found in _constructor ' . 
                                'in mod/logic/classses/logictoolclasses/logic_tables.php. ' .
                                'Invalid logictool type';
					throw new coding_exception($message);
            }
    	}
    	
    	return;
    }
}