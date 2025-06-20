This repository is a WordPress plugin snippet collection. We use PHP, JS, and CSS. Follow these rules:

## Folder structure

- Every PHP file is an entire WordPress plugin. They are categorized by domain, e.g. `acess` for administration, `compatibility` for compatibility with other plugins, `permalinks` for permalink management, `schema` for schema.org markup, etc. You may make custom folders as you see fit, or use existing ones to add new files.

## General Coding Standards

- WordPress coding standards, except as noted
- Use lowercase unit types, but write "Boolean" not "boolean"
- Single quotes for strings unless interpolating
- Interpolate variables in strings when possible
- Align array key/value separators with spaces before separator
- Trailing commas in multiline arrays/function args
- Pad brackets/braces with spaces around arguments
- Align consecutive variable assignments at equal signs
- Place multiline operators at new line start, also for conditional checks
- Put function args on a new line when >30 chars or for objects/arrays
- No braces in single-line constructs
- Write detailed docblocks for all functions, classes, and methods

## WordPress PHP

- Avoid wp_sprintf() (except with %l lists) and wp_json_encode()
- No hooks in class constructs
- In add_filter/action, each argument on a new line for anonymous functions

## PHP

- PHP 7.4+
- Short array syntax
- Never use strict typing unless required
- Namespace-escape these native functions outside global space: strlen, is_null, is_bool, is_long, is_int, is_integer, is_float, is_double, is_string, is_array, is_object, is_resource, is_scalar, boolval, intval, floatval, doubleval, strval, defined, chr, ord, call_user_func_array, call_user_func, in_array, count, sizeof, get_class, get_called_class, gettype, func_num_args, func_get_args, array_slice, array_key_exists, sprintf, constant, function_exists, is_callable, extension_loaded, dirname, define
- Namespace-escape all non-native function calls outside current namespace
- Namespace-escape constants from outside current namespace
- Short Echo Tags, HereDoc, NowDoc permitted
- Use (s|v)printf for complex strings with escaped variables
- Align array key/value separators with spaces before separator
- No padding array access strings with spaces
- Avoid output buffering

## JS

- ES6+
- No constant functions
- No JSX
- Apply PHP's spacing standards, including vertical alignment
- Use const instead of import

## Avoid

- Obvious comments
- Unnecessary variables unless for readability
- Regurgitating instructions
- Cruft

## Be

- Succinct
- Concise
- Matter of factly
