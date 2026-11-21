<?php

/**
 * Periodic Cadence
 *
 * Whether a periodic document is due, from its cadence and its last run.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-creatie-sjablonen/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateInterval;
use DateTimeImmutable;
use Exception;

/**
 * The due rule, pure: no clock of its own, no storage.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-creatie-sjablonen/spec.md
 */
class PeriodicCadence {

	/**
	 * The period of each cadence that runs by itself.
	 *
	 * @var array<string, string>
	 */
	private const PERIODS = [
		'daily' => 'P1D',
		'weekly' => 'P7D',
		'monthly' => 'P1M',
		'quarterly' => 'P3M',
	];

	/**
	 * Whether the schedule is due at the given moment.
	 *
	 * 🔑 AN UNREADABLE LAST RUN IS DUE. A schedule whose `lastRunAt` cannot be
	 * parsed would otherwise never run again, and a periodic document that
	 * silently stops is the failure this job exists to prevent. Running once
	 * writes a readable `lastRunAt`, so it cannot loop.
	 *
	 * @param string            $cadence   The schedule's cadence; empty means the schema default, weekly.
	 * @param string|null       $lastRunAt The last successful run (ISO 8601), or null.
	 * @param DateTimeImmutable $now       The moment to judge at.
	 *
	 * @return bool True when the cadence has come round since the last run.
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function isDue(string $cadence, ?string $lastRunAt, DateTimeImmutable $now): bool {
		if ($cadence === '') {
			$cadence = 'weekly';
		}

		if (isset(self::PERIODS[$cadence]) === false) {
			return false;
		}

		if ($lastRunAt === null || trim($lastRunAt) === '') {
			return true;
		}

		try {
			$last = new DateTimeImmutable($lastRunAt);
		} catch (Exception) {
			return true;
		}

		return $last->add(new DateInterval(self::PERIODS[$cadence])) <= $now;

	}//end isDue()
}//end class
