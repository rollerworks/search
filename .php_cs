<?php

$header = <<<EOF
This file is part of the RollerworksSearch package.

(c) Sebastiaan Stok <s.stok@rollerscapes.net>

This source file is subject to the MIT license that is bundled
with this source code in the file LICENSE.
EOF;

return PhpCsFixer\Config::create()
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PHP70Migration' => true,
        '@PHP71Migration' => true,
        'array_syntax' => array('syntax' => 'short'),
        'combine_consecutive_unsets' => true,
        'declare_strict_types' => true,
        'header_comment' => ['header' => $header],
        'heredoc_to_nowdoc' => true,
        'linebreak_after_opening_tag' => true,
        'no_extra_consecutive_blank_lines' => ['continue', 'extra', 'return', 'throw', 'use', 'parenthesis_brace_block', 'square_brace_block', 'curly_brace_block'],
        'no_short_echo_tag' => true,
        'no_unreachable_default_argument_value' => false,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_class_elements' => false,
        'ordered_imports' => true,
        'phpdoc_add_missing_param_annotation' => false,
        'phpdoc_annotation_without_dot' => true,
        'phpdoc_no_empty_return' => false, // PHP 7 compatibility
        'phpdoc_order' => true,
        // This breaks for variable @var blocks
        'phpdoc_to_comment' => false,
        'phpdoc_var_without_name' => false,
        'semicolon_after_instruction' => true,
        'single_import_per_statement' => false,
        'strict_comparison' => false,
        'strict_param' => true,
    ])
    ->setRiskyAllowed(true)
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->exclude('Fixtures')
            ->in([__DIR__.'/src', __DIR__.'/tests'])
    )
;
