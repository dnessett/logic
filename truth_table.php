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

defined('MOODLE_INTERNAL') || die();

declare(strict_types=1);

namespace D3lph1\Boollet;

require(__DIR__ . '/vendor/autoload.php');

use D3lph1\Boollet\Parser\{Lexer, Reader\StringInputReader, ShuntingYardParser};
use D3lph1\Boollet\TruthTable;
use Symfony\Component\VarDumper\VarDumper;

$lexer = Lexer::default();
$input = new StringInputReader('x ⊕ y → (x ⋀ z)');
$parser = new ShuntingYardParser($lexer);

$expr = $parser->parse($input);

$table = TruthTable::tabulate($expr);
$table->setLabel('');

echo $table;

$pre = "<pre>";
$slashpre = "</pre>";
$columnated = $pre . $table . $slashpre;

echo $columnated;

dd($table->getRows(), $table->getValues());

$boolinput = $table->getRows();
$booloutput = $table->getValues();

foreach ($boolinput as &$inputarray) {
    foreach ($booloutput as &$functionvalue) {
        array_push($inputarray, $functionvalue);
        print($inputarray);
    }
}
