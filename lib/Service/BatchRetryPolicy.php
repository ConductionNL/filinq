<?php

/**
 * Batch Retry Policy: which per-file failures a background batch retries in a later work unit.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/redaction-at-scale
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Exception\ConversionFailedException;
use OCP\Lock\LockedException;
use Throwable;

/**
 * The LibreOffice backend fails fast when another conversion holds its lock.
 * In a background batch that is contention, not a broken file: the file goes
 * back to the queue with its retry count raised, and becomes an error only
 * when the bounded retries are used up.
 *
 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-resource-guards-on-extractconvert-backends-req-ddras-008
 */
final class BatchRetryPolicy {

	/**
	 * How many times a lock miss is retried before the file is an error.
	 */
	public const MAX_RETRIES = 3;

	/**
	 * The failure group of a file whose retries ran out on lock misses.
	 */
	public const LOCK_CATEGORY = 'conversion-lock-timeout';

	/**
	 * The text the LibreOffice backend records on a lock miss.
	 */
	private const LOCK_MARKER = 'soffice:headless:convert lock';

	/**
	 * Whether a thrown failure is a conversion lock miss.
	 *
	 * @param Throwable $error The failure, with its previous chain.
	 *
	 * @return bool True when it, or anything it wraps, is a lock miss.
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-resource-guards-on-extractconvert-backends-req-ddras-008
	 */
	public function isLockMiss(Throwable $error): bool {
		for ($current = $error; $current !== null; $current = $current->getPrevious()) {
			if ($current instanceof LockedException) {
				return true;
			}

			if ($current instanceof ConversionFailedException
				&& $this->attemptsHoldLockMiss(attempts: $current->getAttempts()) === true
			) {
				return true;
			}
		}

		return false;

	}//end isLockMiss()

	/**
	 * Whether a failed file entry records a conversion lock miss.
	 *
	 * @param array<string, mixed> $entry The file entry.
	 *
	 * @return bool True when its recorded conversion attempts name the lock.
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-resource-guards-on-extractconvert-backends-req-ddras-008
	 */
	public function entryIsLockMiss(array $entry): bool {
		if (($entry['status'] ?? '') !== 'error') {
			return false;
		}

		return $this->attemptsHoldLockMiss(attempts: (array)($entry['conversionAttempts'] ?? []));

	}//end entryIsLockMiss()

	/**
	 * Put a file that hit a lock miss back in the queue, or fail it when its retries are used up.
	 *
	 * @param array<string, mixed> $entry       The file entry after the failed attempt.
	 * @param string               $queueStatus The status that queues it again (`uploaded` or `extracted`).
	 * @param string               $message     The failure message.
	 *
	 * @return array<string, mixed> The entry, queued again or failed with the lock category.
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-resource-guards-on-extractconvert-backends-req-ddras-008
	 */
	public function retryOrFail(array $entry, string $queueStatus, string $message): array {
		$retries = ((int)($entry['retryCount'] ?? 0) + 1);
		$entry['retryCount'] = $retries;
		if ($retries > self::MAX_RETRIES) {
			$entry['status'] = 'error';
			$entry['error'] = $message;
			$entry['errorCategory'] = self::LOCK_CATEGORY;
			return $entry;
		}

		$entry['status'] = $queueStatus;
		$entry['error'] = null;
		unset($entry['conversionAttempts']);
		return $entry;

	}//end retryOrFail()

	/**
	 * Whether any recorded conversion attempt names the lock.
	 *
	 * @param array<int|string, mixed> $attempts The attempts.
	 *
	 * @return bool
	 */
	private function attemptsHoldLockMiss(array $attempts): bool {
		foreach ($attempts as $attempt) {
			if (is_array($attempt) === true && str_contains((string)($attempt['reason'] ?? ''), self::LOCK_MARKER) === true) {
				return true;
			}
		}

		return false;

	}//end attemptsHoldLockMiss()
}//end class
