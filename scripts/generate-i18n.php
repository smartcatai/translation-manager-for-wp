<?php
/**
 * Smartcat Translation Manager for WordPress
 *
 * @package Smartcat Translation Manager for WordPress
 * @author Smartcat <support@smartcat.ai>
 * @copyright (c) 2019 Smartcat. All Rights Reserved.
 * @license GNU General Public License version 3 or later; see LICENSE.txt
 * @link http://smartcat.ai
 */

function glob_recursive($pattern, $flags = 0, $exclude = [])
{
	$files = glob($pattern, $flags);
	foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir)
	{
		if ( in_array( $dir, $exclude ) ) {
			continue;
		}
		$files = array_merge($files, glob_recursive($dir.'/'.basename($pattern), $flags, $exclude));
	}
	return $files;
}

$files = glob_recursive('*.php', 0, ['./inc/vendor']);

$global_matches = [];

foreach ( $files as $file ) {
	$matches = [];
	$content = file_get_contents( $file );
	preg_match_all( '/__\(.*?\'(.*?)\',.*?\)/', $content, $matches );
	if ( ! empty( $matches[1] ) ) {
		$global_matches = array_merge($global_matches, $matches[1]);
	}
}

$global_matches = array_unique( $global_matches );

$file_header = <<<HEADER
# Smartcat Translation Manager for WordPress.
# Copyright (C) 2019 Smartcat. All Rights Reserved.
# This file is distributed under the same license as the PACKAGE package.
# Smartcat <support@smartcat.ai>, 2019.
#
msgid ""
msgstr ""
"Project-Id-Version: 2.0.0\\n"
"Report-Msgid-Bugs-To: \\n"
"POT-Creation-Date: 2017-08-11 09:31+0300\\n"
"PO-Revision-Date: 2017-08-14 09:31+0300\\n"
"Last-Translator: Smartcat <support@smartcat.ai>, 2019\\n"
"Language-Team: Smartcat <support@smartcat.ai>\\n"
"Language: Russian\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
HEADER;

file_put_contents( "translation_connectors-ru_RU.pot", $file_header . PHP_EOL . PHP_EOL );

foreach ( $global_matches as $string ) {
	$string = str_replace('"', '\"', $string);
	file_put_contents( "translation_connectors-ru_RU.pot", "msgid \"{$string}\"" . PHP_EOL, FILE_APPEND );
	file_put_contents( "translation_connectors-ru_RU.pot", 'msgstr ""' . PHP_EOL . PHP_EOL, FILE_APPEND );
}
