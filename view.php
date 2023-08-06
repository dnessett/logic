<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Creates the tables for a mod_logic problem bank and prints the attempts to solve it.
 *
 * @package     mod_logic
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/course/format/lib.php');

use \mod_logic\local\logictoolclasses\logic_table_data;

global $SESSION;

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or ...
$l = optional_param('l',  0, PARAM_INT);  // Module instance ID.

if ($id) {
	if (!$cm = get_coursemodule_from_id('logic', $id)) {
		print_error('invalidcoursemodule');
	} else {
	$logic = $DB->get_record('logic', array('id'=>$cm->instance), '*', MUST_EXIST);
	}
	if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
		print_error('coursemisconf');
	}
} else {
	if (!$logic = $DB->get_record('logic', array('id' => $l))) {
		print_error('invalidmoduleid', 'logic');
	}
	if (!$course = $DB->get_record('course', array('id' => $logic->course))) {
		print_error('invalidcourseid');
	}
	if (!$cm = get_coursemodule_from_instance("logic", $logic->id, $course->id)) {
		print_error('invalidcoursemodule');
	}
}

// Set page URL

$PAGE->set_url('/mod/logic/view.php', array('id' => $cm->id));

// Check login and get context.

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/logic:view', $context);

// Create $percentage so grade can be calculated automatically and displayed
// on the result page of a Submit request.

$percentage = 0;

if(!empty($_POST)) {

	// view.php entered as the result of a $_POST request.
	
    global $SESSION;

	$table_data = $SESSION->table_data;
	
	$display_results = process_post_data($table_data);

	if($display_results == 'SaveAndExit') {
	
		// go back to course page
		$url = new moodle_url('/course/view.php', array('id' => $course->id));
		redirect($url);
		return;      
	} elseif($display_results == 'Submit') {
	
	// Mark this problem as submitted
	
	$table_data->attempt_data['problembankattempt']['submitted'] = true;
	
	if (!$DB->set_field('logic_problem_bank_attempt',
		'submitted', true,
		array('id' => $table_data->attempt_data['problembankattempt']['id']))) {

		$message = 'Internal error #2 found in change_truthtable_data ' . 
					   'in mod/logic/view.php';
		throw new \coding_exception($message);
	}	
	
	// Get percentage of correct answers
	
	$percentage = compute_percentage_of_right_answers($table_data);
	
	// Report $percentage using the grade API. For truth table problem banks,
	// $percentage will be printed at the end of the html output when a
	// Submit post is processed.
	
	if($table_data->practice == false) {
	
		$grade = new stdClass();
		$grade->userid   = $table_data->user_id;
		$grade->rawgrade = $percentage;
	
		logic_grade_item_update($logic, $grade);

		}
	}
	
} else {

	// view.php entered as the result of a direct call by the user.
	
	// Create or use the logic, problem bank, problem set, problem and attempt db tables.
	// Return an instance of logic_table_data, which encapsulates the classes associated
	// with the problem bank tables. Put a lock (using flock) around the construction
	// of the logic table data classs to ensure only one process can create the problem
	// and problem bank table rows, since these tables are shared between users. If this
	// lock was not present two processes could try to create those tables concurrently,
	// thereby potentially corrupting the database.

	$lock_pointer = logic_lock();
	$table_data = new logic_table_data($logic, $course, $cm, $lock_pointer);
	logic_unlock($lock_pointer);
	
	// Has the problem already been submitted? If so, set $display_results to
	// 'FinshedProblem' so that only the prevous results are displayed.
	
	if($table_data->attempt_data['problembankattempt']['submitted'] == true) {
		$display_results = 'FinshedProblem';
		
		// Compute percentage result for display on results page
		
		$percentage = compute_percentage_of_right_answers($table_data);
		
	} else {

	// Set up a moodle session to hold the table_data class instance between calls to
	// view.php by the attempt form processing code. Using moodle $SESSION rather than
	// PHP $_SESSION, since the former is per user, while the latter is not.

	$SESSION->table_data = $table_data;
	$display_results = 'InitialForm';
	}
}

// Trigger course_module_viewed event and completion.

logic_view($logic, $course, $cm, $context);

// Output the html for the page according to the reason why entering view.php

$title = $course->shortname . ': ' . format_string($logic->name);
$PAGE->set_title($title);
$PAGE->set_heading(format_string($course->fullname));

// Get an instance of the output class.

$output = $PAGE->get_renderer('mod_logic');

// Create the HTML form

$htmlstring = createhtml($table_data, $display_results, $percentage);

