<?php

/**
 * Removes document drafts past their expiry.
 *
 * @category BackgroundJob
 * @package  OCA\Filinq\BackgroundJob
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-2.5
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\BackgroundJob;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use OCA\Filinq\Kernel\Contracts\SettingsServiceInterface;
use OCA\Filinq\Service\Draft\DocumentDraftService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

/**
 * Daily: deletes every documentDraft whose `expiresAt` has passed.
 *
 * A draft gets `expiresAt` 30 days after each save, so only drafts left
 * untouched for 30 days go. No file is made from a deleted draft. The sweep
 * runs without a user, so it reads and deletes with RBAC off; it touches the
 * documentDraft schema only.
 *
 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-2.5
 */
class DocumentDraftExpiryJob extends TimedJob {

	/**
	 * Constructor.
	 *
	 * @param ITimeFactory             $clock    The clock.
	 * @param SettingsServiceInterface $settings Hands out OpenRegister's object service.
	 * @param LoggerInterface          $logger   The logger.
	 */
	public function __construct(
		private readonly ITimeFactory $clock,
		private readonly SettingsServiceInterface $settings,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(time: $clock);
		$this->setInterval(seconds: 86400);
	}//end __construct()

	/**
	 * Delete the expired drafts.
	 *
	 * @param mixed $argument Unused.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-2.5
	 */
	protected function run(mixed $argument): void {
		$this->sweep();
	}//end run()

	/**
	 * Delete the drafts past their expiry now.
	 *
	 * @return int The number deleted.
	 *
	 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-2.5
	 */
	public function sweep(): int {
		$objectService = $this->settings->getObjectService();
		if ($objectService === null) {
			$this->logger->warning('[DocumentDraftExpiryJob] OpenRegister not available, skipping');
			return 0;
		}

		$now     = $this->clock->now();
		$deleted = 0;
		try {
			$drafts = $objectService->findAll(
				['filters' => ['register' => DocumentDraftService::REGISTER, 'schema' => DocumentDraftService::SCHEMA]],
				_rbac: false
			);
		} catch (Exception $e) {
			$this->logger->error('[DocumentDraftExpiryJob] Could not list drafts: ' . $e->getMessage(), ['exception' => $e]);
			return 0;
		}

		foreach ($drafts as $draft) {
			$fields = $this->toArray(result: $draft);
			$uuid   = (string) ($fields['@self']['id'] ?? ($fields['id'] ?? ''));
			if ($uuid === '' || $this->isExpired(draft: $fields, now: $now) === false) {
				continue;
			}

			try {
				$objectService->deleteObject(
					uuid: $uuid,
					register: DocumentDraftService::REGISTER,
					schema: DocumentDraftService::SCHEMA,
					_rbac: false
				);
				$deleted++;
			} catch (Exception $e) {
				$this->logger->error('[DocumentDraftExpiryJob] Could not delete draft ' . $uuid . ': ' . $e->getMessage(), ['exception' => $e]);
			}
		}

		if ($deleted > 0) {
			$this->logger->info('[DocumentDraftExpiryJob] Deleted ' . $deleted . ' expired draft(s).');
		}

		return $deleted;
	}//end sweep()

	/**
	 * Whether a draft is past its expiry at a moment.
	 *
	 * @param array<string, mixed> $draft The draft.
	 * @param DateTimeInterface    $now   The moment.
	 *
	 * @return bool True when `expiresAt` is set, readable and before the moment.
	 *
	 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-2.5
	 */
	private function isExpired(array $draft, DateTimeInterface $now): bool {
		$expiresAt = (string) ($draft['expiresAt'] ?? '');
		if ($expiresAt === '') {
			return false;
		}

		try {
			return new DateTimeImmutable($expiresAt) < $now;
		} catch (Exception) {
			return false;
		}
	}//end isExpired()

	/**
	 * An object as an array.
	 *
	 * @param mixed $result What OpenRegister returned.
	 *
	 * @return array<string, mixed> The object, or [].
	 */
	private function toArray(mixed $result): array {
		if (is_object($result) === true && method_exists($result, 'jsonSerialize') === true) {
			$result = $result->jsonSerialize();
		}

		if (is_array($result) === false) {
			return [];
		}

		return $result;
	}//end toArray()
}//end class
