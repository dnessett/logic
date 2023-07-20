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

if(!empty($_POST)) {

	// view.php entered as the result of a $_POST request.
	
	process_post_data();
	$initial_form = false;
	
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

	// Set up a moodle session to hold the table_data class instance between calls to
	// view.php by the attempt form processing code. Using moodle $SESSION rather than
	// PHP $_SESSION, since the former is per user, while the latter is not.

	$SESSION->table_data = $table_data;
	$initial_form = true;

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

$htmlstring = createhtml($table_data, $initial_form);

// Use the table_data_data instance to output the HTML for the view page.

outputhtml($htmlstring, $output);

return;

function outputhtml($htmlstring, $output) {

echo $output->header();

$pagetitle = "Problems";

echo $output->heading($pagetitle);

echo $htmlstring;

echo $output->footer();

return;

}

function process_post_data () {

	// process the $_POST data. If the $_POST is for the "Submit" button, record the
	// results of the attempt and terminate the attempt. Otherwise, if the $_POST is
	// for the "SaveAndExit button, process the $_POST data and re-display the
	// attempt form.
    
    global $SESSION;

	$table_data = $SESSION->table_data;

	if ($_POST['action'] == 'SaveAndExit') {
	
	// What logic tool are we processing?
	
		switch ($table_data->logictool) {
			case "truthtable":
			
				return change_truthtable_data($table_data);
			
			break;
			
			case "truthtree":
			
				return change_truthtree_data($table_data);
				
			break;
					
			case "derivation":
			
				return change_derivation_data($table_data);

			break;
					
			default:
				$message = 'Internal error in process_post_data in ' .
					'mod/logic/view.php. Invalid logictool type';
				throw new \coding_exception($message);
	}

	return;

	} elseif ($_POST['action'] == 'Submit') {

	// Process the Submit request

	return;

	} else {

		// Whoopse. There is an internal coding error. Throw a coding exception.
		
		$message = 'Internal error found in process_post_data ' . 
                       'in mod/logic/view.php';
        throw new \coding_exception($message);
    }
    
    return;
}

function change_truthtable_data($table_data) {
    
    global $DB;

	// Process the SaveAndExit request. First, Get the select_tag_name_array
	// to enumerate the attempt data that may have changed. Then create a map
	// between select_tag_names and the indexes of the attempt array.
	
	$select_tag_name_array  = generate_select_tag_name_array($table_data);
	
	// Get the logic ttable attempt table rows pertinent to this problem bank
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
            
			if (!$DB->set_field('logic_ttable_attempt',
				'inputvalue',
				$_POST[$select_name],
				array('id' => $problem_attempt_record_id))) {
		
					$message = 'Internal error #2 found in change_truthtable_data ' . 
								   'in mod/logic/view.php';
					throw new \coding_exception($message);
			}	
        }
	
	return;

}

function generate_select_tag_name_array($table_data) {

// Save in case new code doesn't work
/*
	$problemarray = $table_data->problemstrings;
        
        $select_tag_name_array = array();

	foreach($problemarray as $index => $problemstring) {
	
		// burst the problemstring
		
		$logicexpressionparts = explode(",", $table_data->problemstrings[$index]);
        $atomicvariables = array_shift($logicexpressionparts);
	
		// Fill in the problem number in html
		
		$problem_number = $index + 1;		
		$number_of_subproblems = count($logicexpressionparts);
		$length = strlen($atomicvariables);
		$lines_per_subproblem = 2 ** $length;
		$lines_per_problem = $number_of_subproblems * $lines_per_subproblem;
		
		for($x = 0; $x < $number_of_subproblems; $x++) {
			for($y = 0; $y < $lines_per_subproblem; $y++) {
				$interpretation = ((array)
					($table_data->attempt_data['attemptarray'][$y]))['atomicvariablesvalue'];
			
				// retrieving the string from the attempt array leaves a newline at the end.
				// Getrid of it.
			
				$interpretation = preg_replace('~[\r\n]+~', '', $interpretation);

				array_push($select_tag_name_array, $interpretation
							. '-' . $problem_number . '-' . $x+1);
			}
		}
	}
	*/
	
	for ($i=0; $i<count($table_data->attempt_data['attemptarray']); $i++) {
		
		$interpretation = $table_data->attempt_data['attemptarray'][$i]->atomicvariablesvalue;
		$problem_id = $table_data->attempt_data['attemptarray'][$i]->problemid;
		$subproblem_id = $table_data->attempt_data['attemptarray'][$i]->subproblemid;
		$select_tag_name_array[$i] = $interpretation . '-' . $problem_id . '-' . $subproblem_id;
	
	}
	return $select_tag_name_array;
}

function change_truthtree_data($table_data) {

	// create the html for a truthtree problem set.

	return $html;
}

function change_derivation_data($table_data) {

	// create the html for a derivation problem set.

	return $html;
}

