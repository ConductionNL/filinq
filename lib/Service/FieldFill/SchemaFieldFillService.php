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

use DateTimeImmutable;
use DateTimeInterface;
use OCA\Filinq\Exception\FieldFillRefused;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentTextSource;
use OCA\Filinq\Service\Extraction\AiPrompt;
use OCA\Filinq\Service\IntakeRepository;
use OCA\Filinq\Service\LocalTextAi;
use OCA\Filinq\Service\OcrService;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;
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
class SchemaFieldFillService {

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
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService.
	 * @param FieldFillProfileService       $profiles       The declared profiles and their schemas.
	 * @param FieldValueCheck               $valueCheck     Checks a value against its property.
	 * @param TextPassage                   $passage        Finds a value in the text.
	 * @param ContainerInterface            $container      DI container, for the text model.
	 * @param IRootFolder                   $rootFolder     Root folder, to read the file.
	 * @param IUserSession                  $userSession    The session user.
	 * @param OcrService                    $ocrService     OCR for a file without embedded text.
	 * @param IAppConfig                    $appConfig      Holds the switch.
	 * @param IGroupManager                 $groupManager   Tells an administrator apart.
	 * @param LoggerInterface               $logger         Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly FieldFillProfileService $profiles,
		private readonly FieldValueCheck $valueCheck,
		private readonly TextPassage $passage,
		private readonly ContainerInterface $container,
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $userSession,
		private readonly OcrService $ocrService,
		private readonly IAppConfig $appConfig,
		private readonly IGroupManager $groupManager,
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
		$provider = $this->localAi()->textProviderName();

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
		if ($enabled === true && $this->localAi()->textProviderName() === null) {
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
		$manager = $this->localAi()->manager();
		if ($manager === null || $this->status()['available'] === false) {
			throw new FieldFillRefused(message: self::NO_MODEL, code: 503);
		}

		$record = $this->readRecord(register: $register, schema: $schema, objectId: $objectId);

		$text = $this->textSource();
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
			'provider' => (string)$this->localAi()->textProviderName(),
			'decisions' => [],
			'status' => 'suggested',
		];

		$saved = $this->toArray(object: $this->objectResolver->resolve()->saveObject(
			object: $suggestion,
			register: IntakeRepository::REGISTER,
			schema: self::SCHEMA
		));

		$stored = array_merge($suggestion, $this->fieldsOf(row: $saved));
		$stored['currentValues'] = $this->currentValues(record: $record, fields: array_keys($properties));

