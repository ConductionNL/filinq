<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
	->in(__DIR__)
	->exclude('build')
	->exclude('l10n')
	->exclude('node_modules')
	->exclude('vendor')
	->exclude('vendor-bin')
	->exclude('tests/fixtures')
	->name('*.php')
	->notName('*.generated.php');

$config = new PhpCsFixer\Config();
$config
	->setRiskyAllowed(true)
	->setRules([
		'@PSR12' => true,
		'@PHP81Migration' => true,
		'array_syntax' => ['syntax' => 'short'],
		'binary_operator_spaces' => true,
		'blank_line_after_namespace' => true,
		'blank_line_after_opening_tag' => true,
		'blank_line_before_statement' => ['statements' => ['return']],
		'cast_spaces' => true,
		'concat_space' => ['spacing' => 'one'],
		'declare_equal_normalize' => true,
		'function_typehint_space' => true,
		'include' => true,
		'lowercase_cast' => true,
		'method_argument_space' => true,
		'native_function_casing' => true,
		'no_blank_lines_after_class_opening' => true,
		'no_blank_lines_after_phpdoc' => true,
		'no_empty_statement' => true,
		'no_extra_blank_lines' => true,
		'no_leading_import_slash' => true,
		'no_leading_namespace_whitespace' => true,
		'no_multiline_whitespace_around_double_arrow' => true,
		'no_short_bool_cast' => true,
		'no_singleline_whitespace_before_semicolons' => true,
		'no_spaces_around_offset' => true,
		'no_trailing_comma_in_singleline_array' => true,
		'no_unneeded_control_parentheses' => true,
		'no_unused_imports' => true,
		'no_whitespace_before_comma_in_array' => true,
		'no_whitespace_in_blank_line' => true,
		'normalize_index_brace' => true,
		'object_operator_without_whitespace' => true,
		'php_unit_fqcn_annotation' => true,
		'phpdoc_indent' => true,
		'phpdoc_no_access' => true,
		'phpdoc_no_package' => true,
		'phpdoc_no_useless_inheritdoc' => true,
		'phpdoc_scalar' => true,
		'phpdoc_single_line_var_spacing' => true,
		'phpdoc_summary' => true,
		'phpdoc_to_comment' => true,
		'phpdoc_trim' => true,
		'phpdoc_types' => true,
		'phpdoc_var_without_name' => true,
		'return_type_declaration' => true,
		'self_accessor' => true,
		'short_scalar_cast' => true,
		'single_blank_line_before_namespace' => true,
		'single_class_element_per_statement' => true,
		'single_line_after_imports' => true,
		'single_quote' => true,
		'space_after_semicolon' => true,
		'standardize_not_equals' => true,
		'ternary_operator_spaces' => true,
		'trailing_comma_in_multiline' => ['elements' => ['arrays']],
		'trim_array_spaces' => true,
		'unary_operator_spaces' => true,
		'whitespace_after_comma_in_array' => true,
	])
	->setFinder($finder);

return $config;
