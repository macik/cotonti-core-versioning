<?php
/**
 * Version compare and Requirements checking utility
 *
 * @package API - Versioning
 * @copyright (c) Cotonti Team
 * @license https://github.com/Cotonti/Cotonti/blob/master/License.txt
 * @version 0.2.1
 */

defined('COT_CODE') or die('Wrong URL');

// i18n fallback if no localization is defined for this package
$L['req_no_target'] || $L['req_no_target'] = 'at least Target or Package Name required.';
$L['req_ext_notfound'] || $L['req_ext_notfound'] = 'could not find `{$ext}` extension';
// FIXME: should be more user readable format, see `ext_dependency_error` rc for example
$L['req_satisfied'] || $L['req_satisfied'] = 'Requirement is satisfied: {$req}';
$L['req_not_satisfied'] || $L['req_not_satisfied'] = 'Requirement is not satisfied: {$req}';
$L['req_not_valid'] || $L['req_not_valid'] = 'Can\'t check requirement ({$req}): {$error_msg}';

define('COT_VERSION_DELIMITERS', './-_');
define('COT_VERSION_WILDCARDS', 1);

/**
 * Checks all requirements defined for some Extension
 *
 * @param array $info Extension info array, from setup file header
 * @param bool $mute_err_msg (optional) Disable error messages firing
 * @param bool $mute_info_msg (optional) Disable success messages. Disabled by default.
 * @return boolean Result of check
 *
 * @see cot_infoget() from `API - Extensions` package
 * @uses cot_requirements_satisfied()
 */
function cot_check_requirements($info, $mute_err_msg = false, $mute_info_msg = false)
{
	foreach ($info as $key => $constraint)
	{
		if (strpos(trim($key), 'Requires') === 0)
		{
			list(, $package) = explode('_', $key, 2);
			$package = $package ?: 'Core';
			$package = strtolower($package);
			if (in_array($package, array('plugins','modules')))
			{ // old style requirements check
				$list = explode(',', $constraint);
				foreach ($list as $extname)
				{
					$extname = trim($extname);
					$satisfied = cot_requirements_satisfied(substr($package, 0, -1), '*', $extname);
					if (!$satisfied) break;
				}
			}
			else
			{ // new style constraints
				$check_installed = strpos($constraint, '?') === false;
				if (!$check_installed) $constraint = str_replace('?', '', $constraint);
				$satisfied = cot_requirements_satisfied($package, $constraint, null, $check_installed);
			}
			$requirement_str = " $package: {$info[$key]}";
			if ($satisfied === false)
			{
				$mute_err_msg || cot_error( cot_rc('req_not_satisfied', array('req'=>$requirement_str) ) );
			}
			elseif ($satisfied !== true)
			{ // get error with constraint
				$mute_err_msg || cot_message(cot_rc('req_not_valid', array('req'=>$requirement_str, 'error_msg' => $satisfied) ), 'warning');
			}
			else
			{
				$mute_info_msg || cot_message(cot_rc('req_satisfied', array('req'=>$requirement_str)), 'ok');
			}
			if ($satisfied !== true) return false; // #FIXME comment for test
		}
	}
	//return false; // #FIXME uncomment for test
	return true;
}


/**
 * Extension to native `phpversion()`. Returns PHP extension version
 * or uses extension related function to get version tag.
 * @param string $ext_name PHP extension name
 * @return mixed [string | FALSE | NULL] Returns Version tag if found,
 * FALSE if not found but extension is loaded
 * and NULL otherwise.
 */
