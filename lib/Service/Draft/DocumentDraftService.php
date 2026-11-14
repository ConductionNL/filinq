<?php

/**
 * Document drafts: a generation stopped before it is stored, then finished.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Draft
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-2.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Draft;

use DateInterval;
use DateTimeInterface;
use Exception;
use OCA\Filinq\Kernel\Contracts\CorrespondenceServiceInterface;
use OCA\Filinq\Kernel\Contracts\DocumentServiceInterface;
use OCA\Filinq\Kernel\Contracts\SettingsServiceInterface;
use OCA\Filinq\Kernel\Contracts\TemplateServiceInterface;
use OCP\AppFramework\Utility\ITimeFactory;

/**
 * Opens a generation as an editable draft and finishes it through the direct path.
 *
 * Creating renders through {@see DocumentServiceInterface::generatePreview()}
 * and saves a private `documentDraft`; nothing is filed or logged. Finishing
 * sanitises the stored HTML and produces, files and logs it through
 * {@see DocumentServiceInterface::generateFromHtml()}, so the HTML is never
 * rendered by Twig again. Saving an edit and discarding go through
 * OpenRegister's object API directly.
 *
 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-2.1
 */
class DocumentDraftService {

	/**
	 * Days a draft lives after its last save.
	 */
	public const LIFETIME_DAYS = 30;

	/**
	 * The register and schema of a draft.
	 */
	public const REGISTER = 'filinq';
	public const SCHEMA   = 'documentDraft';

	/**
	 * The answer for a template with a plain-language version.
	 */
	public const PLAIN_LANGUAGE_REFUSAL = 'This template has a plain-language version. Generate it directly.';

	/**
	 * Where a generation can start.
	 */
	private const ORIGINS = ['correspondence', 'document', 'wizard'];

	/**
	 * Options kept on the draft for finishing; render-only options are not.
	 */
	private const KEPT_OPTIONS = ['output', 'filename', 'huisstijlId', 'caseId', 'zaakId', 'recipientType', 'caseReference', 'pdfOptions'];

	/**
	 * Constructor.
	 *
	 * @param DocumentServiceInterface       $documents      Renders the preview and produces the finished document.
	 * @param CorrespondenceServiceInterface $correspondence Logs a finished letter.
	 * @param TemplateServiceInterface       $templates      Reads the template.
	 * @param SettingsServiceInterface       $settings       Hands out OpenRegister's object service.
	 * @param DraftHtmlSanitizer             $sanitizer      Sanitises the edited HTML.
	 * @param ITimeFactory                   $time           The clock.
	 */
	public function __construct(
		private readonly DocumentServiceInterface $documents,
		private readonly CorrespondenceServiceInterface $correspondence,
		private readonly TemplateServiceInterface $templates,
		private readonly SettingsServiceInterface $settings,
		private readonly DraftHtmlSanitizer $sanitizer,
		private readonly ITimeFactory $time,
	) {
	}//end __construct()

