<?php

/**
 * The terms a download is gated on, and who accepted them.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Redaction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Redaction;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRepository;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Reads and writes `downloadAgreement` rows in the `filinq` register.
 */
class DownloadAgreementRepository {

	/**
	 * The schema these rows live in.
	 *
	 * @var string
	 */
	public const SCHEMA = 'downloadAgreement';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService.
	 * @param LoggerInterface               $logger         Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * The terms in force for one document, or null when it is not gated.
	 *
	 * @param string $document The document.
	 *
	 * @return array<string, mixed>|null The agreement.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function forDocument(string $document): ?array {
		$rows = $this->rowsFor(document: $document);

		foreach ($rows as $row) {
			// The terms themselves carry no acceptance. A row that names an
			// acceptor is somebody's acceptance of them, not the terms.
			if (trim((string)($row['acceptedBy'] ?? '')) === '' && trim((string)($row['text'] ?? '')) !== '') {
				return $row;
			}
		}

		return null;

	}//end forDocument()

	/**
	 * What this person has accepted for this document, if anything.
	 *
	 * @param string $document The document.
	 * @param string $person   Who is asking.
	 *
	 * @return array<string, mixed>|null The acceptance.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function acceptanceOf(string $document, string $person): ?array {
		if (trim($person) === '') {
			return null;
		}

		$latest = null;
		$latestAt = '';
		foreach ($this->rowsFor(document: $document) as $row) {
			if (trim((string)($row['acceptedBy'] ?? '')) !== trim($person)) {
				continue;
			}

			$moment = (string)($row['acceptedAt'] ?? '');
			if ($latest === null || $moment > $latestAt) {
				$latest = $row;
				$latestAt = $moment;
			}
		}

		return $latest;

	}//end acceptanceOf()

	/**
	 * Write one acceptance.
	 *
	 * @param string $document The document.
	 * @param string $person   Who accepted.
	 * @param string $version  Which version they accepted.
	 * @param string $text     The text they were shown.
	 *
	 * @return array<string, mixed> The stored acceptance.
	 *
	 * @throws RuntimeException When the write fails, which stops the download.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function recordAcceptance(string $document, string $person, string $version, string $text): array {
		try {
			$stored = $this->objectResolver->resolve()->saveObject(
				object: [
					'document' => $document,
					'version' => $version,
					'text' => $text,
					'acceptedBy' => trim($person),
					'acceptedAt' => gmdate(format: 'c'),
					'acceptedVersion' => $version,
				],
				register: IntakeRepository::REGISTER,
				schema: self::SCHEMA
			);
		} catch (Throwable $e) {
			throw new RuntimeException(
				message: 'The acceptance could not be recorded: '.$e->getMessage(),
				code: 0,
				previous: $e
			);
		}

		return $this->normalise(row: $stored);

	}//end recordAcceptance()

	/**
	 * Every row about one document.
	 *
	 * @param string $document The document.
	 *
	 * @return array<int, array<string, mixed>> The rows.
	 *
	 * @spec exclude Read helper behind the public methods.
	 */
	private function rowsFor(string $document): array {
		if (trim($document) === '') {
			return [];
		}

		try {
			$results = $this->objectResolver->resolve()->searchObjects(
				query: [
					'@self' => [
						'register' => IntakeRepository::REGISTER,
						'schema' => self::SCHEMA,
					],
					'document' => $document,
				]
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[DownloadAgreementRepository] could not read the agreement rows',
				context: [
					'file' => __FILE__,
					'line' => __LINE__,
					'document' => $document,
					'error' => $e->getMessage(),
				]
			);

			return [];
		}

		if (is_array($results) === false) {
			return [];
		}

		$rows = [];
		foreach ($results as $result) {
			$rows[] = $this->normalise(row: $result);
		}

		return $rows;

	}//end rowsFor()

	/**
	 * Read one OpenRegister row into the flat shape this app uses.
	 *
	 * @param mixed $row The row.
	 *
	 * @return array<string, mixed> The row.
	 *
	 * @spec exclude Shape adapter over an OpenRegister response.
	 */
	private function normalise(mixed $row): array {
		$data = $row;
		if (is_object($row) === true && method_exists($row, 'jsonSerialize') === true) {
			$data = $row->jsonSerialize();
		}

		if (is_array($data) === false) {
			return [];
		}

		if (isset($data['object']) === true && is_array($data['object']) === true) {
			return $data['object'];
		}

		return $data;

	}//end normalise()
}//end class
