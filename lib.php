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

function array_flatten($array) {

	$result = array();
	$n = count($array);
    $offset = 0;

	for($i = 0; $i < $n; $i++) {
        $start = array_key_first($array[$i]);
        $m = count($array[$i]);
		for ($j = $start; $j < $m+$start; $j++) {
			$result[($offset)+($j-$start)] = $array[$i][$j-$start];
		}
        $offset = $offset + $m;
    }

	return $result;
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
