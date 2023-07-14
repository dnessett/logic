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
 * @copyright   2023 Dan Nessett <dnessett@yahoo.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/course/format/lib.php');

use \mod_logic\local\logictoolclasses\logic_table_data;

if(!empty($_POST)) {
	
		process_post_data ();
		
		return;
	
}

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

// Check login and get context.

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/logic:view', $context);

// Cache some other capabilities we use several times.

$canattempt = has_capability('mod/logic:attempt', $context);
$canreviewmine = has_capability('mod/logic:reviewmyattempts', $context);

// Trigger course_module_viewed event and completion.

logic_view($logic, $course, $cm, $context);

$PAGE->set_url('/mod/logic/view.php', array('id' => $cm->id));
$title = $course->shortname . ': ' . format_string($logic->name);
$PAGE->set_title($title);
$PAGE->set_heading(format_string($course->fullname));

// Create or use the logic, problem bank, problem set, problem and attempt db tables.
// Return an instance of logic_table_data, which encapsulates the classes associated
// with the problem bank tables.

$table_data = new logic_table_data($logic, $course, $cm);

// Set up a session to hold the table_data class instance between calls to
// view.php by the attempt form processing code.

$_SESSION['table_data'] = $table_data;

// Get an instance of the output class.

$output = $PAGE->get_renderer('mod_logic');

// Create the HTML form

$form = true;

$htmlstring = createhtml($table_data, $form);

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

	$table_data = $_SESSION['table_data'];

	if(isset($_POST['SaveAndExit'])) {

	// Process the SaveAndExit requiest

	return;

	} elseif (isset($_POST['Submit'])) {

	// Process the Submit request

	return;

	} else {

		// Whoopse. There is an internal coding error. Throw a coding exception.
		
		$message = 'Internal error found in get_problem_data ' . 
                       'in mod/logic/classses/logictoolclasses/logic_tables.php.';
        throw new \coding_exception($message);
    }
    
    return;
}

function createhtml($table_data, $form) {

	// Create the html to display on the view page. If $form == true, generate
	// the form for the problem set. If $form == false, generate the html for the
	// result of the problem set (i.e., the result of the submit request.) In
	// either case return the html string to the caller.

	// Determine which logic tool to use.

	switch ($table_data->logictool) {
			case "truthtable":
			
				return create_html_for_truthtable($table_data, $form);
			
			break;
			
			case "truthtree":
			
				return create_html_for_truthtree($table_data, $form);
				
			break;
					
			case "derivation":
			
				return create_html_for_derivation($table_data, $form);

			break;
					
			default:
				$message = 'Internal error in create_html in ' .
				'mod/logic/classses/logictoolclasses/view.php. Invalid logictool type';
				throw new \coding_exception($message);
	}
}

function create_html_for_truthtable($table_data, $form) {

	// create the html for a truthtable problem set.
	
	// Save in case alternative doesn't work
	/*
		input[type=submit] {
		  background-color: #04AA6D;
		  border: none;
		  color: white;
	 	  padding: 16px 32px;
	 	  text-decoration: none;
	 	  margin: 4px 2px;
	 	  cursor: pointer;
		}
		*/
	
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
						<td style="border: none;"><input type="submit" value="SaveAndExit"/></td>
					</tr>
					<tr>
						<td style="border: none;"><input type="submit" value="Submit"
													style = "background-color: #fa1f0f;"/></td>
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
			$interpretation = $table_data->attempt_data['attemptarray'][$x]->atomicvariablesvalue;
			
			// retrieving the string from the attempt array leaves a newline at the end.
			// Getrid of it.
			
			$interpretation = preg_replace('~[\r\n]+~', '', $interpretation);
			
			$html_body = $html_body . '
					<tr>' . '
                        <td>' . $interpretation . '</td>';
                        
			for($i = 0; $i < $number_of_subproblems; $i++) {
				if($form == true) {
				
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
	
	// Close out the body and html sections.

	$html = $html . $html_ending;

	return $html;
}

function create_html_for_truthtree($table_data, $form) {

	// create the html for a truthtree problem set.

	return $html;
}

function create_html_for_derivation($table_data, $form) {

	// create the html for a derivation problem set.

	return $html;
}
