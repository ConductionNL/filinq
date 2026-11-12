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

use OCA\Filinq\Exception\AgreementStoreUnreadableException;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRepository;
use OCA\Filinq\Service\ReadsStoredObjectRows;
use OCA\OpenRegister\Kernel\Contracts\RegisterSchemaRef;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Reads and writes `downloadAgreement` rows in the `filinq` register.
 */
class DownloadAgreementRepository {
	use ReadsStoredObjectRows;

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
	 * @throws AgreementStoreUnreadableException When the store could not be read.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function forDocument(string $document): ?array {
		$rows = $this->rowsFor(document: $document);

		$inForce = null;
		foreach ($rows as $row) {
			if ($this->isTerms(row: $row) === false) {
				continue;
			}

			if ($this->supersedes(row: $row, inForce: $inForce) === true) {
				$inForce = $row;
			}
		}

		return $inForce;

	}//end forDocument()

	/**
	 * Whether a row is the terms themselves rather than somebody's acceptance of them.
	 *
	 * The terms carry no acceptance: a row that names an acceptor is
	 * somebody's acceptance of them, not the terms. Terms without a text are
	 * no terms either.
	 *
	 * @param array<string, mixed> $row The row.
	 *
	 * @return bool True for the terms.
	 */
	private function isTerms(array $row): bool {
		return trim((string)($row['acceptedBy'] ?? '')) === '' && trim((string)($row['text'] ?? '')) !== '';

	}//end isTerms()

	/**
	 * Whether these terms replace the ones found so far.
	 *
	 * 🔴 THE TERMS IN FORCE ARE THE NEWEST ONES, NEVER THE FIRST ROW BACK.
	 * Republishing the agreement adds a row beside the old one, and taking
	 * whichever the store happened to list first would hand a reader who
	 * accepted version 1 the file after version 2 was published, which is the
	 * exact case "a new version asks again" exists to stop.
	 *
	 * @param array<string, mixed>      $row     The terms.
	 * @param array<string, mixed>|null $inForce The terms found so far, if any.
	 *
	 * @return bool True when the row is at least as new.
	 */
	private function supersedes(array $row, ?array $inForce): bool {
		return $inForce === null
			|| version_compare((string)($row['version'] ?? ''), (string)($inForce['version'] ?? '')) >= 0;

	}//end supersedes()

	/**
	 * What this person has accepted for this document, if anything.
	 *
	 * @param string $document The document.
	 * @param string $person   Who is asking.
	 *
	 * @return array<string, mixed>|null The acceptance.
	 *
	 * @throws AgreementStoreUnreadableException When the store could not be read.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function acceptanceOf(string $document, string $person): ?array {
		if (trim($person) === '') {
			return null;
		}

		$mine = $this->acceptancesBy(rows: $this->rowsFor(document: $document), person: trim($person));

		return $this->latestAcceptance(acceptances: $mine);

	}//end acceptanceOf()

	/**
	 * The acceptance rows of one person, in store order.
	 *
	 * @param array<int, array<string, mixed>> $rows   The rows about the document.
	 * @param string                           $person The trimmed person.
	 *
	 * @return array<int, array<string, mixed>> Their acceptances.
	 */
	private function acceptancesBy(array $rows, string $person): array {
		$mine = [];
		foreach ($rows as $row) {
			if (trim((string)($row['acceptedBy'] ?? '')) === $person) {
				$mine[] = $row;
			}
		}

		return $mine;

	}//end acceptancesBy()

	/**
	 * The acceptance with the latest moment; the first one wins a tie.
	 *
	 * @param array<int, array<string, mixed>> $acceptances One person's acceptances.
	 *
	 * @return array<string, mixed>|null The latest, or null when there is none.
	 */
	private function latestAcceptance(array $acceptances): ?array {
		$latest = null;
		$latestAt = '';
		foreach ($acceptances as $row) {
			$moment = (string)($row['acceptedAt'] ?? '');
			if ($latest === null || $moment > $latestAt) {
				$latest = $row;
				$latestAt = $moment;
			}
		}

		return $latest;

	}//end latestAcceptance()

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

		return $this->storedPayload(row: $stored);

	}//end recordAcceptance()

	/**
	 * Every row about one document.
	 *
	 * @param string $document The document.
	 *
	 * @return array<int, array<string, mixed>> The rows.
	 *
	 * @throws AgreementStoreUnreadableException When the read failed, as opposed to finding nothing.
	 *
	 * @spec exclude Read helper behind the public methods.
	 */
	private function rowsFor(string $document): array {
		if (trim($document) === '') {
			return [];
		}

		try {
			// 🔴 SLUGS GO THROUGH `searchObjectsBySlug`, NEVER `searchObjects`.
			// OpenRegister's `searchObjects` has a numeric-ID contract on
			// `@self.register` and `@self.schema`: handed the slugs `filinq`
			// and `downloadAgreement` it returns ZERO ROWS and no error. Every
			// agreement then read as "no agreement was declared", every gated
			// file answered `mayDownload: true`, and the endpoint could not
			// tell a gated document from one nobody had ever declared anything
			// about. The document filter stays a BARE key beside the `@self`
			// block, which is the spelling the objects path reads.
			$results = $this->objectResolver->resolve()->searchObjectsBySlug(
				target: new RegisterSchemaRef(register: IntakeRepository::REGISTER, schema: self::SCHEMA),
				filters: ['document' => $document]
			);
		} catch (DoesNotExistException $e) {
			// The register or the schema is not on this instance, so nothing
			// here is gated. That is an answer, not a failure: an instance that
			// never imported `downloadAgreement` has no agreements to honour,
			// and refusing every download over it would take the download
			// surface down for a feature nobody enabled.
			$this->logger->warning(
				message: '[DownloadAgreementRepository] no downloadAgreement schema on this instance, so nothing is gated',
				context: [
					'file' => __FILE__,
					'line' => __LINE__,
					'document' => $document,
					'error' => $e->getMessage(),
				]
			);

			return [];
		} catch (Throwable $e) {
			// 🔴 A FAILED READ IS NOT "NOT GATED". Returning an empty list here
			// is what made this gate fail open: the caller cannot tell it apart
			// from a document with no terms, so it serves the file. Raising
			// keeps the two apart and lets the gate refuse.
			$this->logger->warning(
				message: '[DownloadAgreementRepository] could not read the agreement rows, so nothing is served',
				context: [
					'file' => __FILE__,
					'line' => __LINE__,
					'document' => $document,
					'error' => $e->getMessage(),
				]
			);

			throw new AgreementStoreUnreadableException(
				message: 'The download conditions could not be read: '.$e->getMessage(),
				code: 0,
				previous: $e
			);
		}//end try

		if (is_array($results) === false) {
			return [];
		}

		$rows = [];
		foreach ($results as $result) {
			$rows[] = $this->storedPayload(row: $result);
		}

		return $rows;

	}//end rowsFor()
}//end class
