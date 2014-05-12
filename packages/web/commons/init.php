<?php
/****************************************************
 * FOG Initialization
 *	Author:		$Author$	
 *	Created:	3:15 PM 1/05/2011
 *	Revision:	$Revision$
 *	Last Update:	$LastChangedDate$
 ***/
// Init
set_time_limit(0);
@error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
@header('Cache-Control: no-cache');
session_cache_limiter('no-cache');
session_start();
@set_magic_quotes_runtime(0);
// PHP Version Check
if (!version_compare(phpversion(), '5.2.1', '>='))
	die('FOG Requires PHP v5.2.1. You have PHP v' . phpversion());
// Module check
$requiredExtenstions = array('gettext'); // , 'curl'
foreach ($requiredExtenstions as $extenstion)
{
	if (!in_array($extenstion, get_loaded_extensions()))
		$missingExtensions[] = $extenstion;
}
if (count((array)$missingExtensions))
	die('Missing Extenstions: ' . implode(', ', $missingExtensions));
// Sanitize valid input variables
foreach (array('node','id','sub','snapinid','userid','storagegroupid','storagenodeid','crit','sort', 'userid', 'confirm', 'tab') AS $x)
	$$x = (isset($_REQUEST[$x]) ? addslashes($_REQUEST[$x]) : '');
unset($x);
// Auto Loader
spl_autoload_register(function ($className) 
{
	$paths = array(BASEPATH . '/lib/fog', BASEPATH . '/lib/db', BASEPATH . '/lib/pages');
	foreach ($paths as $path)
	{
		$fileName = $className . '.class.php';
		$filePath = rtrim($path, '/') . '/' . $fileName;
		if (!class_exists($className) && file_exists($filePath))
			include($filePath);
	}
});
// Core
$FOGFTP = new FOGFTP();
$FOGCore = new FOGCore();
// Hook Manager - Init & Load Hooks
$HookManager = new HookManager();
// Locale
if ($_SESSION['locale'])
{
	putenv('LC_ALL='.$_SESSION['locale']);
	setlocale(LC_ALL, $_SESSION['locale']);
}
// Languages
bindtextdomain('messages', 'languages');
textdomain('messages');
