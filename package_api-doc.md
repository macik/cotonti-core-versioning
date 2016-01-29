Versioning package API
======================

It's a copy of package PHPDoc.

API:
----

### List for package functions: ###

* **cot_check_requirements()** — checks all requirements defined for some Extension;
* **cot_requirements_satisfied()** — checks if requirements for installed/used package is satisfied.
* **cot_find_version_tag()** — parses string and find tag more applicable as version information;
* **cot_version_parse()** — parses version string and format it with predefined or custom mask;
* **cot_version_constraint()** — tests version against some constraint. Some kind alternative for native version_compare() with flexible arguments support and wider range of possible values and constraints;
* **cot_version_compare()** — extended version of the native `version_compare()` function, but more accurate with pre-release tag comparison;
* **cot_phpversion()** — extension to native `phpversion()`. Returns PHP extension version or uses extension related function to get version tag.

### Function details: ###

```php
define('COT_VERSION_DELIMITERS', './-_');
```
List of valid version parts delimiters (e.g. Major, Minor, Patch).

#### cot_check_requirements()
```php
/**
 * Checks all requirements defined for some Extension
 *
 * @param array $info Extension info array, from setup file header
 * @param bool $mute_errors (optional) Disable error messages firing
 * @param bool $mute_messages (optional) Disable success messages. Disabled by default.
 * @return boolean Result of check
 *
 * @see cot_infoget() from `API - Extensions` package
 * @uses cot_requirements_satisfied()
 */
function cot_check_requirements($info, $mute_errors = false, $mute_messages = false)
```

#### cot_phpversion()
```php
/**
 * Extension to native `phpversion()`. Returns PHP extension version
 * or uses extension related function to get version tag.
 * @param string $ext_name PHP extension name
 * @return mixed [string | FALSE | NULL] Returns Version tag if found,
 * FALSE if not found but extension is loaded
 * and NULL otherwise.
 */
function cot_phpversion($ext_name=null)
```

#### cot_requirements_satisfied()
```php
/**
 * Checks if requirements for installed/used package is satisfied
 * Returns boolean if checking is done or Error message otherwise
 *
 * @param string $target Target (case insensitive). Could be:
 * 	php 		- PHP version or PHP extensions
 * 	core 		- for checking Cotonti (core) version
 * 	system 		- Cotonti system modules (like Admin)
 * 	theme 		- for themes
 * 	plugin 		- for plugin check
 * 	module 		- for module check
 * 	extension	- for checking among both types: plugins and modules
 * Can be in complex notation:
 * 	php_curl 	- for certain php module `curl`
 * 	pfs_module 	- for certain Cotonti module (`PFS`)
 * 	comments_plugin 	- for certain Cotonti plugin (`comments`)
 *
 * All other Target types treats as Cotonti Extension name, see examples below.
 * @param string $constraint Version string or version constraint
 * @param string $package_name (optional) Name of package for checking if not defined in type
 * @param string $ext_active (optional) Requirement to check is Extension installed, `true` by default
 *
 * @return bool | string Result of check or Error message
 * **Note:** result should be checked with strait comparison with === / !== operators,
 *
 *	Examples of use:
 *		cot_requirements_satisfied('core','0.9.19'); // Cotonti version check
 *		cot_requirements_satisfied('PHP','5.4.1'); // Checking for PHP version
 *		cot_requirements_satisfied('PHP','7.0','curl'); // Checking for PHP extension version
 *		cot_requirements_satisfied('plugin','1.2','comments');
 *		cot_requirements_satisfied('module','1.1.3','page');
 *		cot_requirements_satisfied('theme','*','skeletonti'); // any version allowed
 *			// installed theme treats as Default Cotonti theme
 *		cot_requirements_satisfied('module','0.7.15','pfs', false);
 *			// checking Extension is in the system but not is it installed
 *		cot_requirements_satisfied('extension','0.9.18','somename'); // checking among plugins and modules
 *		cot_requirements_satisfied('page_module','0.9.18');
 *			// shorthand for → 'module', 0.9.18, 'page'
 *		cot_requirements_satisfied('test','0.9.18'); → 'extension', 0.9.18, 'test'
 *
 * @uses function cot_version_compare()
 */

function cot_requirements_satisfied($target, $constraint, $package_name = null, $ext_active = true)
```

#### cot_find_version_tag()

