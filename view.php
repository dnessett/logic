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

$initialized = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	// make sure the table_data class instance exits (i.e., view.php
	// is called for the first time by moodle, not by processing an
	// attempt form.
	
	if($initialized === false) {
	
		// This should never happen, but if it does it is a coding error
		
		$message = 'Internal error found ' . 
                'in mod/logic/classses/logictoolclasses/view.php.';
        throw new \coding_exception($message);
	
	} else {
	
		process_post_data ();
	
	}

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

$initialized = true;

echo $OUTPUT->header();

// Use the table_data_data instance to output the HTML for the view page.

outputhtml($table_data);

echo $OUTPUT->footer();

process_post_data();

return;

function outputhtml($table_data) {

return;

}

function process_post_data () {

$table_data = $_SESSION['table_data'];

// process the $_POST data. If the $_POST is for a "submit" button, record the
// results of the attempt and terminate the attempt. Otherwise, process the
// $_POST data and re-display the attempt form.
 
return;

}