// Use the table_data_data instance to output the HTML for the view page.

outputhtml($htmlstring, $output, $table_data);

return;

function outputhtml($htmlstring, $output, $table_data) {

echo $output->header();

if($table_data->practice == false) {

	$pagetitle = "Problems (assignment)";
	
} else {

	$pagetitle = "Problems (practice)";
	
}

echo $output->heading($pagetitle);

echo $htmlstring;

echo $output->footer();

return;

}

function process_post_data($table_data) {

	// Process the $_POST data. Whatever value of $_POST, change $table_data to reflect
	// the input from the form. If the $_POST is for the "Submit" button, record the
	// results of the attempt and terminate the attempt. Otherwise, if the $_POST is
	// for the "SaveAndExit button, process the $_POST data and return.
	
	// What logic tool are we processing?
		
	switch ($table_data->logictool) {
		case "truthtable":
	
			change_truthtable_data($table_data);
	
		break;
	
		case "truthtree":
	
			change_truthtree_data($table_data);
		
		break;
			
		case "derivation":
	
			change_derivation_data($table_data);

		break;
			
		default:
			$message = 'Internal error in process_post_data in ' .
				'mod/logic/view.php. Invalid logictool type';
			throw new \coding_exception($message);
	}

	if ($_POST['action'] == 'SaveAndExit') {
	
		return 'SaveAndExit';

	} elseif ($_POST['action'] == 'Submit') {

		return 'Submit';

	} else {

		// Whoopse. There is an internal coding error. Throw a coding exception.
		$message = 'Internal error found in process_post_data ' . 
					   'in mod/logic/view.php';
		throw new \coding_exception($message);
	}
}

function change_truthtable_data($table_data) {
    
    global $DB;

	// Process the SaveAndExit or Submit request. First, update the table_data input
	// values from the $_POST data.
	
	update_table_data_input_values_from_POST($table_data->attempt_data['attemptarray']);
	
	// Then get the logic ttable attempt table rows pertinent to this problem bank
	// attempt. This allows us to modify the input value field in those rows based on
	// their id field.
	
	if (!$problem_attempt_records =
		$DB->get_records('logic_ttable_attempt',
		array('problembankattemptid' =>
		$table_data->attempt_data['problembankattempt']['id']))) {
			$message = 'Internal error #1 found in change_truthtable_data ' . 
                       'in mod/logic/view.php';
        	throw new \coding_exception($message);
	}
	
	foreach($problem_attempt_records as $problem_attempt_record) {
            
            // for each record returned by the above, set the input field in that
            // record to the value returned by the $_POST
            
            $problem_attempt_record_id = $problem_attempt_record->id;
            
            $select_name = $problem_attempt_record->atomicvariablesvalue . '-' .
                           $problem_attempt_record->problemid . '-' .
                           $problem_attempt_record->subproblemid;
            
            // Strip newline from $select_name
            
            $select_name = preg_replace('~[\r\n]+~', '', $select_name);
            
            // set inputvalue in both $table_data and logic_ttable_attempt table
            // to value in select field
            
			if (!$DB->set_field('logic_ttable_attempt',
				'inputvalue',
				$_POST[$select_name],
				array('id' => $problem_attempt_record_id))) {
		
					$message = 'Internal error #2 found in change_truthtable_data ' . 
								   'in mod/logic/view.php';
					throw new \coding_exception($message);
			}	
        }
	
	return false;

}

function update_table_data_input_values_from_POST(&$attempt_array) {

	for ($i=0; $i<count($attempt_array); $i++) {

		$interpretation = $attempt_array[$i]->atomicvariablesvalue;
		
        // Strip whitespace from $interpretation
            
        $interpretation = preg_replace('~[\r\n]+~', '', $interpretation);
        
		$problem_id = $attempt_array[$i]->problemid;
		$subproblem_id = $attempt_array[$i]->subproblemid;
		$select_tag_name = $interpretation . '-' . $problem_id . '-' . $subproblem_id;
                
        // Strip newline from $select_name
            
        $select_name = preg_replace('~[\r\n]+~', '', $select_tag_name);
        
		$attempt_array[$i]->inputvalue = $_POST[$select_tag_name];
	}
	return;
}

function change_truthtree_data($table_data) {

	// Process the SaveAndExit request.

	return;
}

function change_derivation_data($table_data) {

	// Process the SaveAndExit request.

	return;
}

