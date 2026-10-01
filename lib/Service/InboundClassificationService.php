<?php

/**
 * Inbound classification: a suggested type, correspondent and dossier
 *
 * Runs where enrichment runs. For a document object that names a file and
 * has text, it asks DocumentTypeClassifier for an intake type, ranks the
 * entities OpenRegister already detected for a correspondent, looks for a
 * single dossier that matches exactly, and stores the lot as one
 * classificationResult in status `suggested`. It never writes onto the
 * document itself: the canonical type, correspondent and filing happen only
 * when a person confirms (ClassificationController).
 *
 * One active record per file: a document that already has a suggestion,
 * a confirmation or a rejection is left alone, except that a suggestion
 * still waiting for entity detection (correspondentPending) is superseded
 * once the entities are there.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Service\Classification\ClassificationResultRepository;
use OCA\Filinq\Service\Classification\ClassificationSources;
use OCA\Filinq\Service\Classification\CorrespondentRanker;
use OCA\Filinq\Service\Classification\DossierMatcher;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;

/**
 * Orchestrates one classification suggestion per inbound file.
 *
 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-2
 */
class InboundClassificationService {

	/**
	 * The admin toggle; on unless set to "0".
	 *
	 * @var string
	 */
	public const TOGGLE = 'enable_inbound_classification';

	/**
	 * Constructor.
	 *
	 * @param DocumentTypeClassifier         $classifier The intake-type vocabularies.
	 * @param CorrespondentRanker            $ranker     Picks a correspondent from detected entities.
	 * @param DossierMatcher                 $matcher    Exact or prefix dossier match.
	 * @param ClassificationResultRepository $results    The classificationResult records.
	 * @param ClassificationSources          $sources    Detected entities and dossiers.
	 * @param IAppConfig                     $appConfig  Holds the toggle.
	 * @param LoggerInterface                $logger     Logs a skipped document.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentTypeClassifier $classifier,
		private readonly CorrespondentRanker $ranker,
		private readonly DossierMatcher $matcher,
		private readonly ClassificationResultRepository $results,
		private readonly ClassificationSources $sources,
		private readonly IAppConfig $appConfig,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Whether the admin toggle is on.
	 *
	 * @return bool True unless the setting is "0".
	 *
	 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-5
	 */
	public function isEnabled(): bool {
		return $this->appConfig->getValueString('filinq', self::TOGGLE, '1') !== '0';

	}//end isEnabled()

	/**
	 * Suggest a type, correspondent and dossier for one document object.
	 *
	 * @param array<string, mixed> $objectData The object's fields.
	 * @param array<string, string> $objectRef  The object: id, register, schema.
	 *
	 * @return array{outcome: string, reason?: string, record?: array<string, mixed>}
	 *     `suggested` with the record, or `skipped` with the reason.
	 *
	 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-2
	 */
	public function classify(array $objectData, array $objectRef): array {
		if ($this->isEnabled() === false) {
			return ['outcome' => 'skipped', 'reason' => 'disabled'];
		}

		// The enrichment path sees every object; only an intake document is inbound.
		if ($this->isInbound(objectData: $objectData) === false) {
			return ['outcome' => 'skipped', 'reason' => 'not_inbound'];
		}

		$fileId = $this->fileIdOf(objectData: $objectData);
		if ($fileId === 0) {
			return ['outcome' => 'skipped', 'reason' => 'no_file'];
		}

		$active = $this->results->activeFor(fileId: $fileId);
		$entities = $this->sources->entitiesFor(fileId: $fileId);
		if ($active !== null && $this->supersedes(active: $active, entities: $entities) === false) {
			return ['outcome' => 'skipped', 'reason' => 'already_classified'];
		}

		$text = $this->textOf(objectData: $objectData);
		if ($text === '') {
			$this->logger->info('[Classification] skipped a document without text', ['fileId' => $fileId, 'objectId' => ($objectRef['id'] ?? '')]);
			return ['outcome' => 'skipped', 'reason' => 'no_text'];
		}

		$record = $this->suggestion(objectData: $objectData, objectRef: $objectRef, fileId: $fileId, text: $text, entities: $entities);
		if ($active !== null) {
			$this->results->save(record: ['status' => 'superseded'] + $active, uuid: (string) $active['uuid']);
		}

		return ['outcome' => 'suggested', 'record' => $this->results->save(record: $record)];

	}//end classify()