		return $stored;

	}//end suggest()

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
		$suggestion = $this->readSuggestion(id: $id, userId: $userId);
		$properties = $this->profiles->properties(schema: (string)$suggestion['schema']);
		$suggested  = (array)($suggestion['suggestions'] ?? []);
		$now        = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);

		$updates = [];
		$taken   = [];
		foreach ($suggested as $field => $entry) {
			$field    = (string)$field;
			$decision = $this->decisionFor(decisions: $decisions, field: $field);
			$value    = $this->decidedValue(decision: $decision, entry: (array)$entry, property: ($properties[$field] ?? []), field: $field);

			if ($decision['decision'] !== 'reject') {
				$updates[$field] = $value;
			}

			$taken[] = [
				'field' => $field,
				'decision' => $decision['decision'],
				'suggested' => ($entry['value'] ?? null),
				'written' => $value,
				'decidedBy' => $userId,
				'decidedAt' => $now,
			];
		}

		if ($updates !== []) {
			$this->writeRecord(suggestion: $suggestion, updates: $updates);
		}

		$suggestion['decisions'] = $taken;
		$suggestion['decidedBy'] = $userId;
		$suggestion['decidedAt'] = $now;
		$suggestion['status']    = 'decided';

		$object = $suggestion;
		unset($object['uuid'], $object['@self'], $object['id']);
		$this->objectResolver->resolve()->saveObject(
			object: $object,
			register: IntakeRepository::REGISTER,
			schema: self::SCHEMA,
			uuid: $id
		);

		return $suggestion;

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
			$line = '- ' . $name;
			foreach (['type', 'format', 'title', 'description'] as $key) {
				if (isset($property[$key]) === true && is_scalar($property[$key]) === true && (string)$property[$key] !== '') {
					$line .= '; ' . $key . ': ' . (string)$property[$key];
				}
			}

			if (isset($property['enum']) === true && is_array($property['enum']) === true) {
				$line .= '; one of: ' . implode(', ', array_map(static fn (mixed $v): string => (string)json_encode($v), $property['enum']));
			}

			$lines[] = $line;
		}

		return 'You fill in the fields of a record from the text of a document filed on it. '
			. 'Return ONLY a strict JSON object (no markdown, no commentary) whose keys are exactly these field names. '
			. 'Use null for a field the text does not state. Do not invent values. Copy values as they appear in the text '
			. 'where the field type allows it; write a date as YYYY-MM-DD.' . "\n\nFields:\n" . implode("\n", $lines)
			. "\n\nDocument text:\n" . mb_substr($text, 0, self::PROMPT_LIMIT);

	}//end buildPrompt()

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
			$output  = $this->localAi()->run(
				manager: $manager,
				prompt: new AiPrompt(text: $this->buildPrompt(text: $text, properties: $properties), requestedBy: $userId)
			);
			$decoded = json_decode($this->localAi()->stripCodeFences(text: $output), associative: true);
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

			$found = $this->passage->around(text: $text, value: $value);

			$confidence = self::AI_FILL_CONFIDENCE;
			if ($found !== null) {
				$confidence += self::FOUND_BOOST;
			}

			$suggestions[$name] = [
				'value' => $value,
				'confidence' => round($confidence, 2),
				'foundInText' => ($found !== null),
				'passage' => $found,
			];
		}//end foreach

		return ['suggestions' => $suggestions, 'dropped' => $dropped];

	}//end checked()

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
	 * The record, read with the session user's rights.
	 *
	 * @param string $register The register.
	 * @param string $schema   The schema.
	 * @param string $objectId The record.
	 *
	 * @return array<string, mixed> The record.
	 *
	 * @throws FieldFillRefused 404 when it is not there or the user may not read it.
	 */
	private function readRecord(string $register, string $schema, string $objectId): array {
		if ($register === '' || $schema === '' || $objectId === '') {
			throw new FieldFillRefused(message: 'Name the record: register, schema and id.', code: 400);
		}

		try {
			$found = $this->objectResolver->resolve()->find(id: $objectId, register: $register, schema: $schema);
		} catch (Throwable $e) {
			$found = null;
		}

		if ($found === null) {
			throw new FieldFillRefused(message: 'The record was not found or you may not read it.', code: 404);
		}

		return $this->fieldsOf(row: $this->toArray(object: $found));

	}//end readRecord()

	/**
	 * A suggestion the user may decide, still undecided.
	 *
	 * @param string $id     The suggestion.
	 * @param string $userId The session user.
	 *
	 * @return array<string, mixed> The suggestion.
	 *
	 * @throws FieldFillRefused 404, 403 or 409.
	 */
	private function readSuggestion(string $id, string $userId): array {
		try {
			$found = $this->objectResolver->resolve()->find(id: $id, register: IntakeRepository::REGISTER, schema: self::SCHEMA);
		} catch (Throwable $e) {
			$found = null;
		}

		if ($found === null) {
			throw new FieldFillRefused(message: 'The suggestion was not found.', code: 404);
		}

		$suggestion = $this->fieldsOf(row: $this->toArray(object: $found));
		if ((string)($suggestion['requestedBy'] ?? '') !== $userId && $this->groupManager->isAdmin($userId) === false) {
			throw new FieldFillRefused(message: 'Only the person who asked for the suggestion decides it.', code: 403);
		}

		if (($suggestion['status'] ?? 'suggested') !== 'suggested') {
			throw new FieldFillRefused(message: 'This suggestion has already been decided.', code: 409);
		}

		return $suggestion;

	}//end readSuggestion()

	/**
	 * The decision for one field.
	 *
	 * @param array<string, mixed> $decisions The decisions by field.
	 * @param string               $field     The field.
	 *
	 * @return array{decision: string, value: mixed} The decision.
	 *
	 * @throws FieldFillRefused 400 when the field has no valid decision.
	 */
	private function decisionFor(array $decisions, string $field): array {
		$given = ($decisions[$field] ?? null);
		if (is_string($given) === true) {
			$given = ['decision' => $given];
		}

		$decision = '';
		if (is_array($given) === true) {
			$decision = (string)($given['decision'] ?? '');
		}
		if (in_array($decision, self::DECISIONS, true) === false) {
			throw new FieldFillRefused(
				message: sprintf('The field "%s" needs a decision: accept, change or reject.', $field),
				code: 400
			);
		}

		return ['decision' => $decision, 'value' => ($given['value'] ?? null)];

	}//end decisionFor()

	/**
	 * The value a decision writes.
	 *
	 * @param array{decision: string, value: mixed} $decision The decision.
	 * @param array<string, mixed>                  $entry    The suggested entry.
	 * @param array<string, mixed>                  $property The field's property.
	 * @param string                                $field    The field.
	 *
	 * @return mixed The value; null for a rejection.
	 *
	 * @throws FieldFillRefused 400 when a changed value does not fit the field.
	 */
	private function decidedValue(array $decision, array $entry, array $property, string $field): mixed {
		if ($decision['decision'] === 'reject') {
			return null;
		}

		if ($decision['decision'] === 'accept') {
			return ($entry['value'] ?? null);
		}

		if ($this->valueCheck->fits(value: $decision['value'], property: $property) === false) {
			throw new FieldFillRefused(
				message: sprintf('The changed value for "%s" does not fit the field.', $field),
				code: 400
			);
		}

		return $decision['value'];

	}//end decidedValue()

	/**
	 * Write the accepted and changed values to the record, as the session user.
	 *
	 * @param array<string, mixed> $suggestion The suggestion.
	 * @param array<string, mixed> $updates    The values by field.
	 *
	 * @return void
	 *
	 * @throws FieldFillRefused 404 or 403 when the record cannot be changed with the user's rights.
	 */
	private function writeRecord(array $suggestion, array $updates): void {
		$register = (string)$suggestion['register'];
		$schema   = (string)$suggestion['schema'];
		$objectId = (string)$suggestion['objectId'];

		$record = $this->readRecord(register: $register, schema: $schema, objectId: $objectId);
		unset($record['@self'], $record['id'], $record['uuid']);

		try {
			// RBAC and multitenancy stay on: the write is the clerk's, with the clerk's rights (design D4).
			$this->objectResolver->resolve()->saveObject(
				object: array_merge($record, $updates),
				register: $register,
				schema: $schema,
				uuid: $objectId
			);
		} catch (Throwable $e) {
			$this->logger->info(
				message: '[SchemaFieldFillService] the record write was refused',
				context: ['error' => $e->getMessage()]
			);
			throw new FieldFillRefused(message: 'You may not change this record.', code: 403);
		}

	}//end writeRecord()

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

	/**
	 * A row's fields: its wrapped `object` payload when it has one, with the uuid.
	 *
	 * @param array<string, mixed> $row The row.
	 *
	 * @return array<string, mixed> The fields.
	 */
	private function fieldsOf(array $row): array {
		$fields = $row;
		if (isset($row['object']) === true && is_array($row['object']) === true) {
			$fields = $row['object'];
		}

		$uuid = (string)($fields['uuid'] ?? ($row['uuid'] ?? ($row['@self']['id'] ?? ($row['id'] ?? ''))));
		if ($uuid !== '') {
			$fields['uuid'] = $uuid;
		}

		return $fields;

	}//end fieldsOf()

	/**
	 * An ObjectService result as an array.
	 *
	 * @param mixed $object The entity or array.
	 *
	 * @return array<string, mixed> The array.
	 */
	private function toArray(mixed $object): array {
		if (is_object($object) === true && method_exists($object, 'jsonSerialize') === true) {
			return (array)$object->jsonSerialize();
		}

		return (array)$object;

	}//end toArray()

	/**
	 * The text model access.
	 *
	 * @return LocalTextAi The access.
	 */
	private function localAi(): LocalTextAi {
		return new LocalTextAi(container: $this->container);

	}//end localAi()

	/**
	 * The file text access, for the session user.
	 *
	 * @return DocumentTextSource The access.
	 */
	private function textSource(): DocumentTextSource {
		return new DocumentTextSource(
			rootFolder: $this->rootFolder,
			userSession: $this->userSession,
			ocrService: $this->ocrService,
			logger: $this->logger
		);

	}//end textSource()
}//end class
