<?php

/**
 * Email ingestion settings
 *
 * Which Nextcloud folders are watched inboxes, which dossier each one files
 * into, and how many files one tick of the job may take. There is no
 * mailbox host, account or password here, on purpose: mail reaches a
 * watched folder as .eml files, delivered by hand, by a mounted archive or
 * by an Integriq flow that owns the mailbox connection.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\EmailIngestion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-5
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\EmailIngestion;

use InvalidArgumentException;
use OCP\IAppConfig;

/**
 * The inbox to dossier mapping and the per-tick budget.
 */
class EmailIngestionSettings {

	public const KEY_INBOXES = 'filinq.email_ingestion.inbox_folders';

	public const KEY_FILES_PER_TICK = 'filinq.email_ingestion.files_per_tick';

	public const DEFAULT_FILES_PER_TICK = 25;

	public const MAX_FILES_PER_TICK = 500;

	private const APP_ID = 'filinq';

	/**
	 * Constructor.
	 *
	 * @param IAppConfig $appConfig The app config.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IAppConfig $appConfig,
	) {

	}//end __construct()

	/**
	 * The watched inboxes.
	 *
	 * @return list<array{folderId: int, dossierRef: string}> The mappings; none when unset or unreadable.
	 *
	 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-1
	 */
	public function inboxes(): array {
		$decoded = json_decode($this->appConfig->getValueString(self::APP_ID, self::KEY_INBOXES, '[]'), true);
		if (is_array($decoded) === false) {
			return [];
		}

		try {
			return $this->validInboxes(inboxes: $decoded);
		} catch (InvalidArgumentException) {
			return [];
		}

	}//end inboxes()

	/**
	 * How many files one tick may take.
	 *
	 * @return int Between 1 and MAX_FILES_PER_TICK; 25 when unset or not a number.
	 *
	 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-4
	 */
	public function filesPerTick(): int {
		$raw = $this->appConfig->getValueString(self::APP_ID, self::KEY_FILES_PER_TICK, (string) self::DEFAULT_FILES_PER_TICK);
		if (ctype_digit($raw) === false) {
			return self::DEFAULT_FILES_PER_TICK;
		}

		return $this->clamp(value: (int) $raw);

	}//end filesPerTick()

	/**
	 * Both settings.
	 *
	 * @return array{inboxes: list<array{folderId: int, dossierRef: string}>, filesPerTick: int} The settings.
	 *
	 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-5
	 */
	public function toArray(): array {
		return ['inboxes' => $this->inboxes(), 'filesPerTick' => $this->filesPerTick()];

	}//end toArray()

	/**
	 * Store both settings.
	 *
	 * @param array<int, mixed> $inboxes      Rows of {folderId, dossierRef}.
	 * @param int               $filesPerTick The per-tick budget, clamped.
	 *
	 * @return array{inboxes: list<array{folderId: int, dossierRef: string}>, filesPerTick: int} What was stored.
	 *
	 * @throws InvalidArgumentException With code 400 for a row without folder or dossier, or a folder mapped twice.
	 *
	 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-5
	 */
	public function update(array $inboxes, int $filesPerTick): array {
		$valid = $this->validInboxes(inboxes: $inboxes);
		$this->appConfig->setValueString(self::APP_ID, self::KEY_INBOXES, (string) json_encode($valid));
		$this->appConfig->setValueString(self::APP_ID, self::KEY_FILES_PER_TICK, (string) $this->clamp(value: $filesPerTick));

		return $this->toArray();

	}//end update()

	/**
	 * Validate and normalise inbox rows.
	 *
	 * @param array<int|string, mixed> $inboxes The rows.
	 *
	 * @return list<array{folderId: int, dossierRef: string}> The rows.
	 *
	 * @throws InvalidArgumentException With code 400.
	 */
	private function validInboxes(array $inboxes): array {
		$valid = [];
		foreach ($inboxes as $row) {
			if (is_array($row) === false) {
				$row = [];
			}

			$folderId = (int) ($row['folderId'] ?? 0);
			$dossierRef = trim((string) ($row['dossierRef'] ?? ''));
			if ($folderId < 1 || preg_match('/^[A-Za-z0-9_-]{1,64}$/', $dossierRef) !== 1) {
				throw new InvalidArgumentException('Every inbox needs a folder and a dossier.', 400);
			}

			if (in_array($folderId, array_column($valid, 'folderId'), true) === true) {
				throw new InvalidArgumentException('A folder can be the inbox of one dossier only.', 400);
			}

			$valid[] = ['folderId' => $folderId, 'dossierRef' => $dossierRef];
		}

		return $valid;

	}//end validInboxes()

	/**
	 * Clamp the budget.
	 *
	 * @param int $value The value.
	 *
	 * @return int Between 1 and MAX_FILES_PER_TICK.
	 */
	private function clamp(int $value): int {
		return max(1, min(self::MAX_FILES_PER_TICK, $value));

	}//end clamp()
}//end class
