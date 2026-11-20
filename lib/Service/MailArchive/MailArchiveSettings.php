<?php

/**
 * Mail archive import settings
 *
 * How many messages one cron run of the import job takes
 * (`filinq.mail_archive.messages_per_run`, default 200, design D3) and
 * whether `readpst` is installed, for the admin page (REQ-MAI-002).
 *
 * @category  Service
 * @package   OCA\Filinq\Service\MailArchive
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\MailArchive;

use OCP\IAppConfig;

/**
 * The per-run message limit and the readpst status.
 *
 * @spec openspec/changes/intake-mailbox-archive-import/specs/mailbox-archive-import/spec.md#requirement-a-large-archive-drains-in-resumable-units-req-mai-004
 */
class MailArchiveSettings {

	public const KEY_MESSAGES_PER_RUN = 'filinq.mail_archive.messages_per_run';

	public const DEFAULT_MESSAGES_PER_RUN = 200;

	public const MAX_MESSAGES_PER_RUN = 2000;

	private const APP_ID = 'filinq';

	/**
	 * Constructor.
	 *
	 * @param IAppConfig     $appConfig The app config.
	 * @param ReadpstCommand $readpst   The PST converter.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly ReadpstCommand $readpst,
	) {

	}//end __construct()

	/**
	 * How many messages one run may take.
	 *
	 * @return int Between 1 and MAX_MESSAGES_PER_RUN; 200 when unset or not a number.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-1
	 */
	public function messagesPerRun(): int {
		$raw = trim($this->appConfig->getValueString(self::APP_ID, self::KEY_MESSAGES_PER_RUN, (string) self::DEFAULT_MESSAGES_PER_RUN));
		if (ctype_digit($raw) === false) {
			return self::DEFAULT_MESSAGES_PER_RUN;
		}

		return $this->clamp(value: (int) $raw);

	}//end messagesPerRun()

	/**
	 * Both settings, as the admin page shows them.
	 *
	 * @return array{messagesPerRun: int, readpstInstalled: bool} The settings.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-4
	 */
	public function toArray(): array {
		return ['messagesPerRun' => $this->messagesPerRun(), 'readpstInstalled' => $this->readpst->isAvailable()];

	}//end toArray()

	/**
	 * Store the per-run limit, clamped.
	 *
	 * @param int $messagesPerRun The limit.
	 *
	 * @return array{messagesPerRun: int, readpstInstalled: bool} The stored settings.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-4
	 */
	public function update(int $messagesPerRun): array {
		$this->appConfig->setValueString(self::APP_ID, self::KEY_MESSAGES_PER_RUN, (string) $this->clamp(value: $messagesPerRun));

		return $this->toArray();

	}//end update()

	/**
	 * A limit within bounds.
	 *
	 * @param int $value The asked limit.
	 *
	 * @return int The limit.
	 */
	private function clamp(int $value): int {
		return max(1, min(self::MAX_MESSAGES_PER_RUN, $value));

	}//end clamp()
}//end class
