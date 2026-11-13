<?php

/**
 * A recording OCP\Util for the dashboard widget tests.
 *
 * The unit bootstrap does not load OCP\Util, and the real one needs a booted
 * server (translations and script ordering live in \OC). This records the
 * scripts a widget asks for, in order, so the test can assert them exactly.
 * It is only declared when no OCP\Util exists.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCP
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCP;

if (class_exists(Util::class, false) === false) {
	/**
	 * Records addScript() calls.
	 */
	class Util {

		/**
		 * The recorded calls: [application, file, afterAppId, prepend].
		 *
		 * @var array<int, array{0: string, 1: string|null, 2: string, 3: bool}>
		 */
		public static array $recordedScripts = [];

		/**
		 * Record a script request.
		 *
		 * @param string      $application The app id.
		 * @param string|null $file        The script name.
		 * @param string      $afterAppId  The app it loads after.
		 * @param bool        $prepend     Whether it is prepended.
		 *
		 * @return void
		 */
		public static function addScript(string $application, ?string $file = null, string $afterAppId = 'core', bool $prepend = false): void {
			self::$recordedScripts[] = [$application, $file, $afterAppId, $prepend];

		}//end addScript()
	}//end class
}//end if