function compute_percentage_of_right_answers($table_data) {

	// If this is a truth table problem, compute the percentage of answers
	// that were incorrect in order to create a grade.
	
	$errors = 0;		
	$answers = count($table_data->attempt_data['attemptarray']);
	if($table_data->logictool == "truthtable") {
		for($i=0; $i<$answers; $i++) {
			if($table_data->attempt_data['attemptarray'][$i]->inputvalue !=
			   $table_data->attempt_data['attemptarray'][$i]->correctvalue) {
					$errors += 1;
			}
		}	
	}
	
	$percentage = (($answers-$errors)/$answers) * 100;
	return $percentage;

}

function createhtml($table_data, $display_results, $percentage) {

	// Create the html to display on the view page. If $display_results == 'InitialForm',
	// generate the form for the problem set. If $display_results == 'Submit' or $display_results == 'Submit', generate
	// the html for the result of the problem set (i.e., the result of the submit
	// request.) In either case return the html string to the caller.

	// Determine which logic tool to use.

	switch ($table_data->logictool) {
			case "truthtable":
			
				return create_html_for_truthtable($table_data, $display_results,
																		$percentage);
			
			break;
			
			case "truthtree":
			
				return create_html_for_truthtree($table_data, $display_results);
				
			break;
					
			case "derivation":
			
				return create_html_for_derivation($table_data, $display_results);

			break;
					
			default:
				$message = 'Internal error in create_html in ' .
					'mod/logic/classses/logictoolclasses/view.php. Invalid logictool type';
				throw new \coding_exception($message);
	}
}

