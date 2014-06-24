<?php

$rootDir = __DIR__ . '/..';
$testsDir = __DIR__;

if (getenv('NETTE') !== 'default') {
	$composerFile = $testsDir . '/composer-' . getenv('NETTE') . '.json';

	unlink($rootDir . '/composer.json');
	copy($composerFile, $rootDir . '/composer.json');

	echo "Using tests/", basename($composerFile);

} else {
	echo "Using default composer.json";
}
