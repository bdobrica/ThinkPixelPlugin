<?php
// These should be passed to php-src/build/gen_stub.php to generate runkit7_arginfo.h
// Use a checkout of php-src 8.0 to generate this.
//
// FOR A HUMAN-READABLE VERSION, SEE runkit-api.php

#ifdef PHP_RUNKIT_MANIPULATION
/**
 * @param mixed $value
 */
function runkit7_constant_add(string $constant_name, $value, int $new_visibility = RUNKIT7_ACC_PUBLIC): bool {}
/**
 * @param mixed $value
 */
function runkit7_constant_redefine(string $constant_name, $value, ?int $new_visibility = null): bool {}
function runkit7_constant_remove(string $constant_name): bool {}
/** @param Closure|string $argument_list_or_closure */
function runkit7_function_add(string $function_name, $argument_list_or_closure, ?string $code_or_doc_comment = null, ?bool $return_by_reference = null, ?string $doc_comment = null, ?string $return_type = null, ?bool $is_strict = null): bool {}
function runkit7_function_copy(string $source_name, string $target_name): bool {}
/**
 * @param Closure|string $argument_list_or_closure
 */
function runkit7_function_redefine(string $function_name, $argument_list_or_closure, ?string $code_or_doc_comment = null, ?bool $return_by_reference = null, ?string $doc_comment = null, ?string $return_type = null, ?bool $is_strict = null): bool {}
function runkit7_function_remove(string $function_name): bool {}
function runkit7_function_rename(string $source_name, string $target_name): bool {}
/**
 * @param Closure|string $argument_list_or_closure
 * @param int|string|null $code_or_flags
 * @param int|string|null $flags_or_doc_comment
 */
function runkit7_method_add(string $class_name, string $method_name, $argument_list_or_closure, $code_or_flags = null, $flags_or_doc_comment = null, ?string $doc_comment = null, ?string $return_type = null, ?bool $is_strict = null): bool {}
function runkit7_method_copy(string $destination_class, string $destination_method_name, string $source_class, ?string $source_method_name = null): bool {}
/**
 * @param Closure|string $argument_list_or_closure
 * @param int|string|null $code_or_flags
 * @param int|string|null $flags_or_doc_comment
 */
function runkit7_method_redefine(string $class_name, string $method_name, $argument_list_or_closure, $code_or_flags = null, $flags_or_doc_comment = null, ?string $doc_comment = null, ?string $return_type = null, ?bool $is_strict = null): bool {}
function runkit7_method_remove(string $class_name, string $method_name): bool {}
function runkit7_method_rename(string $class_name, string $source_method_name, string $target_method_name): bool {}
/**
 * @param mixed $value
 */
function runkit7_zval_inspect($value): array {}
/**
 * @alias runkit7_constant_add
 * @param mixed $value
 * @deprecated
 */
function runkit_constant_add(string $constant_name, $value, ?int $new_visibility = RUNKIT7_ACC_PUBLIC): bool {}
/**
 * @param mixed $value
 * @alias runkit7_constant_redefine
 * @deprecated
 */
function runkit_constant_redefine(string $constant_name, $value, ?int $new_visibility = null): bool {}
/**
 * @alias runkit7_constant_remove
 * @deprecated
 */
function runkit_constant_remove(string $constant_name): bool {}
/**
 * @param Closure|string $argument_list_or_closure
 * @alias runkit7_function_add
 * @deprecated
 */
function runkit_function_add(string $function_name, $argument_list_or_closure, ?string $code_or_doc_comment = null, ?bool $return_by_reference = null, ?string $doc_comment = null, ?string $return_type = null, ?bool $is_strict = null): bool {}
/**
 * @alias runkit7_function_copy
 * @deprecated
 */
function runkit_function_copy(string $source_name, string $target_name): bool {}
/**
 * @param Closure|string $argument_list_or_closure
 * @alias runkit7_function_redefine
 * @deprecated
 */
function runkit_function_redefine(string $function_name, $argument_list_or_closure, ?string $code_or_doc_comment = null, ?bool $return_by_reference = null, ?string $doc_comment = null, ?string $return_type = null, ?bool $is_strict = null): bool {}
/**
 * @alias runkit7_function_remove
 * @deprecated
 */
function runkit_function_remove(string $function_name): bool {}
/**
 * @alias runkit7_function_rename
 * @deprecated
 */
function runkit_function_rename(string $source_name, string $target_name): bool {}

/**
 * @param Closure|string $argument_list_or_closure
 * @param int|string|null $code_or_flags
 * @param int|string|null $flags_or_doc_comment
 * @alias runkit7_method_add
 * @deprecated
 */
function runkit_method_add(string $class_name, string $method_name, $argument_list_or_closure, $code_or_flags = null, $flags_or_doc_comment = null, ?string $doc_comment = null, ?string $return_type = null, ?bool $is_strict = null): bool {}
/**
 * @param Closure|string $argument_list_or_closure
 * @alias runkit7_method_copy
 * @deprecated
 */
function runkit_method_copy(string $destination_class, string $destination_method_name, string $source_class, ?string $source_method_name = null): bool {}
/**
 * @param Closure|string $argument_list_or_closure
 * @param int|string|null $code_or_flags
 * @param int|string|null $flags_or_doc_comment
 * @alias runkit7_method_redefine
 * @deprecated
 */
function runkit_method_redefine(string $class_name, string $method_name, $argument_list_or_closure, $code_or_flags = null, $flags_or_doc_comment = null, ?string $doc_comment = null, ?string $return_type = null, ?bool $is_strict = null): bool {}
/**
 * @alias runkit7_method_remove
 * @deprecated
 */
function runkit_method_remove(string $class_name, string $method_name): bool {}
/**
 * @alias runkit7_method_rename
 * @deprecated
 */
function runkit_method_rename(string $class_name, string $source_method_name, string $target_method_name): bool {}
/**
 * @param mixed $value
 * @alias runkit7_zval_inspect
 * @deprecated
 */
function runkit_zval_inspect($value): array {}
#endif

#ifdef PHP_RUNKIT_SUPERGLOBALS
function runkit7_superglobals(): array {}
/**
 * @alias runkit7_superglobals
 * @deprecated
 */
function runkit_superglobals(): array {}
#endif