function create_html_for_truthtable($table_data, $display_results, $percentage) {

	// create the html for a truthtable problem set.
	
	$html_begining = '<!DOCTYPE html>
<html>
	<head>
		<style>
		.table {
		  border: 5px solid;
		}

		.table tr td {
		 padding-top: 15px;
		}

		.td {
		  border: 5px solid;
		}

		.th {
		  border: 5px solid;
		}

		h3 {
		  padding: 20px;
		  font-weight: normal;
		}

		input[type=submit] {
		  background-color: #04AA6D;
		  padding: 8px 16px;
		}
		</style>
	</head>
    <body>
        <form method="post">';

	$html_problem_ending = '
				</table>
			</div>';
			
	if($display_results == 'InitialForm') {
		$html_ending = '
			<div class="col-2">
				<table class="table" style="border: none;">
					<tr>
						<td style="border: none;"><input type="submit" name="action"
																value="SaveAndExit"/></td>
					</tr>
					<tr>
						<td style="border: none;"><input type="submit" name="action"
									value="Submit" style = "background-color: #fa1f0f;"/></td>
					</tr>
				</table>
			</div>
    	</form>
    </body>
</html>';
	} elseif ($display_results == 'Submit' or $display_results == 'FinshedProblem') {
		$html_ending = '
			<div class="col-2">
				<br>
				<br>
				<p style="color:blue;font-size:24px;"> Score: ' . 
							number_format((float)$percentage, 1, '.', '') . '%</p>
			</div>
    	</form>
    </body>
</html>';
	} else {
		$message = 'Internal error found in create_html_for_truthtable ' . 
								   'in mod/logic/view.php';
		throw new \coding_exception($message);
	}

	$html_middle_start = '
					<thead>
						<tr>';
	
	$html_middle_end = '
						</tr>
					</thead>';
					
	// Set up the overall document html
	
	$html = $html_begining;
	$problemarray = $table_data->problemstrings;
	
	// Get the array of select data names.
	
	$select_tag_name_array = generate_select_tag_name_array($table_data);
	
    $offset = 0;
	
	foreach($problemarray as $index => $problemstring) {
	
		// burst the problemstring
	
		$logicexpressionparts = explode(",", $table_data->problemstrings[$index]);
		$atomicvariables = array_shift($logicexpressionparts);

		// Fill in the problem number in html
		
		$number_of_subproblems = count($logicexpressionparts);
		$length = strlen($atomicvariables);
		$lines_per_subproblem = 2 ** $length;
		$lines_per_problem = $number_of_subproblems * $lines_per_subproblem;
	
		// Fill in the problem number in html
		
		$problem_number = $index + 1;
		
		$html_problem = '
		<h3>Problem ' . $problem_number . '</h3>
			<div class="col-2 text-center">
				<table class="table table-striped">';

		$html_header = '
						<th class="col text-center">' . $atomicvariables . '</th>';
		foreach($logicexpressionparts as $logicexpression) {
		
			$html_header = $html_header . '
						<th class="col text-center">' . $logicexpression . '</th>';
		
		}
		
		// create html for truthtable form
			
		$html_body = '
					<tbody>';
					
		$FT = array((string) "F", (string) "T");
		$zero_one   = array("0", "1");
        $false_true = array((string) "false", (string) "true");
			
		for($x = 0; $x < $lines_per_subproblem ; $x++) {
		
			$select_name_string =  $select_tag_name_array[$offset+($x*$number_of_subproblems)];
			$select_name_string_parts =  explode("-", $select_name_string);
			$interpretation = $select_name_string_parts[0];
			
			// Strip the newline from $interpretation
			
			$interpretation = preg_replace('~[\r\n]+~', '', $interpretation);
			
			$html_body = $html_body . '
					<tr>' . '
                        <td class="text-center">' . $interpretation . '</td>';
                        
			for($i = 0; $i < $number_of_subproblems; $i++) {
				if($display_results == 'InitialForm') {
				
					// output select tag for the form. Strip the newline from
                    // $select_name_string.
                    
                    $select_name_string =  $select_tag_name_array
                    			[$offset+($x*$number_of_subproblems)+$i];
                    $select_name_string = preg_replace('~[\r\n]+~', '', $select_name_string);
                    
                    $selected_option = $table_data->attempt_data['attemptarray']
                    				[$offset+($x*$number_of_subproblems)+$i]->inputvalue;

                    if($selected_option == -1) {
                    	$options = '
								<option value="-1" selected></option>
								<option value="0">F</option>
								<option value="1">T</option>';
                    } elseif($selected_option == 0) {
                         $options = '
								<option value="-1"></option>
								<option value="0" selected>F</option>
								<option value="1">T</option>';
					} else {
					     $options = '
								<option value="-1"></option>
								<option value="0">F</option>
								<option value="1" selected>T</option>';
					}
					
					$html_body = $html_body . '
						<td class="text-center">' . '
							<select name="' . $select_name_string . '">' .
								$options . '
							</select>
						</td>';
							
				} elseif($display_results == 'Submit' or
											$display_results == 'FinshedProblem') {
					
					// output the result for the display request. Get the input
					// value and the correct value.
					
					$select_input = $table_data->attempt_data['attemptarray']
									[$offset+($x*$number_of_subproblems)+$i]->inputvalue;
                    $correct_value = $table_data->attempt_data['attemptarray']
                    				[$offset+($x*$number_of_subproblems)+$i]->correctvalue;
                    
                    // If inputvalue == -1, make it the opposite of correctvalue, so
                    // the following test will always result in a red colored value
                    
                    if($select_input == -1) {$select_input = 1 - $correct_value;}
                    				
                    // Insure $correct_value is interpreted as either "T" or "F",
                    // not as "1" or "NULL"
                    
                    if($correct_value == false) {$correct_value_normalized = "F";
                        } else {$correct_value_normalized = "T";}
                    $select_input_normalized = str_replace($zero_one, $FT, $select_input);
                    
                    if($select_input_normalized != $correct_value_normalized) {
                    	$html_body = $html_body . '
							<td class="text-center"><font color="red"><b>' .
								$correct_value_normalized . '</b></font>
							</td>';
                    } else {
                    	$html_body = $html_body . '
							<td class="text-center">' .
								$correct_value_normalized . '
							</td>';                    
                    }
				
				} else {
					$message = 'Internal error found in create_html_for_truthtable ' . 
                    'in mod/logic/view.php';
        			throw new \coding_exception($message);
				}
			}
			
			$html_body = $html_body . '
					</tr>';
		}
			
		// close the html body
				
		$html_body = $html_body . '
					</tbody>';
					
		$html = $html . $html_problem . $html_middle_start . $html_header .
			$html_middle_end . $html_body . $html_problem_ending;
			
		// Offset used to traverse $select_tag_name_array.
			
		$offset = $offset + $lines_per_problem;
	}
	
	// Close out the body and html sections.

	$html = $html . $html_ending;

	return $html;
}

function generate_select_tag_name_array($table_data) {
	
	for ($i=0; $i<count($table_data->attempt_data['attemptarray']); $i++) {
			
		$interpretation = $table_data->attempt_data['attemptarray'][$i]->atomicvariablesvalue;
		$problem_id = $table_data->attempt_data['attemptarray'][$i]->problemid;
		$subproblem_id = $table_data->attempt_data['attemptarray'][$i]->subproblemid;
		$select_tag_name_array[$i] = $interpretation . '-' . $problem_id . '-' . $subproblem_id;
	
	}
	return $select_tag_name_array;
}
