<?php

$header = <<<EOF
This file is part of the RollerworksSearch package.

(c) Sebastiaan Stok <s.stok@rollerscapes.net>

This source file is subject to the MIT license that is bundled
with this source code in the file LICENSE.
EOF;

return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules(
        // This rules list is based of the styleci.yml file.
        // Last updated on 2016-12-24 16:30
        [
            'header_comment' => ['header' => $header],
            'array_syntax' =>
                [
                    'syntax' => 'short',
                ],
            'binary_operator_spaces' =>
                [
                    'align_equals' => false,
                    'align_double_arrow' => false,
                ],
            'blank_line_after_namespace' => true,
            'blank_line_after_opening_tag' => true,
            'blank_line_before_return' => true,
            'braces' => true,
            'cast_spaces' => true,
            'class_definition' =>
                [
                    'singleLine' => true,
                ],
            'concat_space' =>
                [
                    'spacing' => 'none',
                ],
            'declare_equal_normalize' => true,
            'declare_strict_types' => true,
            'elseif' => true,
            'encoding' => true,
            'full_opening_tag' => true,
            'function_declaration' => true,
            'function_typehint_space' => true,
            'general_phpdoc_annotation_remove' =>
                [
                    'access',
                    'package',
                    'subpackage',
                ],
            'hash_to_slash_comment' => true,
            'heredoc_to_nowdoc' => true,
            'include' => true,
            'indentation_type' => true,
            'line_ending' => true,
            'linebreak_after_opening_tag' => true,
            'lowercase_cast' => true,
            'lowercase_constants' => true,
            'lowercase_keywords' => true,
            'method_argument_space' => true,
            'method_separation' => true,
            'native_function_casing' => true,
            'new_with_braces' => true,
            'no_alias_functions' => true,
            'no_blank_lines_after_class_opening' => true,
            'no_blank_lines_after_phpdoc' => true,
            'no_closing_tag' => true,
            'no_empty_phpdoc' => true,
            'no_empty_statement' => true,
            'no_extra_consecutive_blank_lines' =>
                [
                    'throw',
                    'use',
                    'curly_brace_block',
                    'parenthesis_brace_block',
                    'square_brace_block',
                    'extra',
                ],
            'no_leading_import_slash' => true,
            'no_leading_namespace_whitespace' => true,
            'no_mixed_echo_print' =>
                [
                    'use' => 'echo',
                ],
            'no_multiline_whitespace_around_double_arrow' => true,
            'no_short_bool_cast' => true,
            'no_singleline_whitespace_before_semicolons' => true,
            'no_spaces_after_function_name' => true,
            'no_spaces_around_offset' =>
                [
                    'inside',
                    'outside',
                ],
            'no_spaces_inside_parenthesis' => true,
            'no_trailing_comma_in_list_call' => true,
            'no_trailing_comma_in_singleline_array' => true,
            'no_trailing_whitespace' => true,
            'no_trailing_whitespace_in_comment' => true,
            'no_unneeded_control_parentheses' => true,
            'no_unreachable_default_argument_value' => true,
            'no_unused_imports' => true,
            'no_whitespace_before_comma_in_array' => true,
            'no_whitespace_in_blank_line' => true,
            'normalize_index_brace' => true,
            'object_operator_without_whitespace' => true,
            'ordered_imports' => true,
            'php_unit_fqcn_annotation' => true,
            'phpdoc_align' => true,
            'phpdoc_annotation_without_dot' => true,
            'phpdoc_indent' => true,
            'phpdoc_inline_tag' => true,
            'phpdoc_no_alias_tag' =>
                [
                    'link' => 'see',
                    'type' => 'var',
                ],
            'phpdoc_order' => true,
            'phpdoc_scalar' => true,
            'phpdoc_separation' => true,
            'phpdoc_single_line_var_spacing' => true,
            'phpdoc_summary' => true,
            'phpdoc_to_comment' => true,
            'phpdoc_trim' => true,
            'phpdoc_types' => true,
            'phpdoc_var_without_name' => true,
            'pre_increment' => true,
            'psr4' => true,
            'return_type_declaration' => true,
            'self_accessor' => true,
            'short_scalar_cast' => true,
            'silenced_deprecation_error' => true,
            'single_blank_line_at_eof' => true,
            'single_blank_line_before_namespace' => true,
            'single_class_element_per_statement' => true,
            'single_import_per_statement' => false, // Why else would be using PHP 7.1?
            'single_line_after_imports' => true,
            'single_quote' => true,
            'space_after_semicolon' => true,
            'standardize_not_equals' => true,
            'strict_param' => true,
            'switch_case_semicolon_to_colon' => true,
            'switch_case_space' => true,
            'ternary_operator_spaces' => true,
            'trailing_comma_in_multiline_array' => true,
            'trim_array_spaces' => true,
            'unary_operator_spaces' => true,
            'visibility_required' =>
                [
                    'method',
                    'property',
                ],
            'whitespace_after_comma_in_array' => true,
        ]
    )
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->exclude('Fixtures')
            ->exclude('travis')
            ->in(__DIR__)
    )
;
