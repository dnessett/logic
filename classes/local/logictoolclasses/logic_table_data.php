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
    /** @var stdClass the problem string array from logicexpressions. */
    protected $problemstrings;
    /** @var stdClass the id of the user for the problembank. */
    protected $user_id;
    /** @var The problem array id. */
    protected $attempt_data;

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
            throw new \coding_exception($message);
        }
        
        // fill in remaining class variables except $attempt_data, which will hold
        // an array of class instances, one for each table associated with the
        // problem bank.

        $this->logictool = $logic_record[$logic->id]->logictool;
        $this->logicexpressions = $logic_record[$logic->id]->logicexpressions;
        $this->problemstrings = explode(";", $this->logicexpressions);
        $this->name = $logic_record[$logic->id]->name;
        $this->timecreated = $logic_record[$logic->id]->timecreated;
        $this->timemodified = strval(time());
        $this->intro = $logic_record[$logic->id]->intro;
        $this->user_id = $USER->id;
         
        // Create the $attempt_data array.

		$this->get_attempt_data($logic, $course, $cm);
		
    }      
      
	/**
	 * Gets the class instances associated with logic tables. If the table entries
	 * do not exist for these instances, it creates them.
	 *
	 * @param object $logic - the id of the logic table for mod_logic.
	 * @param object $course - the row from the course table for the course we belong to.
	 * @param object $cm - the course_module object for this logic problem bank.
	 */
 
    protected function get_attempt_data($logic, $course, $cm) {
 		
 		// Get a problem bank table data.
 		
 		$this->get_problem_bank_data($logic, $course);
 		
         // Get the problem bank attempt table data
            
		$this->get_problem_bank_attempt_data();
		
		// Get the problem table data
		
		$this->get_problem_data();
		
		// Get the logic tool attempt table data
		
		$this->get_logicgtool_attempt_data();

		// The table data member is now filled, return to the caller.
		
    	return;
    }
    
    /**
	 * Get table data associated with the problem bank. If the problem bank record
	 * corresponding to the parameters of the call does not exist, creates it.
	 *
	 * @param object $logic - the id of the logic table for mod_logic.
	 * @param object $course - the row from the course table for the course we belong to.
	 * @param object $cm - the course_module object for this logic problem bank.
	 */
 
    protected function get_problem_bank_data($logic, $course) {
    	global $DB;
    	
    	// See if there is a problem_bank_record for this course module instance.
		
		$problem_bank_record = $DB->get_record('logic_problem_bank',
        						array('id'=>$logic->id,
        						'course_id'=>$course->id));
        						
        // If the problem bank record does not exist, create the object that
        // comprises it, fill in $attempt_data with it and insert rows into
        // the problem_bank tables.
        
        if($problem_bank_record == false) {
	        
	        // How many problems in this problem bank? Use this to compute
	        // the problemidstring.
	        
	        $numberofproblems = count($this->problemstrings);
	        
	        $problem_next_id = $DB->get_field('logic_problem', 'MAX(problemid)', array());
	        if($problem_next_id === NULL) {$problem_next_id = 1;}
	        else {$problem_next_id = $problem_next_id + 1;}
	        									
	        $problemidstring = strval($problem_next_id);
	        
	        for ($i = $problem_next_id+1; $i <= $numberofproblems; $i++) {
				$problemidstring = $problemidstring . ',' . strval($i);
			}
        
	        // create problem bank object
        
            $this->attempt_data['problembank'] = new logic_problem_bank($this->course->id,
            														  $this->cm->id,
            														  $this->logictool,                                                                    
                                                                      $problemidstring);
       	
        	// Insert the problem bank object into the problem bank table
            
            // First create the necessary dataobj
            
            $problembankobj = new \stdClass();
            $problembankobj->course_id = $this->attempt_data['problembank']->course_id;
            $problembankobj->cm_id = $this->attempt_data['problembank']->cm_id;
            $problembankobj->timecreated = $this->attempt_data['problembank']->timecreated;
            $problembankobj->logictool = $this->attempt_data['problembank']->logictool;
            $problembankobj->problemidstring =
            						$this->attempt_data['problembank']->problemidstring;
                
			try {
				try {
                	$transaction = $DB->start_delegated_transaction();
                	$id = $DB->insert_record('logic_problem_bank', $problembankobj);
                	$transaction->allow_commit();
            	} catch (Exception $e) {
            		// Make sure transaction is valid.
            		if (!empty($transaction) && !$transaction->is_disposed()) {
                	$transaction->rollback($e);
            		}
				}
			} catch (Exception $e) {
				// if the rollback fails, throw fatal error exception.
				$message = 'Internal error occured in class logic_tables, function ' .
					   'get_problem_bank_data(), action insert into logic problem bank' .
                       'table in mod/logic/classses/local/logictoolclasses/logic_tables.php.';
				throw new \coding_exception($message);
			}
			         
        	$this->attempt_data['problembank']->id = $id;
	
		} else {
		
		// If the problem bank record exists, fill in the table data from it.
		
		$problemidstring = $problem_bank_record->problemidstring;
		
		$this->attempt_data['problembank']['id'] = $problem_bank_record->id;
		$this->attempt_data['problembank']['course_id'] = $problem_bank_record->course_id;
		$this->attempt_data['problembank']['cm_id'] = $problem_bank_record->cm_id;
		$this->attempt_data['problembank']['timecreated'] = $problem_bank_record->timecreated;
		$this->attempt_data['problembank']['timemodified'] = $problem_bank_record->timemodified;
		$this->attempt_data['problembank']['logictool'] = $problem_bank_record->logictool;
		$this->attempt_data['problembank']['problemidstring'] = $problem_bank_record->problemidstring;
		}
		
		return;
    }
    
    /**
	 * Get table data associated with the problem bank attempt. If the problem bank
	 * attempt record corresponding to the parameters of the call does not exist,
	 * creates it.
	 */
 
    protected function get_problem_bank_attempt_data() {
        global $DB;
    	
    	// See if there is a problem_bank_attempt_record for the problem bank id.
    	// First, get the problem bank id.
    	
    	if(array_key_exists('problembank', $this->attempt_data)) {
			$problem_bank_id = $this->attempt_data['problembank']->id;

		} else {
		
		// Whoopse. There is an internal coding error. Throw a coding exception.
		$message = 'Internal error found in get_problem_bank_attempt_data ' . 
                       'in mod/logic/classses/logictoolclasses/logic_tables.php.';
        throw new \coding_exception($message);
		
		}
		
		// Then get the problem bank attempt record corresponding to the problembankid
		// and user_id.
	
		$problem_bank_attempt_record = $DB->get_record('logic_problem_bank_attempt',
        						array('problembankid'=> $problem_bank_id,
        						'userid'=> $this->user_id));
        						
        // If the problem bank record does not exist, fill in $attempt_data and
        // insert rows into the problem_bank tables.
      
        if($problem_bank_attempt_record == false) {
   	
    		// Fill in the problem bank attempt data. Note: at this point the problem
    		// bank data corresponding to the current processing should exist
    		// in the table data array.
    		                
        	$this->attempt_data['problembankattempt']['problembankid'] = $problem_bank_id;
			$this->attempt_data['problembankattempt']['userid'] = $this->user_id;
			$this->attempt_data['problembankattempt']['submitted'] = false;

 			// Create the problem bank attempt record
        
			try {
				try {
                	$transaction = $DB->start_delegated_transaction();
                	$id = $DB->insert_record('logic_problem_bank_attempt',
                						$this->attempt_data['problembankattempt'],
                						true);
                	$transaction->allow_commit();
            	} catch (Exception $e) {
            		// Make sure transaction is valid.
            		if (!empty($transaction) && !$transaction->is_disposed()) {
                		$transaction->rollback($e);
            		}
				}
			} catch (Exception $e) {
				// if the rollback fails, throw fatal error exception.
				$message = 'Internal error occured in class logic_tables, function ' .
					   'get_problem_bank_attempt_data(), action insert into logic ' .
					   'problem bank attempt table ' .
                       'in mod/logic/classses/local/logictoolclasses/logic_tables.php.';
				throw new \coding_exception($message);
        	}
        	
        	$this->attempt_data['problembankattempt']['id'] = $id;
		
        } else {
        	
        	// if the problem bank record does exist, fill in the table data with
        	// the values from the problem_bank_attempt record.
        	
			$this->attempt_data['problembankattempt']['id'] = 
										$problem_bank_attempt_record->id;
			$this->attempt_data['problembankattempt']['problembankid'] = 
										$problem_bank_attempt_record->problembankid;
			$this->attempt_data['problembankattempt']['userid'] =
										$problem_bank_attempt_record->userid;
			$this->attempt_data['problembankattempt']['submitted'] =
										$problem_bank_attempt_record->submitted;
			}
			
    	return;

    }
    
    /**
	 * Get table data associated with the problems in the problem bank. If the
	 * problem records corresponding to the parameters of the call do not exist,
	 * create them. 
	 */
 
    protected function get_problem_data() {
    	global $DB;
    	
    	// Get an array of the problemstrings in the problem bank set.
    	
    	if(array_key_exists('problembank', $this->attempt_data)) {
    		$problemidstring = $this->attempt_data['problembank']->problemidstring;
			$problemidarray = str_getcsv($problemidstring);
		} else {

		// Whoopse. There is an internal coding error. Throw a coding exception.
		$message = 'Internal error found in get_problem_data ' . 
                       'in mod/logic/classses/logictoolclasses/logic_tables.php.';
        throw new \coding_exception($message);
		
		}

        // Loop over the problem id array, processing each problem.
        
        foreach($problemidarray as $index => $problemid) {
        	
        	// Get the problem record corresponding to the problembankid
			// and user_id.
	
			$problem_record = $DB->get_record('logic_problem',
        						array('problemid' => $problemid));
        							
        	// If the problem bank record does not exist, fill in $attempt_data and
        	// insert rows into the problem_bank tables.
      
        	if($problem_record == false) {
        	
        		// The problem does not exist in the problem table. Set up table data
        		// accordingly and create problem table record
        
        		// Fill in problem array data
                            
        		$logicexpressionparts = explode(",", $this->problemstrings[$index]);
        		$atomicvariables = array_shift($logicexpressionparts);
        		$logicexpressionstring = implode(',',$logicexpressionparts);
        			
        		$this->attempt_data['problemarray'][$index]['problemid'] = $problemid;
        		$this->attempt_data['problemarray'][$index]['atomicvariables'] =
                												$atomicvariables;
        		$this->attempt_data['problemarray'][$index]['logicexpressions'] =
                												$logicexpressionstring;
  		 
       	 		// Insert the problem array data into the logic problem table.
       	 			
       	 		$problemarrayindexobj = new \stdClass();
       	 		$problemarrayindexobj->problemid = $problemid;
       	 		$problemarrayindexobj->atomicvariables = $atomicvariables;
       	 		$problemarrayindexobj->logicexpressions = $logicexpressionstring;
                                
        		try {
            		try {
                		$transaction = $DB->start_delegated_transaction();
                		$DB->insert_record('logic_problem', $problemarrayindexobj);
                		$transaction->allow_commit();
            		} catch (Exception $e) {
            			// Make sure transaction is valid.
            			if (!empty($transaction) && !$transaction->is_disposed()) {
                			$transaction->rollback($e);
            			}
					}
				} catch (Exception $e) {
					// if the rollback fails, throw fatal error exception.
					$message = 'Internal error occured in class logic_tables, ' .
					   	'function get_problem_data(), action insert into logic problem table ' .
                        'in mod/logic/classses/local/logictoolclasses/logic_tables.php.';
					throw new \coding_exception($message);
				}			
			} else {
		
			// The table exists. Fill in the table data from the logic_problem record.
		
			$this->attempt_data['problemarray'][$index] = $problem_record;
					
			}
			
		}
    }
         
    /**
	 * Get table data associated with the logic tool attempt. If the logic tool attempt
	 * records corresponding to the parameters of the call do not exist, create them. 
	 */
 
    protected function get_logicgtool_attempt_data() {
    	global $DB;
    	
    	if(array_key_exists('problembankattempt', $this->attempt_data)) {
    		$problem_bank_id = $this->attempt_data['problembankattempt']['problembankid'];
    		$problem_bank_attempt_id = $this->attempt_data['problembankattempt']['id'];
			
		} else {
		
		// Whoopse. There is an internal coding error. Throw a coding exception.
		$message = 'Internal error found in get_logicgtool_attempt_data - 1st instance ' . 
                       'in mod/logic/classses/logictoolclasses/logic_tables.php.';
        throw new \coding_exception($message);
		
		}
		
		// Get the logic bank record corresponding to the problem bank id
	
		$problem_bank = $DB->get_record('logic_problem_bank',
        						array('id'=> $problem_bank_id));
        						
        if($problem_bank == false) {
        
        	// Whoopse. There is an internal coding error. Throw a coding exception.
			$message = 'Internal error found in get_logicgtool_attempt_data - 2nd instance ' . 
                       'in mod/logic/classses/logictoolclasses/logic_tables.php.';
        	throw new \coding_exception($message);
        
        }
        						
        // Get the problemidstring from the problem bank record.
        
        $problemidstring = $problem_bank->problemidstring;
        $problemidarray = str_getcsv($problemidstring);
        
        switch ($this->logictool) {
			case "truthtable":

				// Loop over the problem ids in the problemidstring
					
				foreach($problemidarray as $key => $problemid) {
							
					// Get the ttable attempt record corresponding to the
					// problembankattemptid and problem id.
	
					$ttable_attempt = $DB->get_records('logic_ttable_attempt',
        						array('problembankattemptid'=> $problem_bank_attempt_id,
        						'problemid'=> $problemid));
        						
        			if($ttable_attempt == false) {
        				
        				// If there is no record corresponding to the truth
        				// table attempt, fill in table data and create it.
        				// Done in get_ttable_attempt().
        					
        				$problemexpression = $this->problemstrings[$key];
						$attemptarrayelement[$key] = $this->get_ttable_attempt(
																$problem_bank_attempt_id,
																$problemid,
																$problemexpression);
        				
        			} else {
        				
        			$attemptarrayelement[$key] = (array) $ttable_attempt;
        				
        			}
				}
					
					// flatten $attemptarrayelement into a single array and store 
       				// the result in table data.
					
					$attemptarray = call_user_func_array('array_merge',
														  $attemptarrayelement); 
					$this->attempt_data['attemptarray'] = $attemptarray;
					
			break;
					
			case "truthtree":

			break;
					
			case "derivation":


			break;
					
			default:
				$message = 'Internal error in get_logicgtool_attempt_data ' . 
                        '  in mod/logic/classses/logictoolclasses/logic_tables.php. '
                        . 'Invalid logictool type';
				throw new \coding_exception($message);
        }
        
        return;
    }
    
    

    /**
	 * Create table data associated with a truth table problem in the problem bank. If the
	 * problem records corresponding to the parameters of the call do not exist,
	 * create them.
	 *
	 * @param object 
	 * @param object 
	 * @param object 
	 */
 
    protected function get_ttable_attempt($problembankattemptid, $problemid,
    															 $problemexpression) {
    	global $DB;
    	
    	$attemptarray = array(array());
        $attemptarrayelement = array(array());
        $FT = array("F", "T");
        $zero_one = array("0", "1");
        $x = 0;
        
		$attemptdata = logic_ttable_attempt($problemexpression, $problemid);
                                                    				
		$attemptarrayflat = array_merge($attemptdata);
		$logicexpressionparts = explode(",", $problemexpression);
        $atomicvariables = array_shift($logicexpressionparts);
		$length = strlen($atomicvariables);
                        
		foreach($attemptarrayflat as $attemptarrayelement) {
			for($i = $x;
				$i < count($attemptarrayelement['inputvalue'])+$x;
				$i++) {
														
					$attemptarray[$i]['problembankattemptid'] = $problembankattemptid;
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
				
		try {
            try {
                $transaction = $DB->start_delegated_transaction();
                $DB->insert_records('logic_ttable_attempt',
                								$attemptarray);
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
				throw new \coding_exception($message);
		}
	return $attemptarray;
    }
}