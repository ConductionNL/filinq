<?php

/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

define('PHPUNIT_RUN', 1);
require_once __DIR__ . '/../vendor/autoload.php';

spl_autoload_register(function ($class) {
	if (strpos($class, 'OCP\\') === 0) {
		$rel = str_replace(['OCP\\', '\\'], ['', '/'], $class);
		$file = '/srv/nextcloud/lib/public/' . $rel . '.php';
		if (file_exists($file)) {
			require_once $file;
		}
	} elseif (strpos($class, 'OC\\') === 0) {
		$rel = str_replace(['OC\\', '\\'], ['', '/'], $class);
		$file = '/srv/nextcloud/lib/private/' . $rel . '.php';
		if (file_exists($file)) {
			require_once $file;
		}
	}
});

require_once __DIR__ . '/stubs/OpenRegisterStubs.php';
