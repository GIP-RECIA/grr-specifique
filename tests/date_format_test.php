<?php

require dirname(__DIR__) . '/include/config.inc.php.ori';

function assert_same($expected, $actual, $message)
{
	if ($expected !== $actual) {
		fwrite(STDERR, $message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
		exit(1);
	}
}

function intl_format($locale, $timezone, $pattern, $timestamp)
{
	$formatter = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, $timezone, IntlDateFormatter::GREGORIAN, $pattern);
	return $formatter->format($timestamp);
}

$timestamp = 1720962306; // 2024-07-14 13:05:06 UTC

$grr_locale = 'fr-FR';
$grr_timezone = 'Europe/Paris';
assert_same('2024-07-14 15:05:06', grr_strftime('%Y-%m-%d %T', $timestamp), 'Paris numeric format');
assert_same(intl_format($grr_locale, $grr_timezone, 'EEEE dd MMMM yyyy', $timestamp), grr_strftime('%A %d %B %Y', $timestamp), 'Paris localized format');
assert_same('0', grr_strftime('%w', $timestamp), 'Sunday weekday number');
assert_same('%', grr_strftime('%%', $timestamp), 'Literal percent sign');

$grr_locale = 'fr_RE';
$grr_timezone = 'Indian/Reunion';
assert_same('2024-07-14 17:05:06', grr_strftime('%Y-%m-%d %T', $timestamp), 'Reunion numeric format');
assert_same(intl_format($grr_locale, $grr_timezone, 'EEEE dd MMMM yyyy', $timestamp), grr_strftime('%A %d %B %Y', $timestamp), 'Reunion localized format');

$grr_locale = 'en-US';
assert_same(intl_format($grr_locale, $grr_timezone, 'EEEE, MMMM dd, yyyy', $timestamp), grr_strftime('%A, %B %d, %Y', $timestamp), 'English localized format');

fwrite(STDOUT, "Date format tests passed\n");
