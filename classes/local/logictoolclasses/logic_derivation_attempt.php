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
 * Back-end code for handling data about logic tool problems and the current
 * user's attempt.
 *
 * @package   mod_quiz
 * @copyright 2023 Dan Nessett <dnessett@yahoo.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_logic\local\logictoolclasses;

defined('MOODLE_INTERNAL') || die();

/**
 * A class encapsulating an attempt to solve a logictoolproblembank
 *
 * @copyright  2023 Dan Nessett
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 4.0
 */
class logic_ttable_attempt {
    /** @var stdClass the logicexpressions string for the problembank. */
    protected $logicexpression;
    /** @var stdClass the course_module. */
    protected $problem_bank_record;

    // Constructor =============================================================
    /**
     * Constructor
     *
     * @param object $attemptdata from ????.
     * @param object $logictool from the logic table.
	 * @param object $logicexpressions from the logic table.
     * @param object $cm the course_module object for this logictoolproblembank.
     * @param object $course the row from the course table for the course we belong to.
     */
    public function __construct($logicexpression, $problem_id, $problem_bank_record) {
        $this->logicexpressions = $logicexpressions;
        }
    }