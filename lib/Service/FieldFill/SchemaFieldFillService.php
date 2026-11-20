<?php

/**
 * Schema field fill service
 *
 * A text model reads a file filed on a record and suggests values for the
 * fields the record's schema declares; a person then accepts, changes or
 * rejects each one, and only those values are written, with that person's rights.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\FieldFill
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\FieldFill;

use OCA\Filinq\Exception\FieldFillRefused;
use OCA\Filinq\Kernel\Contracts\SchemaFieldFillServiceInterface;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentTextSource;
use OCA\Filinq\Service\Extraction\AiPrompt;
use OCA\Filinq\Service\IntakeRepository;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindTarget;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use OCA\Filinq\Service\LocalTextAi;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Suggests the declared fields of a record from one of its files, and writes the ones a person accepts.
 *
 * @category Service
 * @package  OCA\Filinq\Service\FieldFill
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) The service joins the file, the model, the profile and the record.
 */
class SchemaFieldFillService implements SchemaFieldFillServiceInterface {

	/**
	 * The schema slug of a suggestion.
	 *
	 * @var string
	 */
	public const SCHEMA = 'fieldSuggestion';

	/**
	 * The app-config key of the switch; off on a fresh install (REQ-EMF-004).
	 *
	 * @var string
	 */
	public const ENABLED_KEY = 'field_fill_enabled';

	/**
	 * The confidence of a model-suggested value, as the financial extraction uses.
	 *
	 * @var float
	 */
	public const AI_FILL_CONFIDENCE = 0.55;

	/**
	 * Added to the confidence when the value occurs in the document text (design D3).
	 *
	 * @var float
	 */
	public const FOUND_BOOST = 0.2;

	/**
	 * How much document text goes into the prompt, in characters.
	 *
	 * @var int
	 */
	public const PROMPT_LIMIT = 4000;

	/**
	 * The decisions a person can take on a field.
	 *
	 * @var array<int, string>
	 */
	public const DECISIONS = ['accept', 'change', 'reject'];

	/**
	 * The answer when the switch is off or no text model is available.
	 *
	 * @var string
	 */
	public const NO_MODEL = 'No text model is available on this server.';

	/**
	 * The answer for a schema without an active profile.
	 *
	 * @var string
	 */
	public const NO_PROFILE = 'No fields are declared for model filling on this schema.';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolves OpenRegister's ObjectService.
	 * @param FieldFillProfileService       $profiles       The declared profiles and their schemas.
	 * @param FieldValueCheck               $valueCheck     Checks a value against its property.
	 * @param TextPassage                   $passage        Finds a value in the text.
	 * @param LocalTextAi                   $textModel      The text model, through Nextcloud task processing.
	 * @param DocumentTextSource            $documentText   The text of a file, with OCR for a scan.
	 * @param FieldSuggestionDecisions      $decisions      Reads records and writes the clerk's decisions.
	 * @param IAppConfig                    $appConfig      Holds the switch.
	 * @param LoggerInterface               $logger         Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly FieldFillProfileService $profiles,
		private readonly FieldValueCheck $valueCheck,
		private readonly TextPassage $passage,
		private readonly LocalTextAi $textModel,
		private readonly DocumentTextSource $documentText,
		private readonly FieldSuggestionDecisions $decisions,
		private readonly IAppConfig $appConfig,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Whether model filling is switched on, and which provider would read the documents.
	 *
	 * @return array{enabled: bool, provider: string|null, available: bool} The state.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	public function status(): array {
		$enabled  = $this->appConfig->getValueBool('filinq', self::ENABLED_KEY, false);
		$provider = $this->textModel->textProviderName();

		return [
			'enabled' => $enabled,
			'provider' => $provider,
			'available' => ($enabled === true && $provider !== null),
		];

	}//end status()

