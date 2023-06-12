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
 * Upgrade script for the logic module.
 *
 * @package    mod_logic
 * @copyright   2023 Dan Nessett <dnessett@yahoo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Logic module upgrade function.
 * @param string $oldversion the version we are upgrading from.
 */
function xmldb_logic_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    // Put any upgrade step following this.

    // Automatically generated Moodle v4.0.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2023061100) {

        // Define field logicexpressions to be added to logic.
        $table = new xmldb_table('logic');
        $field = new xmldb_field('logicexpressions', XMLDB_TYPE_TEXT, null, null, null, null, null, 'introformat');

        // Conditionally launch add field logicexpressions.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Define field logictool to be added to logic.
        $table = new xmldb_table('logic');
        $field = new xmldb_field('logictool', XMLDB_TYPE_TEXT, null, null, null, null, null, 'logicexpressions');

        // Conditionally launch add field logictool.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Logic savepoint reached.
        upgrade_mod_savepoint(true, 2023061100, 'logic');

    }

    return true;
}