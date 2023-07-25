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
 * Capability definitions for this module.
 *
 * @package   mod_logic
 * @copyright 2023 Dan Nessett <dnessett@yahoo.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/../../../../../config.php');
require_once(dirname(__FILE__).'/../../../Boollet/vendor/autoload.php');

use D3lph1\Boollet\Parser\{Lexer, Reader\StringInputReader, ShuntingYardParser};
use D3lph1\Boollet\TruthTable;

function compute_correct_ttable_values($atomicvariables, $expression) {
	$lexer = Lexer::default();
    $enhancedexpression = expression_prefix_from_atomicvariables($atomicvariables) .
                                               '⋀(' . $expression . ')';
	$input = new StringInputReader($enhancedexpression);
	$parser = new ShuntingYardParser($lexer);

	$expr = $parser->parse($input);

	$table = TruthTable::tabulate($expr);
		
	return $table;
	
}

function expression_prefix_from_atomicvariables($atomicvariables) {

	// This creates a prefix comprising the atomic variables listed in order
	// as a tautology, e.g. for atomic variables x, y and z:
	// ((x⋁!x)⋀(y⋁!y)⋀(z⋁!z)). This is necessary since the package (Boollet) used
	// for computing the correct values of a boolean expression does not
	// provide a way to specify the order of the atomic variables. Instead,
	// it puts them in the order in which they appear in the expression.
	// This means if the boolean expression has subexpressions that form
	// the subproblems of the truth table, potentially the output of Boollet will not
	// put the correct values in the same order in all subproblems. Consequently,
	// I have to force the order by conjoining the subproblems with the above
	// specified tautoloty, which does not change the truth value of the expression.
    
    // Get the atomic variables as elements of an array
    
    $atomicvariablearray = str_split($atomicvariables, 1);
    
    // iterating over the length of the array, process each atomic
    // variable as '(' <atomicvariable> ⋀ '!' <atomicvariable> ')'.
    // Then concatenate these strings into '(' <strings> ')'.
    
    $numberofvariables = count($atomicvariablearray);
    $atomicvariableexpression = '(';
    
    for($i = 0; $i < $numberofvariables; $i++) {
        
        $element = '(' . $atomicvariablearray[$i] . '⋁' . '!' .
                             $atomicvariablearray[$i] . ')';
        if($i == 0) {    
            $atomicvariableexpression = $atomicvariableexpression . $element;
        } else {
            $atomicvariableexpression = $atomicvariableexpression . '⋀' . $element  ;
        }
    }
    
    $atomicvariableexpression = $atomicvariableexpression . ')';
    
    return $atomicvariableexpression;
}