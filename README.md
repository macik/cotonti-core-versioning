Cotonti: Versioning package
===========================

:ru: Russian doc is [here](README_ru.md)

> This Cotonti CMF package proposed to be included in core in future versions as base for package requirements checking. Modify of `Extensions API` and `extensions` part of Admin module will be required.

Package provide pack of function to easy check versions constraints
and extensions requirements for Cotonti.


Preface
-------

As for now (Cotonti Siena 0.9.18) Extensions API provide ability to define
requirings and recommendations for installed Extensions. But there are no 
version check as version definition ability.

Also there are no way to define minimal required Core version or check for required PHP 
extensions.

This package aim is to eliminate this gap. 


Features:
---------

* Easy version comparison ([SemVer](http://semver.org/) style)
* Allowing wildcards constraints
* Version parsing and normalization 
* Ability to check certain Extension requirements
* Can check Cotonti Core, Modules, Plugins, Themes for being installed and version
* Also allow to check PHP and its extensions versions

API:
----

Package structure is common for Cotonti CMF core packages — it's a single `versioning.php` file, provides list of a utility functions.

### List for package functions: ###

* **cot_check_requirements()** — checks all requirements defined for some Extension;
* **cot_requirements_satisfied()** — checks if requirements for installed/used package is satisfied;
* **cot_find_version_tag()** — parses string and find tag more applicable as version information;
* **cot_version_parse()** — parses version string and format it with predefined or custom mask;
* **cot_version_constraint()** — tests version against some constraint. Some kind alternative for native version_compare() with flexible arguments support and wider range of possible values and constraints;
* **cot_version_compare()** — extended version of the native `version_compare()` function, but more accurate with pre-release tag comparison;
* **cot_phpversion()** — extension to native `phpversion()`. Returns PHP extension version or uses extension related function to get version tag.

More info:
----------

See Package [API documentation](package_api-doc.md) for more info.


Related links:
--------------

* Semantic Versioning: [version 2.0.0](http://semver.org/)
* PHP: [version_compare()](http://php.net/manual/en/function.version-compare.php) documentation
* PHP: [phpversion()](http://php.net/manual/en/function.phpversion.php) documentation