	/**
	 * Render a generation and save it as a draft in stage `editing`.
	 *
	 * @param string $templateId The template.
	 * @param array  $dataRefs   Data references: [{register, schema, id}, ...].
	 * @param array  $options    Generation options: format, adHocData, listRefs, huisstijlId, output,
	 *                           filename, wizardContext ({wizardId, answers, step}).
	 * @param string $origin     Where the generation started: correspondence, document or wizard.
	 *
	 * @return array{id: string, stage: string, html: string, warnings: array<int, string>, expiresAt: string}
	 *
	 * @throws Exception 400 for an unknown origin, 409 for a template with a plain-language version,
	 *                   503 without OpenRegister.
	 *
	 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-2.1
	 */
	public function create(string $templateId, array $dataRefs, array $options, string $origin): array {
		if (in_array($origin, self::ORIGINS, true) === false) {
			throw new Exception(message: 'Unknown draft origin: ' . $origin, code: 400);
		}

		$template = $this->templates->getTemplate(id: $templateId);
		if (empty($template['plainLanguage']) === false) {
			throw new Exception(message: self::PLAIN_LANGUAGE_REFUSAL, code: 409);
		}

		$preview = $this->documents->generatePreview(templateId: $templateId, dataRefs: $dataRefs, options: $options);
		$draft   = [
			'origin' => $origin,
			'stage' => 'editing',
			'templateId' => $templateId,
			'templateName' => (string) ($template['name'] ?? ''),
			'templateVersion' => $this->intOrNull(value: ($template['version'] ?? null)),
			'dataRefs' => array_values($dataRefs),
			'adHocData' => (array) ($options['adHocData'] ?? []),
			'format' => (string) ($options['format'] ?? 'pdf'),
			'outputOptions' => array_intersect_key($options, array_flip(self::KEPT_OPTIONS)),
			'html' => $preview['html'],
			'warnings' => $preview['warnings'],
			'expiresAt' => $this->expiresAt(),
		] + $this->wizardFields(context: ($options['wizardContext'] ?? null));

		$saved = $this->toArray(result: $this->objectService()->saveObject(object: $draft, register: self::REGISTER, schema: self::SCHEMA));

		return [
			'id' => $this->idOf(object: $saved),
			'stage' => $draft['stage'],
			'html' => $draft['html'],
			'warnings' => $draft['warnings'],
			'expiresAt' => $draft['expiresAt'],
		];
	}//end create()

	/**
	 * Produce, file and log a draft's edited text, then close the draft.
	 *
	 * @param string $draftId The draft.
	 * @param string $userId  The session user, who must own the draft.
	 *
	 * @return array<string, mixed> The generation result, plus `draftId` and `recordId`.
	 *
	 * @throws Exception 404 when the draft is not the user's, 409 when it is not being edited,
	 *                   422 when a wizard draft's answers do not pass, 503 without OpenRegister.
	 *
	 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-2.3
	 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-2.4
	 */
	public function finish(string $draftId, string $userId): array {
		$draft = $this->ownDraft(draftId: $draftId, userId: $userId);
		$stage = (string) ($draft['stage'] ?? '');
		if ($stage === 'finished') {
			throw new Exception(message: 'This draft is already finished.', code: 409);
		}

		if ($stage !== 'editing') {
			throw new Exception(message: 'Open the draft for editing before finishing it.', code: 409);
		}

		$templateId = (string) ($draft['templateId'] ?? '');
		$dataRefs   = (array) ($draft['dataRefs'] ?? []);
		$options    = $this->finishOptions(draft: $draft, userId: $userId);
		$isLetter   = ($draft['origin'] ?? '') === 'correspondence';

		$result = $this->documents->generateFromHtml(
			templateId: $templateId,
			html: $this->sanitizer->sanitize(html: (string) ($draft['html'] ?? '')),
			dataRefs: $dataRefs,
			options: $options,
			recordFields: ['draftId' => $draftId, 'editedBy' => $userId],
			log: $isLetter === false
		);

		$record = $result['metadata'];
		if ($isLetter === true) {
			$record = $this->correspondence->logFinishedDraft(
				templateId: $templateId,
				templateName: (string) ($draft['templateName'] ?? ''),
				dataRefs: $dataRefs,
				options: $options,
				draftId: $draftId,
				editedBy: $userId
			);
		}

		$recordId = $this->idOf(object: (array) $record);
		$this->objectService()->saveObject(
			object: array_merge(
				$this->withoutMetadata(draft: $draft),
				['stage' => 'finished', 'finishedDocumentId' => $recordId, 'expiresAt' => $this->expiresAt()]
			),
			register: self::REGISTER,
			schema: self::SCHEMA,
			uuid: $draftId
		);

		return $result + ['draftId' => $draftId, 'recordId' => $recordId];
	}//end finish()

