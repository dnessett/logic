<?php
// grade.php

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
 * Redirects the user to either a logic or to the logic statistics
 *
 * @package   mod_logic
 * @category  grade
 * @copyright 2023 Dan Nessett <dnessett@yahoo.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

/**
 * Require config.php
 */

global $DB;
require_once("../../config.php");

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('logic', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$logic = new logic($DB->get_record('logic', array('id' => $cm->instance), '*', MUST_EXIST));

require_login($course, false, $cm);

$PAGE->set_url('/mod/logic/grade.php', array('id'=>$cm->id));

redirect('view.php?id='.$cm->id);



// the functions below go into mod/logic/lib.php

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

    $params = array('itemname'=>$logic->name, 'idnumber'=>$logic->cmid);

    if (!$logic->assessed or $logic->scale == 0) {
        $params['gradetype'] = GRADE_TYPE_NONE;

    } else if ($logic->scale > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $logic->scale;
        $params['grademin']  = 0;

    } else if ($logic->scale < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$logic->scale;
    }

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = NULL;
    }

    return grade_update('mod/logic', $logic->course, 'mod', 'logic', $logic->id,
    																0, $grades, $params);
}

/**
 * Update activity grades.
 *
 * @param stdClass $logic database record
 * @param int $userid specific user only, 0 means all
 * @param bool $nullifnone - not used
 */
function logic_update_grades($logic, $userid=0, $nullifnone=true) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    if (!$logic->assessed) {
        logic_grade_item_update($logic);

    } else if ($grades = logic_get_user_grades($logic, $userid)) {
        logic_grade_item_update($logic, $grades);

    } else if ($userid and $nullifnone) {
        $grade = new stdClass();
        $grade->userid   = $userid;
        $grade->rawgrade = NULL;
        logic_grade_item_update($logic, $grade);

    } else {
        logic_grade_item_update($logic);
    }
}

/**
 * Create grade item for given logic problem
 *
 * @category grade
 * @uses GRADE_TYPE_VALUE
 * @uses GRADE_TYPE_NONE
 * @param object $logic object with extra cmid
 * @param array|object $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function logic_grade_item_update($logic, $grades=null) {
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    if (property_exists($logic, 'cmid')) { //it may not be always present
        $params = array('itemname'=>$logic->name, 'idnumber'=>$logic->cmid);
    } else {
        $params = array('itemname'=>$logic->name);
    }

    if (!$logic->practice and $logic->grade > 0) {
        $params['gradetype']  = GRADE_TYPE_VALUE;
        $params['grademax']   = $logic->grade;
        $params['grademin']   = 0;
    } else if (!$logic->practice and $logic->grade < 0) {
        $params['gradetype']  = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$logic->grade;

        // Make sure current grade fetched correctly from $grades
        $currentgrade = null;
        if (!empty($grades)) {
            if (is_array($grades)) {
                $currentgrade = reset($grades);
            } else {
                $currentgrade = $grades;
            }
        }

        // When converting a score to a scale, use scale's grade maximum to calculate it.
        if (!empty($currentgrade) && $currentgrade->rawgrade !== null) {
            $grade = grade_get_grades($logic->course, 'mod', 'logic', $logic->id, $currentgrade->userid);
            $params['grademax']   = reset($grade->items)->grademax;
        }
    } else {
        $params['gradetype']  = GRADE_TYPE_NONE;
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