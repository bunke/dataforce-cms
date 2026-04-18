<?php
/**
 * Conservative formatter config — safe for legacy code.
 *
 * Only normalises whitespace, indentation, trailing commas and a couple
 * of trivial syntactic tidies. No structural changes, no type hints, no
 * renames. Run manually:
 *
 *     php php-cs-fixer.phar fix --config=.php-cs-fixer.php
 */

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/bin')
    ->append([__DIR__ . '/bootstrap.php'])
    ->name('*.php')
    ->name('dataforce-install')
    // Leave third-party libs alone
    ->notPath('lib/PHPExcel')
    ->notPath('plugins');

return (new PhpCsFixer\Config())
    ->setFinder($finder)
    ->setRiskyAllowed(false)
    ->setIndent("\t")              // legacy uses tabs predominantly
    ->setLineEnding("\n")
    ->setRules([
        // --- Whitespace hygiene ---
        'no_trailing_whitespace'              => true,
        'no_trailing_whitespace_in_comment'   => true,
        'no_whitespace_in_blank_line'         => true,
        'single_blank_line_at_eof'            => true,
        'blank_line_after_opening_tag'        => true,
        'no_extra_blank_lines'                => ['tokens' => ['extra', 'throw', 'use']],

        // --- Operator / comma spacing ---
        'binary_operator_spaces'              => ['default' => 'single_space'],
        'concat_space'                        => ['spacing' => 'one'],
        'no_spaces_after_function_name'       => true,
        'no_spaces_inside_parenthesis'        => true,
        'no_whitespace_before_comma_in_array' => true,
        'whitespace_after_comma_in_array'     => true,
        'trim_array_spaces'                   => true,
        'array_indentation'                   => true,
        'space_after_semicolon'               => true,

        // --- Minor syntactic tidies ---
        'array_syntax'                        => ['syntax' => 'short'],    // array() -> []
        'no_closing_tag'                      => true,                     // drop trailing close-tag
        'single_quote'                        => true,
        'no_empty_statement'                  => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'single_line_after_imports'           => true,

        // --- Braces / indentation ---
        'braces'                              => [
            'allow_single_line_closure' => true,
            'position_after_functions_and_oop_constructs' => 'next',
        ],
    ]);