	/**
	 * Load a draft through OpenRegister's access rules and require the user to own it.
	 *
	 * @param string $draftId The draft.
	 * @param string $userId  The session user.
	 *
	 * @return array<string, mixed> The draft.
	 *
	 * @throws Exception 404 when it does not exist, cannot be read, or belongs to someone else.
	 */
	private function ownDraft(string $draftId, string $userId): array {
		$notFound = new Exception(message: 'Draft not found', code: 404);
		try {
			$found = $this->objectService()->find(id: $draftId, register: self::REGISTER, schema: self::SCHEMA);
		} catch (Exception $e) {
			if ($e->getCode() === 503) {
				throw $e;
			}

			throw $notFound;
		}

		$draft = $this->toArray(result: $found);
		if ($draft === [] || $userId === '' || (string) ($draft['@self']['owner'] ?? '') !== $userId) {
			throw $notFound;
		}

		return $draft;
	}//end ownDraft()

	/**
	 * The generation options a draft finishes with.
	 *
	 * @param array<string, mixed> $draft  The draft.
	 * @param string               $userId The session user.
	 *
	 * @return array<string, mixed> The options.
	 */
	private function finishOptions(array $draft, string $userId): array {
		$options = (array) ($draft['outputOptions'] ?? []);
		$options['format'] = (string) ($draft['format'] ?? 'pdf');
		$options['userId'] = $userId;
		if (isset($draft['templateVersion']) === true) {
			$options['templateVersion'] = (int) $draft['templateVersion'];
		}

		if ((string) ($draft['wizardId'] ?? '') !== '') {
			$options['wizardContext'] = [
				'wizardId' => (string) $draft['wizardId'],
				'answers' => (array) ($draft['wizardAnswers'] ?? []),
			];
		}

		return $options;
	}//end finishOptions()

	/**
	 * The wizard fields of a draft opened from a wizard run.
	 *
	 * @param mixed $context The request's wizardContext.
	 *
	 * @return array<string, mixed> wizardId, wizardAnswers and wizardStep, or nothing.
	 */
	private function wizardFields(mixed $context): array {
		if (is_array($context) === false || (string) ($context['wizardId'] ?? '') === '') {
			return [];
		}

		return [
			'wizardId' => (string) $context['wizardId'],
			'wizardAnswers' => (array) ($context['answers'] ?? []),
			'wizardStep' => max(0, (int) ($context['step'] ?? 0)),
		];
	}//end wizardFields()

	/**
	 * The expiry of a draft saved now.
	 *
	 * @return string ISO 8601.
	 */
	private function expiresAt(): string {
		return $this->time->now()->add(new DateInterval('P' . self::LIFETIME_DAYS . 'D'))->format(DateTimeInterface::ATOM);
	}//end expiresAt()

	/**
	 * OpenRegister's object service.
	 *
	 * @return object The object service.
	 *
	 * @throws Exception 503 when OpenRegister is not available.
	 */
	private function objectService(): object {
		$service = $this->settings->getObjectService();
		if ($service === null) {
			throw new Exception(message: 'OpenRegister is not available', code: 503);
		}

		return $service;
	}//end objectService()

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

	/**
	 * The id of an object.
	 *
	 * @param array<string, mixed> $object The object.
	 *
	 * @return string The id, or ''.
	 */
	private function idOf(array $object): string {
		return (string) ($object['@self']['id'] ?? ($object['id'] ?? ($object['uuid'] ?? '')));
	}//end idOf()

	/**
	 * A draft's own fields, without OpenRegister's metadata.
	 *
	 * @param array<string, mixed> $draft The draft as read.
	 *
	 * @return array<string, mixed> The fields.
	 */
	private function withoutMetadata(array $draft): array {
		unset($draft['@self'], $draft['id'], $draft['uuid']);

		return $draft;
	}//end withoutMetadata()

	/**
	 * An integer, or null when the value is not one.
	 *
	 * @param mixed $value The value.
	 *
	 * @return int|null The integer.
	 */
	private function intOrNull(mixed $value): ?int {
		if (is_numeric($value) === true) {
			return (int) $value;
		}

		return null;
	}//end intOrNull()
}//end class
