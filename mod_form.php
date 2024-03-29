*<?php
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
 * The main mod_logic configuration form.
 *
 * @package     mod_logic
 * @copyright   2023 Dan Nessett <dnessett@yahoo.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package     mod_logic
 * @copyright   2023 Dan Nessett <dnessett@yahoo.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_logic_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('logicname', 'mod_logic'), array('size' => '64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'logicname', 'mod_logic');

        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }
                
        // Select Problem Mode
        
		$mode = array();
        $mode['assignment'] = get_string('assignment', 'mod_logic');
        $mode['practice'] = get_string('practice', 'mod_logic');      
        
        $mform->addElement('select', 'mode', get_string('mode', 'mod_logic'), $mode);
        
        // Select Logic Tool
        
		$tools = array();
        $tools['truthtable'] = get_string('truthtable', 'mod_logic');
        $tools['truthtree'] = get_string('truthtree', 'mod_logic');
		$tools['derivation'] = get_string('derivation', 'mod_logic');      
        
        $mform->addElement('select', 'logictool', get_string('logictool', 'mod_logic'), $tools);
        
        // Text area for logic expressions
        
        $mform->addElement('textarea', 'logicexpressions', get_string("logicexpressions", "logic"), 'wrap="virtual" tcols="50"');

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }
}  