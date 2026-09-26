<?php

/**
 * Party Suggestion Service
 *
 * Reads the name and address block out of an arriving document and OFFERS a
 * party. It never writes one. A party record written from a guess is a record
 * nobody chose, and no screen afterwards says which parties were guessed and
 * which were decided.
 *
 * The confidence is stated rather than modelled: it is the share of the four
 * fields (name, address, email, telephone) the extraction actually found, so a
 * reader can say what it means. A model score nobody can explain would look
 * more precise and mean less.
 *
 * Accept, edit and reject are each recorded as a correction against the sender.
 * That corpus is what the next suggestion from the same sender is ranked
 * against, which is why a rejection is stored as carefully as an acceptance.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;
use DateTimeInterface;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Offers a party read out of a document, and records what was done with it.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 */
class PartySuggestionService {

	/**
	 * The schema holding the corrections corpus.
	 *
	 * @var string
	 */
	public const CORRECTION_SCHEMA = 'intakePartyCorrection';

	/**
	 * The entity types a party is read out of, in the order they are asked for.
	 *
	 * @var array<string, string>
	 */
	private const FIELDS = [
		'PERSON' => 'name',
		'LOCATION' => 'address',
		'EMAIL' => 'email',
		'PHONE_NUMBER' => 'telephone',
	];

	/**
	 * Constructor.
	 *
	 * @param FileEntityStatsService $entities The seam onto OpenRegister's entity relations.
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService.
	 * @param IUserSession $userSession The current session.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly FileEntityStatsService $entities,
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Read one party out of the entities already detected on a file.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array<int, array<string, mixed>> Zero or one suggestion, each with its confidence and source span.
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function suggestFor(int $fileId): array {
		if ($fileId <= 0) {
			return [];
		}

		$mapper = $this->entities->tryGetEntityRelationMapper();
		if ($mapper === null) {
			return [];
		}

		try {
			$relations = $mapper->findEntitiesForFile($fileId);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[PartySuggestionService] could not read the entities of this file',
				context: ['file' => __FILE__, 'line' => __LINE__, 'fileId' => $fileId, 'error' => $e->getMessage()]
			);

			return [];
		}

		$party = [];
		foreach ($relations as $relation) {
			$field = $this->fieldOf(relation: $relation);
			if ($field === null || isset($party[$field['name']]) === true) {
				continue;
			}

			$party[$field['name']] = $field['value'];
		}

		$spans = $party;

		if ($party === []) {
			return [];
		}

		return [
			[
				'party' => $party,
				'confidence' => round((count($party) / count(self::FIELDS)), 2),
				'confidenceMeaning' => 'share of name, address, email and telephone the document actually carried',
				'sourceSpan' => $spans,
				'source' => 'entity-detection',
				'written' => false,
			],
		];

	}//end suggestFor()

	/**
	 * The party field one detected entity fills, and with what.
	 *
	 * A relation whose type is not one this app maps, or that carries no text,
	 * fills nothing. It is skipped rather than suggested empty: a suggestion
	 * with a blank name is worse than no suggestion, because a clerk accepts it.
	 *
	 * @param mixed $relation The entity relation, as OpenRegister returned it.
	 *
	 * @return array{name: string, value: string}|null The field and its value, or null.
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	private function fieldOf(mixed $relation): ?array {
		$type = '';
		if (method_exists($relation, 'getType') === true) {
			$type = strtoupper((string)$relation->getType());
		}

		$value = '';
		if (method_exists($relation, 'getValue') === true) {
			$value = trim((string)$relation->getValue());
		}

		if (isset(self::FIELDS[$type]) === false || $value === '') {
			return null;
		}

		return ['name' => self::FIELDS[$type], 'value' => $value];

	}//end fieldOf()

	/**
	 * Record what a clerk did with a suggestion.
	 *
	 * @param string $sender The sender the suggestion was made for.
	 * @param string $decision One of `accepted`, `edited`, `rejected`.
	 * @param array<string, mixed> $suggested What was proposed.
	 * @param array<string, mixed> $accepted What was filed, empty on a rejection.
	 * @param string $intakeDocument The intake document it was shown on.
	 *
	 * @return array<string, mixed> The stored correction.
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function recordDecision(
		string $sender,
		string $decision,
		array $suggested,
		array $accepted = [],
		string $intakeDocument = '',
	): array {
		$correction = [
			'sender' => $sender,
			'intakeDocument' => $intakeDocument,
			'suggested' => $suggested,
			'decision' => $decision,
			'accepted' => $accepted,
			'decidedBy' => $this->currentUserId(),
			'decidedAt' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
		];

		try {
			$this->objectResolver->resolve()->saveObject(
				object: $correction,
				register: IntakeRepository::REGISTER,
				schema: self::CORRECTION_SCHEMA
			);
		} catch (Throwable $e) {
			// Fails LOUD on the write. This is the corpus the next suggestion
			// is ranked against; losing a correction silently makes the next
			// suggestion wrong for a reason nobody can see.
			$this->logger->error(
				message: '[PartySuggestionService] could not store a party correction',
				context: ['file' => __FILE__, 'line' => __LINE__, 'sender' => $sender, 'error' => $e->getMessage()]
			);

			throw $e;
		}

		return $correction;

	}//end recordDecision()

	/**
	 * The user id of the person at the keyboard.
	 *
	 * @return string The user id, or an empty string when there is no session.
	 *
	 * @spec exclude Session accessor with no behaviour of its own.
	 */
	private function currentUserId(): string {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return '';
		}

		return $user->getUID();

	}//end currentUserId()
}//end class
