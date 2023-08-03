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
 * Library of interface functions and constants.
 *
 * @package     mod_logic
 * @copyright   2023 Dan Nessett <dnessett@yahoo.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
 
 function logic_view($logic, $course, $cm, $context) {

    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $logic->id
    );

    $event = \mod_page\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('logic', $logic);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

function logic_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_logic into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_logic_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function logic_add_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timecreated = time();

    $id = $DB->insert_record('logic', $moduleinstance);

    return $id;
}

/**
 * Updates an instance of the mod_logic in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_logic_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function logic_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    return $DB->update_record('logic', $moduleinstance);
}

/**
 * Removes an instance of the mod_logic from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function logic_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('logic', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('logic', array('id' => $id));

    return true;
}

function logic_lock() {

$file = __DIR__  . "/lockfile.txt";
$fp = fopen($file, 'w');
flock($fp, LOCK_EX);
return $fp;

}

function logic_unlock($fp) {

flock($fp, LOCK_UN);
fclose($fp);

}

// functions logic_supports(), logic_grade_item_update(), logic_update_grades(),
// and logic_grade_item_update() are taken from mod/lesson/lib.php, then modified
// for logic

/**
 * Return the list if Moodle features this module supports
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not,
 * null if doesn't know or string for the module purpose.
 */
function logic_supports($feature) {
    switch($feature) {
        case FEATURE_GRADE_HAS_GRADE:         return true;

        default: return null;
    }
}

/**
 * Create grade item for given assignment.
 *
 * @param stdClass $logic record with extra cmid
 * @param array $grades optional array/object of grade(s); 
 * 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function logic_grade_item_update($logic, $grades=NULL) {
    global $CFG;
    
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    $params = array('itemname'=>$logic->name, 'idnumber'=>$logic->cm_id);
    
	$params['gradetype'] = GRADE_TYPE_VALUE;
	$params['grademax']  = 100;
	$params['grademin']  = 0;

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = NULL;
    }

    return grade_update('mod/logic', $logic->course, 'mod', 'logic', $logic->id,
    																0, $grades, $params);
}

/**
 * Create grade item for given logic problem
 *
 * @category grade
 * @uses GRADE_TYPE_VALUE
 * @param object $logic object
 * @param array|object $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function logic_grade_item_update($logic, $grades=null) {
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    if (property_exists($logic, 'cm_id')) { //it may not be always present
        $params = array('itemname'=>$logic->name, 'idnumber'=>$logic->cm_id);
    } else {
        $params = array('itemname'=>$logic->name);
    }

    if (!$logic->practice and $logic->grade > 0) {
        $params['gradetype']  = GRADE_TYPE_VALUE;
        $params['grademax']   = 100;
        $params['grademin']   = 0;
    }

	// Make sure current grade fetched correctly from $grades
	$currentgrade = null;
	if (!empty($grades)) {
		if (is_array($grades)) {
			$currentgrade = reset($grades);
		} else {
			$currentgrade = $grades;
		}
	}

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = null;
    } else if (!empty($grades)) {
        // Need to calculate raw grade (Note: $grades has many forms)
        if (is_object($grades)) {
            $grades = array($grades->userid => $grades);
        } else if (array_key_exists('userid', $grades)) {
            $grades = array($grades['userid'] => $grades);
        }
        foreach ($grades as $key => $grade) {
            if (!is_array($grade)) {
                $grades[$key] = $grade = (array) $grade;
            }
            //check raw grade isnt null otherwise we erroneously insert a grade of 0
            if ($grade['rawgrade'] !== null) {
                $grades[$key]['rawgrade'] = ($grade['rawgrade'] * $params['grademax'] / 100);
            } else {
                //setting rawgrade to null just in case user is deleting a grade
                $grades[$key]['rawgrade'] = null;
            }
        }
    }

    return grade_update('mod/logic', $logic->course, 'mod', 'logic', $logic->id, 0, $grades, $params);
}