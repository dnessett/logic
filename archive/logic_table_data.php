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
        
        $this->logic = $logic;
        $this->course = $course;
        $this->cm = $cm;
        $this->cmid = $this->cm->id;
        
        // get the other logic table values.
    
        $logic_record = $DB->get_records('logic', array('id'=>$logic->id));
        
        if(!$logic_record) {
            // whoopse. We have an internal coding error.
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
         
        // Create the $table_data array. If this is the first time
        // the problem bank is used, fill in the appropriate tables.

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
        
	        // create problem bank object
        
            $this->table_data['problembank'] = new logic_problem_bank($this->logictool,
                                                                      $this->logicexpressions,
                                                                      $this->cm,
                                                                      $this->course,
                                                                      $this->user_id,
                                                                      $problem_bank_record);
			
			// Burst logicexpressions into its parts, one for each problem.
			// The delimiter is ';'
	
			$problemstrings = explode(";", $this->logicexpressions);
		
			// For each problem create an instance of the logic_problem object
			// and an attempt object instance.
		
			$problemarray = array();
			$problemidstring = ($this->table_data['problembank'])->problemidstring;
			$problemidarray = str_getcsv($problemidstring);
                        $attemptarray = array();
                        $attemptarrayelement = array(array());
                        $data = array();
		
			foreach($problemstrings as $key => $problemexpression) {
			
				$problem_id = array_shift($problemidarray);
				
				switch ($this->logictool) {
					case "truthtable":
						$attemptdata[$key] = new logic_ttable_attempt(
                                                            $problemexpression,
                                                            $problem_id,
                                                            $problem_bank_record);
						break;
					case "truthtree":
						$attemptdata[$key] = new logic_ttree_attempt(
                                                            $problemexpression,
                                                            $problem_id,
                                                            $problem_bank_record);
						break;
					case "derivation":
						$attemptdata[$key] = new logic_deriviation_attempt(
                                                            $problemexpression,
                                                            $problem_id,
                                                            $problem_bank_record);
						break;
					default:
						$message = 'Internal error found in get_record_class_instances ' . 
									'in mod/logic/classses/logictoolclasses/logic_tables.php. ' .
									'Invalid logictool type';
						throw new coding_exception($message);
				}
                                
                                $attemptarrayelement[$key] = array_merge($attemptdata[$key]->attempt_array);
                                $attemptarrayinput[$key] = array_merge($attemptarrayelement[0][$key]['inputvalue']);
                                $attemptarraycorrect[$key] = array_merge($attemptarrayelement[0][$key]['correctvalue']);                               
                                
			}
                        
                        $attemptarray['input'][$key] = call_user_func_array('array_merge', $attemptarrayinput);
                        $attemptarray['correct'][$key] = call_user_func_array('array_merge', $attemptarraycorrect);
		
			// record the problem and attempt arrays in the table data array.
		
			$this->table_data['problemarray'] = $problemarray;
			$this->table_data['attemptarray'] = $attemptarray;
			        
			// Fill out the problem data bank record.
	
            $problem_bank_dataobj = new \stdClass();
            $problem_bank_dataobj->course_id = $this->course->id;
			$problem_bank_dataobj->cmid = $this->cmid;
			$problem_bank_dataobj->timecreated = strval(time());
			$problem_bank_dataobj->userid = $this->user_id;
			$problem_bank_dataobj->logictool = $this->logictool;
			$problem_bank_dataobj->problemidstring = $problem_id;
			$this->table_data['problembank']->$problemidstring;
			$problem_bank_dataobj->submitted = false;

	
			// And insert the required data into the logic problem bank table.
        
			try {
				try {
                	$transaction = $DB->start_delegated_transaction();
                	$DB->insert_record('logic_problem_bank', $problem_bank_dataobj);
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
    	
			return;
	
		} else {
		 
		// read the data in the problem_bank, problem and attempt tables in order
		// create the correspondig objects to store in the table_data array.
		
		// $problem_bank_id = ($DB->get_field('logic_problem_bank', 'MAX(id)', array())) + 1;
		
		}
	}
}