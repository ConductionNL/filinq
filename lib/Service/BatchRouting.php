<?php

/**
 * Batch Routing: which path (in-request or background) a batch of a given size takes.
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

use Exception;
use OCP\IAppConfig;

/**
 * Two caps decide a batch's path: up to the synchronous cap
 * (`batch.max_files_per_run`, default 100) a batch is processed in-request
 * as before; above it, up to the background cap
 * (`batch.max_files_background`, default 1000), it is processed in work
 * units by the cron coordinator; above that it is refused. The engine
 * switch `batch.engine` (`background` default, `legacy-sync` kill switch)
 * restores the old behaviour, in which the synchronous cap is the only cap.
 *
 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-resource-guards-on-extractconvert-backends-req-ddras-008
 */
class BatchRouting {

	/**
	 * The batch is processed in the request that started it.
	 */
	public const SYNC = 'sync';

	/**
	 * The batch is processed in background work units.
	 */
	public const BACKGROUND = 'background';

	/**
	 * The engine value that switches the background path off.
	 */
	public const ENGINE_LEGACY = 'legacy-sync';

	/**
	 * The default background cap.
	 */
	public const DEFAULT_BACKGROUND_CAP = 1000;

	/**
	 * Constructor.
	 *
	 * @param BatchStateService $stateService The batch store, which owns the synchronous cap.
	 * @param IAppConfig        $appConfig    App config for the background cap and the engine switch.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly BatchStateService $stateService,
		private readonly IAppConfig $appConfig,
	) {

	}//end __construct()

	/**
	 * Whether the background engine is switched on.
	 *
	 * @return bool False only when `batch.engine` is `legacy-sync`.
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-chunked-background-processing-under-cron-req-ddras-002
	 */
	public function backgroundEnabled(): bool {
		$engine = trim($this->appConfig->getValueString('filinq', 'batch.engine', self::BACKGROUND));
		return $engine !== self::ENGINE_LEGACY;

	}//end backgroundEnabled()

	/**
	 * The synchronous cap.
	 *
	 * @return int The most files processed in-request.
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-resource-guards-on-extractconvert-backends-req-ddras-008
	 */
	public function syncCap(): int {
		return $this->stateService->getMaxFiles();

	}//end syncCap()

	/**
	 * The background cap, never below the synchronous cap.
	 *
	 * @return int The most files one batch may hold.
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-resource-guards-on-extractconvert-backends-req-ddras-008
	 */
	public function backgroundCap(): int {
		$cap = (int)$this->appConfig->getValueString(
			'filinq',
			'batch.max_files_background',
			(string)self::DEFAULT_BACKGROUND_CAP
		);
		if ($cap <= 0) {
			$cap = self::DEFAULT_BACKGROUND_CAP;
		}

		return max($cap, $this->syncCap());

	}//end backgroundCap()

	/**
	 * The path a batch of this many files takes.
	 *
	 * @param int $fileCount The number of files in the batch.
	 *
	 * @return string self::SYNC or self::BACKGROUND.
	 *
	 * @throws Exception With code 400, naming the maximum, when the batch is above the cap that applies.
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-resource-guards-on-extractconvert-backends-req-ddras-008
	 */
	public function route(int $fileCount): string {
		$syncCap = $this->syncCap();
		if ($fileCount <= $syncCap) {
			return self::SYNC;
		}

		$maximum = $syncCap;
		if ($this->backgroundEnabled() === true) {
			$maximum = $this->backgroundCap();
		}

		if ($fileCount > $maximum) {
			throw new Exception(
				'Folder contains too many files (found: ' . $fileCount . ', maximum: ' . $maximum . ')',
				400
			);
		}

		return self::BACKGROUND;

	}//end route()

	/**
	 * Whether an already-created batch of this many files is anonymized in the background.
	 *
	 * Unlike route() this never refuses: the batch exists already, and a batch
	 * the background path cannot take stays on the synchronous path it was
	 * accepted for.
	 *
	 * @param int $fileCount The number of files in the batch.
	 *
	 * @return bool True above the synchronous cap with the background engine on.
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/batch-anonymization/spec.md#requirement-batch-anonymization
	 */
	public function anonymizesInBackground(int $fileCount): bool {
		return $this->backgroundEnabled() === true && $fileCount > $this->syncCap();

	}//end anonymizesInBackground()
}//end class
