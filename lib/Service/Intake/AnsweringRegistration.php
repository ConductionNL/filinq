<?php

/**
 * Answering Registration: the post register entries an acknowledgement of receipt reads and writes.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Intake
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/intake-acknowledge-and-stamp
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Intake;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\SlugSearch;
use OCA\Filinq\Service\StoredRecordFields;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindTarget;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use OCP\AppFramework\Utility\ITimeFactory;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Finds the inbound `documentRegistration` that names an arrived document,
 * and registers an outbound one whose `answers` names it (design D4). The
 * number of the outbound entry is drawn by the platform sequence on save,
 * never here.
 *
 * Both answer, never throw: a post register that cannot be read or written
 * never refuses an arrival or its acknowledgement (design D2).
 *
 * @spec openspec/changes/intake-acknowledge-and-stamp
 */
class AnsweringRegistration {

	/**
	 * The register the post register lives in.
	 */
	public const REGISTER = 'filinq';

	/**
	 * The post register schema.
	 */
	public const SCHEMA = 'documentRegistration';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolves openregister's ObjectService.
	 * @param ITimeFactory                  $time           When the answer was registered.
	 * @param LoggerInterface               $logger         Records a read or write that failed.
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly ITimeFactory $time,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * The inbound registration of an arrived document.
	 *
	 * @param string $documentUuid The intake document.
	 *
	 * @return array<string, mixed>|null The registration with its `uuid`, or null when none names the
	 *                                   document or the register could not be read.
	 *
	 * @spec openspec/changes/intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-a-matching-rule-acknowledges-receipt-to-the-sender-req-ias-001
	 */
	public function inboundFor(string $documentUuid): array|null {
		$uuid = trim($documentUuid);
		if ($uuid === '') {
			return null;
		}

		try {
			// Bare keys through searchObjectsBySlug: a `filter[...]` wrapper or
			// searchObjects with slugs answers zero rows and no error.
			$results = (new SlugSearch(objects: $this->objectResolver->resolve()))->search(
				register: self::REGISTER,
				schema: self::SCHEMA,
				filters: ['document' => $uuid, 'direction' => 'inbound']
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[AnsweringRegistration] the post register could not be read; the acknowledgement carries the date only',
				context: ['file' => __FILE__, 'line' => __LINE__, 'document' => $uuid, 'error' => $e->getMessage()]
			);
			return null;
		}

		return self::firstInbound(results: (array)$results);
	}//end inboundFor()

	/**
	 * The first inbound entry among the rows a search answered.
	 *
	 * @param array<mixed> $results The rows.
	 *
	 * @return array<string, mixed>|null The entry with its `uuid`, or null when none is inbound.
	 */
	private static function firstInbound(array $results): array|null {
		foreach ($results as $row) {
			$entry = (new StoredRecordFields())->read(row: $row);
			if (($entry['uuid'] ?? '') !== '' && ($entry['direction'] ?? '') === 'inbound') {
				return $entry;
			}
		}

		return null;
	}//end firstInbound()

	/**
	 * The outbound entry answering an inbound registration with a letter.
	 *
	 * @param array<string, mixed> $inbound    The inbound registration, with its `uuid` and `unit`.
	 * @param string               $letterUuid The document that went out.
	 *
	 * @return array<string, string>|null The entry to save, or null when the inbound one or the letter is missing.
	 */
	private function answerEntry(array $inbound, string $letterUuid): array|null {
		$answers = trim((string)($inbound['uuid'] ?? ''));
		$unit = trim((string)($inbound['unit'] ?? ''));
		if ($answers === '' || $unit === '' || trim($letterUuid) === '') {
			return null;
		}

		return [
			'direction' => 'outbound',
			'registeredAt' => $this->time->getDateTime('now', new \DateTimeZone('UTC'))->format('c'),
			'unit' => $unit,
			'document' => trim($letterUuid),
			'answers' => $answers,
		];
	}//end answerEntry()

	/**
	 * Register the acknowledgement as the outbound answer to an inbound registration.
	 *
	 * @param array<string, mixed> $inbound    The inbound registration, with its `uuid` and `unit`.
	 * @param string               $letterUuid The document that went out: the acknowledgement's generated document.
	 *
	 * @return array<string, mixed>|null The saved outbound registration with its `uuid`, or null when it
	 *                                   could not be written.
	 *
	 * @spec openspec/changes/intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-a-matching-rule-acknowledges-receipt-to-the-sender-req-ias-001
	 */
	public function registerAnswer(array $inbound, string $letterUuid): array|null {
		$entry = $this->answerEntry(inbound: $inbound, letterUuid: $letterUuid);
		if ($entry === null) {
			return null;
		}

		try {
			$saved = $this->objectResolver->resolve()->saveObject(
				object: $entry,
				options: new ObjectSaveOptions(
					target: new ObjectFindTarget(register: self::REGISTER, schema: self::SCHEMA)
				)
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[AnsweringRegistration] the acknowledgement could not be registered as outgoing post',
				context: ['file' => __FILE__, 'line' => __LINE__, 'answers' => $entry['answers'], 'error' => $e->getMessage()]
			);
			return null;
		}

		$stored = (new StoredRecordFields())->read(row: $saved);
		if (($stored['uuid'] ?? '') === '') {
			return null;
		}

		return $stored;
	}//end registerAnswer()
}//end class