```php
/**
 * Parses string and find tag more applicable as version information.
 * If found several version tags most comprehensive will choosen.
 * @param string $string Source string for search
 * @param bool $expand Enable expanding short notation to commonly used `x.y.z-tag`
 * @return string | FALSE Result version strings of FALSE if can not be found
 *
 * Examples:
 * 		`mysqlnd 5.0.10 - 20111026` → `5.0.10`
 * 		`bundled (2.1.0 compatible)` → `2.1.0`
 * 		`Release 1.2 version 1.5.7` → `1.5.7`
 * 		`v1.4` → `1.4.0`  // as expanding enabled
 *
 * @uses function cot_rc()
 */
function cot_find_version_tag($string, $expand = true)
```

#### cot_version_parse()
```php
/**
 * Parses version string and format it with predefined or custom mask
 *
 * @param string|array $version String with version number or array with
 * version data (see $tags variable inside function)
 * @param string $format Format for parsed data. Can be one of predefined or
 * user custom mask. If string 'array' is passed as format name — function will
 * return array of version data instead string
 * @return string|FALSE Formated version string or false if can not be parsed
 *
 * Examples parsed with `default` format:
 *		0.1-RC.1+build.meta.info 	→ 0.1.0-RC.1
 *		2-4-13.dd 					→ 2.4.13-dd
 *		1.1.0b1 					→ 1.1.0-b.0
 *		1.5RC7 						→ 1.5.0-RC.7
 *		10b 						→ 10.0.0-b
 *		10.1 						→ 10.1.0
 *		ver 17.1.5 					→ 17.1.5
 *		0/1/4/beta 					→ 0.1.4-beta
 *		=10.1 						→ 10.1.0
 *		>= v1 						→ 1.0.0
 *		0.00.001.beta1 				→ 0.0.1-beta.1
 *		010.01.53.64 				→ 10.1.53-64
 *
 * @uses function cot_rc()
 */
function cot_version_parse($version, $format = 'default', $wildcards = false)
```

#### cot_version_constraint()
```php
/**
 * Tests version against some constraint. Some kind alternative for
 * native version_compare() with flexible arguments support and wider
 * range of possible values and constraints.
 *
 *   Features:
 *   - version expanded to `x.y.z-tag` format before comparison, so `1` == `1.0.0`
 *   - allow wildcard in constraints, like 1.0.* or 2.*
 *   - compares release tags with semantic versioning rules

 * @param string $base_version Version, constraint compared to. Not allowing wildcards.
 * 		Must be defined, except `*` gets as a constraint (see examples).
 * @param string $constraint Version number, operator or basic constraint.
 * 	Could be in these formats:
 * 		`0.2.3`, `1`, `1.0.*`  — version numbers (wildcards allowed)
 * 		=, >, >=, <, <=, !=, gt, ge, lt, le, eq, ne - allowed operators
 * 		`>=1.0.0`, `ne 0.5`, `<1.0` - basic constraints
 * @param string $compared_version Version number for cases operator defined
 * @return mixed By default as native version_compare() returns. In case
 * of invalid arguments provided will return NULL
 *
 *	Examples:
 * cot_version_constraint('1.0.0','=','1') // true, as differ from version_compare()
 * cot_version_constraint('0.9.18','')  true as no
 * cot_version_constraint('','*')  true as we allow any version
 * cot_version_constraint('','0.0.1')  null as base version not defined, but we try to compare
 * cot_version_constraint('1.1.2','1.1.*')  true as we allow any patch
 * cot_version_constraint('0.9.18','>=0.9.*')// null as improper constraint
 * cot_version_constraint('0.9.18','~0.9')// null as range operators (`~`,`^`,` - `) not yet supported
 *
 * @uses cot_version_compare()
 */
function cot_version_constraint($base_version, $constraint, $compared_version = null)
```

#### cot_version_compare()
```php
/**
 * Extended version of the native `version_compare()` function.
 * More accurate with pre-release tag comparison:
 *   - version expanded to `x.y.z-tag` format before comparison, so `1` == `1.0.0`
 *   - more semver friendly, thus  `1.0.0-alpha.1` < `1.0.0-alpha.beta`
 *
 * @param string $version1 First version string
 * @param string $version2 Secont version string
 * @param string (optional) $operator
 * <br />
 *  Example: 1.0.0-anystr < 1.0.0-dev < 1.0.0-alpha < 1.0.0-alpha.1 < 1.0.0-alpha.beta
 *  < 1.0.0-beta < 1.0.0-beta.2 < 1.0.0-beta.11 < 1.0.0-rc.1 < 1.0.0 < 1.0.0-p.1 < 1.0.1-dev
 *
 * @see version_compare()
 * @link http://semver.org/
 */
function cot_version_compare($version1, $version2, $operator = null)
```