	/**
	 * Switch model filling on or off.
	 *
	 * @param bool $enabled The new state.
	 *
	 * @return array{enabled: bool, provider: string|null, available: bool} The state after the change.
	 *
	 * @throws FieldFillRefused 503 when it is switched on without a text model.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	public function setEnabled(bool $enabled): array {
		if ($enabled === true && $this->textModel->textProviderName() === null) {
			throw new FieldFillRefused(message: self::NO_MODEL, code: 503);
		}

		$this->appConfig->setValueBool('filinq', self::ENABLED_KEY, $enabled);

		return $this->status();

	}//end setEnabled()

	/**
	 * Ask the model for the declared fields of a record, read from one of its files.
	 *
	 * @param int    $fileId   The Nextcloud file id.
	 * @param string $register The record's register.
	 * @param string $schema   The record's schema.
	 * @param string $objectId The record.
	 * @param string $userId   The session user, who asked.
	 *
	 * @return array<string, mixed> The stored suggestion, with the record's current values.
	 *
	 * @throws FieldFillRefused 503 without a model, 404 for a record or file the user cannot read,
	 *                          409 without a profile, 422 when no text could be read.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	public function suggest(int $fileId, string $register, string $schema, string $objectId, string $userId): array {
		$manager = $this->availableModel();

		$record = $this->decisions->record(register: $register, schema: $schema, objectId: $objectId);

		$text = $this->documentText;
		if ($text->readable(fileId: $fileId) === false) {
			throw new FieldFillRefused(message: 'The file was not found or you may not read it.', code: 404);
		}

		$profile = $this->profiles->activeFor(register: $register, schema: $schema);
		if ($profile === null) {
			throw new FieldFillRefused(message: self::NO_PROFILE, code: 409);
		}

		$properties = $this->declaredProperties(profile: $profile, schema: $schema);
		$content    = (string)$text->resolveText(fileId: $fileId)['text'];
		if (trim($content) === '') {
			throw new FieldFillRefused(message: 'No text could be read from the document.', code: 422);
		}

		$answer = $this->ask(manager: $manager, text: $content, properties: $properties, userId: $userId);
		$result = $this->checked(answer: $answer, properties: $properties, text: $content);

		$suggestion = [
			'fileId' => $fileId,
			'register' => $register,
			'schema' => $schema,
			'objectId' => $objectId,
			'requestedBy' => $userId,
			'suggestions' => $result['suggestions'],
			'droppedFields' => $result['dropped'],
			'truncated' => (mb_strlen($content) > self::PROMPT_LIMIT),
			'provider' => (string)$this->textModel->textProviderName(),
			'decisions' => [],
			'status' => 'suggested',
		];

		$saved = $this->decisions->row(object: $this->objectResolver->resolve()->saveObject(
			object: $suggestion,
			options: new ObjectSaveOptions(
				target: new ObjectFindTarget(register: IntakeRepository::REGISTER, schema: self::SCHEMA)
			)
		));

		$stored = array_merge($suggestion, $saved);
		$stored['currentValues'] = $this->currentValues(record: $record, fields: array_keys($properties));

		return $stored;

	}//end suggest()

	/**
	 * The text model, when model filling is on and one is there.
	 *
	 * @return array<string, mixed> The task manager.
	 *
	 * @throws FieldFillRefused 503 when it is off or no model is there.
	 */
	private function availableModel(): array {
		$manager = $this->textModel->manager();
		if ($manager === null || $this->status()['available'] === false) {
			throw new FieldFillRefused(message: self::NO_MODEL, code: 503);
		}

		return $manager;

	}//end availableModel()

	/**
	 * Take the person's decision on every suggested field and write what they accepted or changed.
	 *
	 * @param string               $id        The suggestion.
	 * @param array<string, mixed> $decisions Per field: `decision` (accept, change or reject) and, for change, `value`.
	 * @param string               $userId    The session user, who decides.
	 *
	 * @return array<string, mixed> The decided suggestion.
	 *
	 * @throws FieldFillRefused 404 for an unknown suggestion or record, 403 for someone else's suggestion
	 *                          or a record the user may not change, 409 when already decided,
	 *                          400 for a missing decision or a changed value that does not fit.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	public function decide(string $id, array $decisions, string $userId): array {
		return $this->decisions->decide(id: $id, decisions: $decisions, userId: $userId);

	}//end decide()

	/**
	 * Build the prompt from the declared fields and the text.
	 *
	 * @param string                              $text       The document text.
	 * @param array<string, array<string, mixed>> $properties The declared properties by name.
	 *
	 * @return string The prompt.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	public function buildPrompt(string $text, array $properties): string {
		$lines = [];
		foreach ($properties as $name => $property) {
			$lines[] = self::promptLine(name: (string)$name, property: (array)$property);
		}

		return 'You fill in the fields of a record from the text of a document filed on it. '
			. 'Return ONLY a strict JSON object (no markdown, no commentary) whose keys are exactly these field names. '
			. 'Use null for a field the text does not state. Do not invent values. Copy values as they appear in the text '
			. 'where the field type allows it; write a date as YYYY-MM-DD.' . "\n\nFields:\n" . implode("\n", $lines)
			. "\n\nDocument text:\n" . mb_substr($text, 0, self::PROMPT_LIMIT);

	}//end buildPrompt()

	/**
	 * One field of the prompt: its name, type, format, title, description and choices.
	 *
	 * @param string               $name     The field name.
	 * @param array<string, mixed> $property The field's property.
	 *
	 * @return string The line.
	 */
	private static function promptLine(string $name, array $property): string {
		$line = '- ' . $name;
		foreach (['type', 'format', 'title', 'description'] as $key) {
			$value = ($property[$key] ?? null);
			if (is_scalar($value) === true && (string)$value !== '') {
				$line .= '; ' . $key . ': ' . (string)$value;
			}
		}

		if (is_array($property['enum'] ?? null) === true) {
			$line .= '; one of: ' . implode(', ', array_map(static fn (mixed $v): string => (string)json_encode($v), $property['enum']));
		}

		return $line;

	}//end promptLine()