	/**
	 * Build the suggestion record. Minimal by design: no document text and
	 * no entity other than the one suggested correspondent's name.
	 *
	 * @param array<string, mixed>                  $objectData The object's fields.
	 * @param array<string, string>                 $objectRef  The object: id, register, schema.
	 * @param int                                   $fileId     The file id.
	 * @param string                                $text       The document text.
	 * @param array<int, array<string, mixed>>|null $entities   The detected entities, null when not known yet.
	 *
	 * @return array<string, mixed> The record.
	 */
	private function suggestion(array $objectData, array $objectRef, int $fileId, string $text, ?array $entities): array {
		$type = $this->classifier->classify(text: $text);
		$correspondent = null;
		if ($entities !== null) {
			$correspondent = $this->ranker->rank(rows: $entities);
		}

		$method = 'rules';
		if ($correspondent !== null) {
			$method = 'mixed';
		}

		return [
			'fileId' => $fileId,
			'fileName' => (string) ($objectData['fileName'] ?? ($objectData['name'] ?? '')),
			'objectId' => (string) ($objectRef['id'] ?? ''),
			'objectRegister' => (string) ($objectRef['register'] ?? ''),
			'objectSchema' => (string) ($objectRef['schema'] ?? ''),
			'suggestedDocumentType' => $type['type'],
			'documentTypeConfidence' => $type['confidence'],
			'method' => $method,
			'suggestedCorrespondent' => $correspondent,
			'correspondentPending' => $entities === null,
			'suggestedDossier' => $this->matcher->match(dossiers: $this->sources->dossiers(), correspondent: $correspondent['name'] ?? null, text: $this->referenceText(objectData: $objectData, text: $text)),
			'status' => 'suggested',
		];

	}//end suggestion()

	/**
	 * Whether a new suggestion replaces the active record: only a suggestion
	 * still waiting for entity detection, once the entities are there.
	 *
	 * @param array<string, mixed>                  $active   The active record.
	 * @param array<int, array<string, mixed>>|null $entities The detected entities now.
	 *
	 * @return bool True to supersede.
	 */
	private function supersedes(array $active, ?array $entities): bool {
		return ($active['status'] ?? '') === 'suggested'
			&& ($active['correspondentPending'] ?? false) === true
			&& $entities !== null;

	}//end supersedes()

	/**
	 * The text a case reference is looked for in: the subject first, then the
	 * document text. An email names its case in the subject far more often
	 * than in the body, and the body is what the type is read from.
	 *
	 * @param array<string, mixed> $objectData The object's fields.
	 * @param string               $text       The document text.
	 *
	 * @return string The subject and the text.
	 */
	private function referenceText(array $objectData, string $text): string {
		$subject = ($objectData['subject'] ?? '');
		if (is_string($subject) === false || trim($subject) === '' || str_contains($text, $subject) === true) {
			return $text;
		}

		return trim($subject) . "\n" . $text;

	}//end referenceText()

	/**
	 * Whether the object is an inbound document: an intakeDocument, which
	 * alone carries the channel it arrived through. A conformance report or
	 * an archive job also has a file and a subject, and is not classified.
	 *
	 * @param array<string, mixed> $objectData The object's fields.
	 *
	 * @return bool True for an intake document.
	 */
	private function isInbound(array $objectData): bool {
		$channel = ($objectData['channel'] ?? null);

		return is_string($channel) === true && $channel !== '';

	}//end isInbound()

	/**
	 * The file id an object names: `file` on an intake document, `fileId` elsewhere.
	 *
	 * @param array<string, mixed> $objectData The object's fields.
	 *
	 * @return int The file id, 0 when there is none.
	 */
	private function fileIdOf(array $objectData): int {
		$value = ($objectData['file'] ?? ($objectData['fileId'] ?? 0));
		if (is_int($value) === true || (is_string($value) === true && ctype_digit($value) === true)) {
			return (int) $value;
		}

		return 0;

	}//end fileIdOf()

	/**
	 * The text to classify: the OCR text of an intake document, else the
	 * enrichment text fields (content, text, description), else the subject.
	 *
	 * @param array<string, mixed> $objectData The object's fields.
	 *
	 * @return string The text, '' when there is none.
	 */
	private function textOf(array $objectData): string {
		foreach (['contentText', 'content', 'text', 'description', 'subject'] as $field) {
			$raw = ($objectData[$field] ?? null);
			if (is_scalar($raw) === true && trim((string) $raw) !== '') {
				return trim((string) $raw);
			}
		}

		return '';

	}//end textOf()
}//end class