function createhtml($table_data, $initial_form) {

	// Create the html to display on the view page. If $initial_form == true, generate
	// the form for the problem set. If $initial_form == false, generate the html for the
	// result of the problem set (i.e., the result of the submit request.) In
	// either case return the html string to the caller.

	// Determine which logic tool to use.

	switch ($table_data->logictool) {
			case "truthtable":
			
				return create_html_for_truthtable($table_data, $initial_form);
			
			break;
			
			case "truthtree":
			
				return create_html_for_truthtree($table_data, $initial_form);
				
			break;
					
			case "derivation":
			
				return create_html_for_derivation($table_data, $initial_form);

			break;
					
			default:
				$message = 'Internal error in create_html in ' .
					'mod/logic/classses/logictoolclasses/view.php. Invalid logictool type';
				throw new \coding_exception($message);
	}
}

function create_html_for_truthtable($table_data, $initial_form) {

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

	$html_middle_start = '
					<thead>
						<tr>';
	
	$html_middle_end = '
						</tr>
					</thead>';
					
	// Set up the overall document html
	
	$html = $html_begining;
	
/*	Save in case new code doesn't work.
	// Get the problem array
	
	$problemarray = $table_data->problemstrings;
	
	foreach($problemarray as $index => $problemstring) {
	
		// Fill in the problem number in html
		
		$problem_number = $index + 1;
		
		$html_problem = '
		<h3>Problem ' . $problem_number . '</h3>
			<div class="col-2">
				<table class="table table-striped">';
	
		// burst the problemstring
		
		$logicexpressionparts = explode(",", $table_data->problemstrings[$index]);
        $atomicvariables = array_shift($logicexpressionparts);

		$html_header = '
						<th class="col">' . $atomicvariables . '</th>';
		foreach($logicexpressionparts as $logicexpression) {
		
			$html_header = $html_header . '
						<th class="col">' . $logicexpression . '</th>';
		
		}
		
// create html for truthtable form
			
		$html_body = '
					<tbody>';
			
		$number_of_subproblems = count($logicexpressionparts);
		$length = strlen($atomicvariables);
		$lines_per_subproblem = 2 ** $length;
			
		for($x = 0; $x < $lines_per_subproblem; $x++) {
			$interpretation =  ((array) ($table_data->attempt_data['attemptarray'][$x]))['atomicvariablesvalue'];
			
			// retrieving the string from the attempt array leaves a newline at the end.
			// Getrid of it.
			
			$interpretation = preg_replace('~[\r\n]+~', '', $interpretation);
			
			$html_body = $html_body . '
					<tr>' . '
                        <td>' . $interpretation . '</td>';
                        
			for($i = 0; $i < $number_of_subproblems; $i++) {
				if($initial_form == true) {
				
					// output select tag for the form
					
					$html_body = $html_body . '
						<td>' . '
							<select name= "' . (string) $interpretation . '-' . $problem_number .
									'-' . $i+1 . '">
								<option value="-1"></option>
								<option value="0">F</option>
								<option value="1">T</option>
							</select>
						</td>';
							
				} else {
					
					// output the result for the submit results
				
				}

			// create html for truthtable problem
		
			}
			
			$html_body = $html_body . '
					</tr>';
		}
			
		// close the html body
				
		$html_body = $html_body . '
					</tbody>';
					
		$html = $html . $html_problem . $html_middle_start . $html_header .
			$html_middle_end . $html_body . $html_problem_ending;
	}
*/
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
			<div class="col-2">
				<table class="table table-striped">';

		$html_header = '
						<th class="col">' . $atomicvariables . '</th>';
		foreach($logicexpressionparts as $logicexpression) {
		
			$html_header = $html_header . '
						<th class="col">' . $logicexpression . '</th>';
		
		}
		
		// create html for truthtable form
			
		$html_body = '
					<tbody>';
			
		for($x = 0; $x < $lines_per_subproblem ; $x++) {
		
			$select_name_string =  $select_tag_name_array[$offset+($x*$number_of_subproblems)];
			$select_name_string_parts =  explode("-", $select_name_string);
			$interpretation = $select_name_string_parts[0];
			
			// Strip the newline from $interpretation
			
			$interpretation = preg_replace('~[\r\n]+~', '', $interpretation);
			
			$html_body = $html_body . '
					<tr>' . '
                        <td>' . $interpretation . '</td>';
                        
			for($i = 0; $i < $number_of_subproblems; $i++) {
				if($initial_form == true) {
				
					// output select tag for the form. Strip the newline from
                    // $select_name_string.
                    
                    $select_name_string =  $select_tag_name_array
                    			[$offset+($x*$number_of_subproblems)+$i];
                    $select_name_string = preg_replace('~[\r\n]+~', '', $select_name_string);
					
					$html_body = $html_body . '
						<td>' . '
							<select name="' . $select_name_string . '">
								<option value="-1"></option>
								<option value="0">F</option>
								<option value="1">T</option>
							</select>
						</td>';
							
				} else {
					
					// output the result for the submit results
				
				}

			// create html for truthtable problem
		
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