function cot_phpversion($ext_name=null)
{
	if (!$ext_name) return phpversion(); // core PHP version

	$version = phpversion($ext_name);
	if ($version) return cot_find_version_tag($version);

	switch (strtolower($ext_name)) {
		case 'curl':
			$vinfo = curl_version();
			$version = $vinfo['version'];
			break;
		case 'gd':
			$vinfo = gd_info();
			$version = $vinfo['GD Version'];
			break;
		default:
			// no version found
			$version = extension_loaded($ext_name) ? false : null;
	}

	return $version ? cot_find_version_tag($version) : $version;
}

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
{
	global $cot_plugins_enabled, $cot_modules;

	$target_types = array('core','theme','admin_theme','module','plugin','system','extension');
	$target = strtolower($target);

	if (!$package_name)
	{
		// tries to extract `package_name` from `target`
		foreach ($target_types as $ttype) {
			$last_part = substr($target, -(strlen($ttype)));
			if ($last_part == $ttype)
			{
				$package_name = substr($target, 0, - (strlen($last_part) + 1) );
				$target = $last_part;
				break;
			}
			$last_part = '';
		}
		if (!$last_part && substr($target, 0, 4) == 'php_')
		{
			$target = 'php';
			$package_name = substr($target, 4);
		}
	}

	switch ($target)
	{
		case 'php': // PHP version
			$current_version = cot_phpversion($package_name);
			if (is_null($current_version)) return false; // ext not found
			break;
		case 'core': // Cotonti core
			$current_version = cot::$cfg['version'];
			break;
		case 'system': // Cotonti system modules, like admin
			$sql = $db->query("SELECT * FROM $db_core WHERE ct_lock = 1");
			while ($row = $sql->fetch())
			{
				if ($row['ct_code'] == $package_name)
				{
					$active = (bool) $row['ct_state'];
					$current_version = $row['ct_version'];
				}
			}
			$sql->closeCursor();
			if (!$current_version || ($ext_active && !$active)) return false;
			break;
		case 'admin_theme':
		case 'theme':
			$themefile = cot::$cfg['themes_dir'] . ($target == 'admin-theme' ? '/admin/' : '') . "/$package_name/$package_name.php";
			if (file_exists($themefile))
			{
				$themeinfo = cot_infoget($themefile, 'COT_THEME');
				$current_version = $themeinfo['Version'];
				if (!$current_version) $themeinfo = cot_file_phpdoc($themefile);
				$current_version = $themeinfo['version'];
			}
			else
			{
				return false;
			}
			if ($target != 'theme')
			{
				$active = cot::$cfg['admintheme'] == $package_name;
			}
			else
			{
				$active = cot::$cfg['forcedefaulttheme'] && cot::$cfg['defaulttheme'] == $package_name;
			}
			if ($ext_active && !$active) return false;
			break;
		case 'plugin':
		case 'module':
		case 'extension': // either plugin or module
		default:
			if (!$target)
			{
				$target = 'extension';
			}
			elseif (!$package_name)
			{
				$package_name = $target;
				$target = 'extension';
			}
			if (!$package_name) return cot_rc('req_no_target');
			if ($ext_active)
			{
				if ($target == 'extension' || $target == 'module')
				{
					$active = $current_version = $cot_modules[$package_name]['version'];
				}
				if ($target == 'extension' || $target == 'plugin')
				{
					$active || $active = $current_version = $cot_plugins_enabled[$package_name]['version'];
				}
				if (!$active) return false;
			}
			else
			{
				// checking version from not installed Extension
				$setup_file = cot::$cfg['modules_dir'] . "/$package_name/$package_name.setup.php";
				if ($target == 'extension' || $target == 'module')
				{
					is_file($setup_file) && $info = cot_infoget($setup_file);
				}
				if ($target == 'extension' || $target == 'plugin')
				{
					$info || (is_file($setup_file) && $info = cot_infoget($setup_file));
				}
				if (!$info) return cot_rc('req_ext_notfound', array('ext' => $package_name));
				$current_version = $info['Version'];
			}
			//end of default
	}

	$meet = cot_version_constraint($current_version, $constraint);
	// as we can get boolean (true | false) or integer (-1 | 0 | 1) respect
	// to operator presence in constraint, we need to change integer to boolean.
	if (is_int($meet)) $meet = $meet == 1 ? true : false;
	return $meet;
}


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
{
	$found = preg_match_all(
		'/
			\b(?:(?:v)?
			(?<original>
				(?:(?<!\.)
				0*(?<major>0|(?:[1-9][0-9]*))
					(?:(?<delim>['.preg_quote(COT_VERSION_DELIMITERS, '/').'])
					0*(?<minor>0|(?:[1-9][0-9]*)))?
					(?:(?:\3) 	## reference to first delimiter
					0*(?<patch>0|(?:[1-9][0-9]*)))?
				)
				(?:
					(?<tagdelim>[-\.]|\3)?
					(?<tag>(?:[a-z\d][a-z\.\d-]*))?
				)?
			)
			(?:\+(?<meta>[^\s\*]+))?
		)\b
		/ix', $string, $found_tags, PREG_SET_ORDER);
		if (!$found) return false;
	if (sizeof($found_tags) == 1)
	{
		$tags = $found_tags[0];
	}
	else
	{
		// search best match among founded, more complex is prefered
		$counts = array();
		foreach ($found_tags as $k => $tags_arr)
		{
			$counts[$k] = count(array_filter($tags_arr, function ($x) {return !empty($x);}));
		}

		$tags = $found_tags[array_search(max($counts), $counts)];
	}
	if (!$expand)
	{
		return $tags['original'];
	}
	else
	{
		if (!$tags['minor']) $tags['minor'] = '0';
		if (!$tags['patch']) $tags['patch'] = '0';
		$tags['tagdelim'] = !$tags['tag'] ? '' : ($tags['tagdelim'] ?  : '-');
		if ($tags['tag']) $tags['tag'] = preg_replace('/(?|([a-z])(\d)|(\d)([a-z]))/i', '$1.$2', $tags['tag']);

		return cot_rc('{$major}.{$minor}.{$patch}{$tagdelim}{$tag}', $tags);
	}
}


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
{
	// predefined formats for further parsing with cot_rc()
	$formats = array(
		'array' 	=> 'array', // return array instead of formatted string
		'arr2str'	=> '{$operator}{$vtag}{$major}{$delim}{$minor}{$delim}{$patch}{$tagdelim}{$tag}{$meta}',
		'constraint'=> '{$operator}{$major}.{$minor}.{$patch}{$tagdelim}{$tag}{$meta}',
		'full' 		=> '{$major}.{$minor}.{$patch}{$tagdelim}{$tag}{$meta}',
		'default' 	=> '{$major}.{$minor}.{$patch}{$tagdelim}{$tag}',
		'version' 	=> '{$major}.{$minor}.{$patch}',
		'operator' 	=> '{$operator}',
		'vtag' 		=> '{$vtag}',
		'delim' 	=> '{$delim}',
		'major' 	=> '{$major}',
		'minor' 	=> '{$minor}',
		'patch' 	=> '{$patch}',
		'tag' 		=> '{$tag}',
		'unparsed' 	=> '{$unparsed}', // source version string without junk
		'meta' 		=> '{$meta}',
	);

	$version_filter_regexp = '/
		^\s*(?<operator>gt|ge|lt|le|eq|ne|=|>|>=|<|<=|!=)?
		\s*(?<vtag>v|ver|version|r|rel|release)?\s*
		(?:
			(?<unparsed>
				0*(?<major>0|(?:[1-9][0-9]*|(?<!\d)\*)) 	## allow number or wildcard
				(?:(?<delim>['.preg_quote(COT_VERSION_DELIMITERS, '/').'])
				0*(?<minor>0|(?:[1-9][0-9]*|(?<!\d)\*)))?
				(?:(?:\5) 									## reference to first delimiter
				0*(?<patch>0|(?:[1-9][0-9]*|(?<!\d)\*)))?
			)
			(?:
				(?:[-\.]|\5)?
				(?<tag>(?:[\d]?|[a-z\d]?[a-z\.\d-]+))?
			)?
		)
		(?:\+(?<meta>[^\s\*]+))?
		\s*$
	/ix';

	if (array_key_exists($format, $formats)) $format = $formats[$format];
	if (!$format) $format = $formats['default'];

	if (is_array($version))
	{
		$version['tagdelim'] = !$version['tag'] ? '' : ($version['tagdelim'] ?: '-');
		if ($version['meta']) $version['meta'] = '+'.$version['meta'];
		$version = cot_rc($formats['arr2str'], $version);
	}

	/**
	 * @var array $tags Parsing data
	 */
	$parsed = preg_match($version_filter_regexp, $version, $tags);
	if (!$parsed) return false;
	if (in_array('*', $tags) && !$wildcards) return false;

	if (!$tags['minor']) $tags['minor'] = '0';
	if (!$tags['patch']) $tags['patch'] = '0';
	$tags['tagdelim'] = !$tags['tag'] ? '' : ($tags['tagdelim'] ?  : '-');

	if ($tags['tag']) $tags['tag'] = preg_replace('/(?|([a-z])(\d)|(\d)([a-z]))/i', '$1.$2', $tags['tag']);

	// clean numeric indexes
	foreach ($tags as $key => $value)
	{
		if (is_numeric($key)) unset($tags[$key]);
	}

	$res = ($format == 'array') ? $tags : cot_rc($format, $tags);

	return $res;
}

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
{
	$constraint .= $compared_version;
	$constraint = trim($constraint);

	if ($constraint == '*' || !$constraint) return true; // assume we allow any version

	$base_version = cot_version_parse($base_version);
	if (!$base_version) return null; // base version should be proper defined
	$base_array = cot_version_parse($base_version, 'array');

	// get version from constraint, allowing wildcards
	$version = cot_version_parse($constraint, 'default', true);
	if ($version)
	{
		$version_array = cot_version_parse($constraint, 'array', true);
		$operator = $version_array['operator'];

		if (!in_array('*', $version_array))
		{
			// no wildcard, just do compare
			return $operator ? cot_version_compare($base_version, $version, $operator) : cot_version_compare($base_version, $version);
		}
		else
		{
			// otherwise try expanding wildcard
			if ($operator) return null; // we can not resolve wildcard with operator
			foreach (array('major', 'minor', 'patch') as $key)
			{
				if ($version_array[$key] == '*') $wildcard = true;
				if ($wildcard) $version_array[$key] = $base_array[$key];
			}
			$version = cot_version_parse($version_array, 'version');
			$base = cot_version_parse($base_version, 'version');
			return cot_version_compare($base, $version, '=');
		}
	}
	else
	{
		// #TODO add support for (~,^,-) operators and parsing complex constraints
		// version constraint can not be parsed now, so
		return null;
	}
}

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
{
	$stable_mark = 'stable';
	$tags_expand = array('b' => 'beta', 'a' => 'alpha', 'p' => 'pl');
	$classes_order = array('','_digit','_any','dev','alpha','beta','rc',$stable_mark,'pl');

	$tag1 = cot_version_parse($version1);
	$tag1 = explode('.', str_replace(str_split('-_'), '.', $tag1));

	$tag2 = cot_version_parse($version2);
	$tag2 = explode('.', str_replace(str_split('-_'), '.', $tag2));

	$size = max(sizeof($tag1), sizeof($tag2));

	$cmp_result = '=';
	for ($i = 0; $i < $size; $i ++)
	{
		$v1 = strtolower($tag1[$i]);
		$v2 = strtolower($tag2[$i]);

		if ($i > 2)
		{ // processing release tag part
			if ($v1 === '' && $i == 3) $v1 = $stable_mark;
			if (array_key_exists($v1, $tags_expand)) $v1 = $tags_expand[$v1];
			$cmp_class1 = preg_match('/\d+/', $v1) ? 1 :
							(in_array($v1, $classes_order, 1) ? array_search($v1, $classes_order) : 2);


			if ($v2 === '' && $i == 3) $v2 = $stable_mark;
			if (array_key_exists($v2, $tags_expand)) $v2 = $tags_expand[$v2];
			$cmp_class2 = preg_match('/\d+/', $v2) ? 1 :
							(in_array($v2, $classes_order, 1) ? array_search($v2, $classes_order) : 2);

			if ($cmp_class1 != $cmp_class2) {
				$v1 = $cmp_class1;
				$v2 = $cmp_class2;
			}
		}

		if ($v1 < $v2)
		{
			$cmp_result = '<';
			break;
		}
		if ($v1 > $v2)
		{
			$cmp_result = '>';
			break;
		}
	}

	if ($operator)
	{
		$operator = str_replace(
			array('gt','ge','lt','le','eq','ne','!='),
			array('>' ,'>=','<' ,'<=','=' ,'<>','<>'),
			strtolower($operator)
		);

		return strpos($operator, $cmp_result) !== false;
	}
	else
	{
		if ($cmp_result == '<') return -1;
		if ($cmp_result == '=') return 0;
		if ($cmp_result == '>') return 1;
	}
}

