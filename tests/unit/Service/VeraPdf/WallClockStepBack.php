<?php

/**
 * A wall clock that can be made to step backwards inside the VeraPdf namespace.
 *
 * Unqualified microtime() calls in OCA\Filinq\Service\VeraPdf resolve to this
 * function. It returns the real time unless a test switches the step-back on,
 * in which case every call returns a time ten seconds earlier than the last.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\VeraPdf
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\VeraPdf;

/**
 * The real microtime(), or a clock stepping back by ten seconds per call while a test asks for it.
 *
 * @param bool $asFloat Return a float, as microtime(true) does.
 *
 * @return float|string The time.
 */
function microtime(bool $asFloat = false): float|string {
	if (($GLOBALS['filinqWallClockStepBack'] ?? null) === null) {
		return \microtime($asFloat);
	}

	$GLOBALS['filinqWallClockStepBack'] -= 10.0;

	return (float) $GLOBALS['filinqWallClockStepBack'];

}//end microtime()
