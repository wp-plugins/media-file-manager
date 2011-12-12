<?php
if (!isset($_SERVER['DOCUMENT_ROOT'])) $_SERVER['DOCUMENT_ROOT'] = substr($_SERVER['SCRIPT_FILENAME'], 0, 0-strlen($_SERVER['SCRIPT_NAME']) );
$svpathflag = false;
if (strstr($_SERVER['DOCUMENT_ROOT'],"\\\\")==$_SERVER['DOCUMENT_ROOT'] || strstr($_SERVER['DOCUMENT_ROOT'],"//")==$_SERVER['DOCUMENT_ROOT']) {$svpathflag = true;}
$_SERVER['DOCUMENT_ROOT'] = str_replace('\\','/',$_SERVER['DOCUMENT_ROOT']);
$_SERVER['DOCUMENT_ROOT'] = str_replace('//','/',$_SERVER['DOCUMENT_ROOT']);
if ($svpathflag) {
	if (strstr($_SERVER['DOCUMENT_ROOT'], "//") === FALSE) {
		$_SERVER['DOCUMENT_ROOT'] = "/" . $_SERVER['DOCUMENT_ROOT'];
	}
}
?>