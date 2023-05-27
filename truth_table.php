<?php
declare(strict_types=1);

namespace D3lph1\Boollet;

require __DIR__ . '/vendor/autoload.php';

use D3lph1\Boollet\Parser\{Lexer, Reader\StringInputReader, ShuntingYardParser};
use D3lph1\Boollet\TruthTable;
use Symfony\Component\VarDumper\VarDumper;

$lexer = Lexer::default();
$input = new StringInputReader('X ⊕ Y → (X ⋀ Z)');
$parser = new ShuntingYardParser($lexer);

$expr = $parser->parse($input);

$table = TruthTable::tabulate($expr);
$table->setLabel('');

echo $table;

$pre = "<pre>";
$slash_pre =  "</pre>";
$columnated = $pre . $table . $slash_pre;

echo $columnated;

dd($table->getRows(), $table->getValues());

$bool_input = $table->getRows();
$bool_output = $table->getValues();

foreach ($bool_input as &$input_array) {
    foreach ($bool_output as &$function_value) {
        array_push($input_array, $function_value);
        print($input_array);
    }
}

?>