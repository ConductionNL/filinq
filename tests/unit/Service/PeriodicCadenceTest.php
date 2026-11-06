<?php

/**
 * When a periodic document is due (periodic-documents-on-a-schedule D1).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use DateTimeImmutable;
use OCA\Filinq\Service\PeriodicCadence;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Every cadence, the first run and on demand.
 */
class PeriodicCadenceTest extends TestCase {

	/**
	 * Cadence, last run, now, due.
	 *
	 * @return array<string, array{0: string, 1: string|null, 2: string, 3: bool}>
	 */
	public static function cases(): array {
		return [
			'never ran, weekly' => ['weekly', null, '2026-09-28T10:00:00+00:00', true],
			'never ran, on demand' => ['onDemand', null, '2026-09-28T10:00:00+00:00', false],
			'on demand long ago' => ['onDemand', '2020-01-01T00:00:00+00:00', '2026-09-28T10:00:00+00:00', false],
			'daily, 23 hours' => ['daily', '2026-09-27T11:00:00+00:00', '2026-09-28T10:00:00+00:00', false],
			'daily, 24 hours' => ['daily', '2026-09-27T10:00:00+00:00', '2026-09-28T10:00:00+00:00', true],
			'weekly, six days' => ['weekly', '2026-09-22T10:00:00+00:00', '2026-09-28T10:00:00+00:00', false],
			'weekly, seven days' => ['weekly', '2026-09-21T10:00:00+00:00', '2026-09-28T10:00:00+00:00', true],
			'monthly, same day next month' => ['monthly', '2026-08-28T10:00:00+00:00', '2026-09-28T10:00:00+00:00', true],
			'monthly, a day short' => ['monthly', '2026-08-29T10:00:00+00:00', '2026-09-28T10:00:00+00:00', false],
			'quarterly, two months' => ['quarterly', '2026-07-28T10:00:00+00:00', '2026-09-28T10:00:00+00:00', false],
			'quarterly, three months' => ['quarterly', '2026-06-28T10:00:00+00:00', '2026-09-28T10:00:00+00:00', true],
			'no cadence means weekly' => ['', '2026-09-21T10:00:00+00:00', '2026-09-28T10:00:00+00:00', true],
			'unknown cadence never runs' => ['hourly', null, '2026-09-28T10:00:00+00:00', false],
			'unreadable last run is due' => ['weekly', 'last tuesday-ish', '2026-09-28T10:00:00+00:00', true],
		];
	}

	#[DataProvider('cases')]
	public function testIsDue(string $cadence, ?string $lastRunAt, string $now, bool $due): void {
		$this->assertSame($due, (new PeriodicCadence())->isDue($cadence, $lastRunAt, new DateTimeImmutable($now)));
	}
}