	/**
	 * The model's answer as an array of values by field.
	 *
	 * @param array{type: string, manager: object} $manager    The resolved manager.
	 * @param string                               $text       The document text.
	 * @param array<string, array<string, mixed>>  $properties The declared properties.
	 * @param string                               $userId     The user the task runs for.
	 *
	 * @return array<string, mixed> The answer; empty when the model failed or did not answer JSON.
	 */
	private function ask(array $manager, string $text, array $properties, string $userId): array {
		try {
			$output  = $this->textModel->run(
				manager: $manager,
				prompt: new AiPrompt(text: $this->buildPrompt(text: $text, properties: $properties), requestedBy: $userId)
			);
			$decoded = json_decode($this->textModel->stripCodeFences(text: $output), associative: true);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[SchemaFieldFillService] the text model failed',
				context: ['error' => $e->getMessage()]
			);
			throw new FieldFillRefused(message: self::NO_MODEL, code: 503);
		}

		if (is_array($decoded) === false) {
			return [];
		}

		return $decoded;

	}//end ask()

	/**
	 * Keep the answers that fit their field, with their passage; count the rest.
	 *
	 * @param array<string, mixed>                $answer     The model's answer.
	 * @param array<string, array<string, mixed>> $properties The declared properties.
	 * @param string                              $text       The document text.
	 *
	 * @return array{suggestions: array<string, array<string, mixed>>, dropped: int} The kept values and the dropped count.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	public function checked(array $answer, array $properties, string $text): array {
		$suggestions = [];
		$dropped     = 0;
		foreach ($properties as $name => $property) {
			$value = ($answer[$name] ?? null);
			if ($value === null || $value === '') {
				continue;
			}

			if ($this->valueCheck->fits(value: $value, property: $property) === false) {
				$dropped++;
				continue;
			}

			$suggestions[$name] = $this->suggestionOf(value: $value, found: $this->passage->around(text: $text, value: $value));
		}//end foreach

		return ['suggestions' => $suggestions, 'dropped' => $dropped];

	}//end checked()

	/**
	 * One checked value with its passage and confidence (design D3).
	 *
	 * @param mixed       $value The value.
	 * @param string|null $found The passage around it, or null when it is not in the text.
	 *
	 * @return array{value: mixed, confidence: float, foundInText: bool, passage: string|null}
	 */
	private function suggestionOf(mixed $value, ?string $found): array {
		$confidence = self::AI_FILL_CONFIDENCE;
		if ($found !== null) {
			$confidence += self::FOUND_BOOST;
		}

		return [
			'value' => $value,
			'confidence' => round($confidence, 2),
			'foundInText' => ($found !== null),
			'passage' => $found,
		];

	}//end suggestionOf()

	/**
	 * The declared properties of a profile, leaving out any bsn field and any field the schema lacks.
	 *
	 * @param array<string, mixed> $profile The profile.
	 * @param string               $schema  The schema.
	 *
	 * @return array<string, array<string, mixed>> The properties by name.
	 *
	 * @throws FieldFillRefused 409 when none of the declared fields can be filled.
	 */
	private function declaredProperties(array $profile, string $schema): array {
		$all      = $this->profiles->properties(schema: $schema);
		$declared = [];
		foreach ($this->profiles->fieldNames(profile: $profile) as $field) {
			if (isset($all[$field]) === true && $this->profiles->isBsn(property: $all[$field]) === false) {
				$declared[$field] = $all[$field];
			}
		}

		if ($declared === []) {
			throw new FieldFillRefused(message: self::NO_PROFILE, code: 409);
		}

		return $declared;

	}//end declaredProperties()

	/**
	 * The record's current value of each field.
	 *
	 * @param array<string, mixed> $record The record.
	 * @param array<int, string>   $fields The fields.
	 *
	 * @return array<string, mixed> The values by field.
	 */
	private function currentValues(array $record, array $fields): array {
		$values = [];
		foreach ($fields as $field) {
			$values[$field] = ($record[$field] ?? null);
		}

		return $values;

	}//end currentValues()
}//end class
