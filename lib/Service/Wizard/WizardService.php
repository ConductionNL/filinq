<?php

/**
 * Guided document wizards
 *
 * Saves, reads and removes the wizard of a template, and prefills a run from
 * a register object. A save is refused with 422 when the definition cannot
 * run, 409 when the template already has another active wizard, and 423 when
 * someone else holds the template's edit lock: the wizard changes what the
 * template produces.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Wizard
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/guided-document-wizard/tasks.md#2-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Wizard;

use DateTimeImmutable;
use OCA\Filinq\Service\DataResolverService;
use OCA\Filinq\Service\TemplateService;
use OCA\OpenRegister\Db\SchemaMapper;
use Throwable;

/**
 * Wizard authoring and prefill.
 */
class WizardService {

	/**
	 * Minutes a template edit lock holds, as in TemplateService.
	 *
	 * @var int
	 */
	private const LOCK_MINUTES = 15;

	/**
	 * Constructor.
	 *
	 * @param WizardRepository          $repository The wizard store.
	 * @param WizardDefinitionValidator $validator  Save-time checks.
	 * @param TemplateService           $templates  The templates the wizards front.
	 * @param DataResolverService       $resolver   Resolves the entry object of a prefill.
	 * @param SchemaMapper              $schemas    Reads the property names of a template's bound schema.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly WizardRepository $repository,
		private readonly WizardDefinitionValidator $validator,
		private readonly TemplateService $templates,
		private readonly DataResolverService $resolver,
		private readonly SchemaMapper $schemas,
	) {

	}//end __construct()

	/**
	 * Save a new or changed wizard.
	 *
	 * @param array<string, mixed> $wizard The definition.
	 * @param string               $userId The author.
	 * @param string|null          $uuid   The wizard to change, null for a new one.
	 *
	 * @return array{wizard: array<string, mixed>, warnings: string[]} The stored wizard and the binding warnings.
	 *
	 * @throws WizardRefused 404, 409, 422 or 423.
	 *
	 * @spec openspec/changes/guided-document-wizard/tasks.md#2-1
	 */
	public function save(array $wizard, string $userId, ?string $uuid=null): array {
		$fields = $this->fields(wizard: $wizard);
		if ($uuid !== null && $this->repository->find(uuid: $uuid) === null) {
			throw new WizardRefused(message: 'Wizard not found', code: 404);
		}

		$template = $this->template(templateId: (string) ($fields['templateId'] ?? ''));
		$checked = $this->validator->check(wizard: $fields, template: $template, boundProperties: $this->boundProperties(template: $template));
		if ($checked['errors'] !== []) {
			throw new WizardRefused(message: 'The wizard has errors', code: 422, errors: $checked['errors']);
		}

		if ($template === null) {
			throw new WizardRefused(message: 'Template not found', code: 422, errors: ['templateId' => 'No template with this id.']);
		}

		if ($this->lockedByOther(template: $template, userId: $userId) === true) {
			throw new WizardRefused(message: 'The template is locked by another user', code: 423);
		}

		if ($fields['active'] === true && $this->otherActive(templateId: (string) $fields['templateId'], uuid: $uuid) !== null) {
			throw new WizardRefused(message: 'This template already has an active wizard', code: 409);
		}

		return [
			'wizard' => $this->repository->save(wizard: $fields, uuid: $uuid),
			'warnings' => $checked['warnings'],
		];

	}//end save()

	/**
	 * One wizard.
	 *
	 * @param string $uuid The uuid.
	 *
	 * @return array<string, mixed> The wizard.
	 *
	 * @throws WizardRefused 404.
	 *
	 * @spec openspec/changes/guided-document-wizard/tasks.md#2-1
	 */
	public function requireWizard(string $uuid): array {
		$wizard = $this->repository->find(uuid: $uuid);
		if ($wizard === null) {
			throw new WizardRefused(message: 'Wizard not found', code: 404);
		}

		return $wizard;

	}//end requireWizard()

	/**
	 * The active wizard of a template, if any.
	 *
	 * @param string $templateId The template uuid.
	 *
	 * @return array<string, mixed>|null The wizard.
	 *
	 * @spec openspec/changes/guided-document-wizard/tasks.md#2-1
	 */
	public function activeFor(string $templateId): ?array {
		return $this->otherActive(templateId: $templateId, uuid: null);

	}//end activeFor()

	/**
	 * The active wizards that ask for an object of this register and schema.
	 *
	 * The object-driven entry point: a page showing such an object offers
	 * these wizards, started with the object as their entry.
	 *
	 * @param string $register The register.
	 * @param string $schema   The schema.
	 *
	 * @return array<int, array<string, mixed>> The wizards.
	 *
	 * @spec openspec/changes/guided-document-wizard/tasks.md#4-3
	 */
	public function forObject(string $register, string $schema): array {
		$fits = [];
		foreach ($this->repository->active() as $wizard) {
			foreach ((array) ($wizard['questions'] ?? []) as $question) {
				if (($question['type'] ?? '') === 'registerObject' && ($question['register'] ?? '') === $register && ($question['schema'] ?? '') === $schema) {
					$fits[] = $wizard;
					break;
				}
			}
		}

		return $fits;

	}//end forObject()

	/**
	 * Remove a wizard.
	 *
	 * @param string $uuid   The uuid.
	 * @param string $userId The caller.
	 *
	 * @return void
	 *
	 * @throws WizardRefused 404 or 423.
	 *
	 * @spec openspec/changes/guided-document-wizard/tasks.md#2-1
	 */
	public function delete(string $uuid, string $userId): void {
		$wizard = $this->requireWizard(uuid: $uuid);
		$template = $this->template(templateId: (string) ($wizard['templateId'] ?? ''));
		if ($template !== null && $this->lockedByOther(template: $template, userId: $userId) === true) {
			throw new WizardRefused(message: 'The template is locked by another user', code: 423);
		}

		$this->repository->delete(uuid: $uuid);

	}//end delete()

	/**
	 * Suggested answers for a run started from a register object.
	 *
	 * The object is resolved through DataResolverService as the caller, so
	 * prefill sees only what the caller may read. A register object question
	 * on the entry object's register and schema gets its id; a scalar question
	 * whose `mapsTo` path resolves gets that value; every other question is
	 * unresolved and asked.
	 *
	 * @param string               $uuid  The wizard.
	 * @param array<string, mixed> $entry The entry object: register, schema, id.
	 *
	 * @return array{answers: array<string, mixed>, unresolved: string[]} The suggestions.
	 *
	 * @throws WizardRefused 404 for an unknown wizard, 422 for an entry without register, schema and id.
	 *
	 * @spec openspec/changes/guided-document-wizard/tasks.md#2-5
	 */
	public function prefill(string $uuid, array $entry): array {
		$wizard = $this->requireWizard(uuid: $uuid);
		$ref = [
			'register' => trim((string) ($entry['register'] ?? '')),
			'schema' => trim((string) ($entry['schema'] ?? '')),
			'id' => trim((string) ($entry['id'] ?? '')),
		];
		if (in_array('', $ref, true) === true) {
			throw new WizardRefused(message: 'The entry object needs register, schema and id', code: 422);
		}

		$resolution = $this->resolver->resolve(dataRefs: [$ref]);
		$found = $resolution['errors'] === [];
		$data = (array) ($resolution['data'] ?? []);
		$answers = [];
		$unresolved = [];
		foreach ((array) ($wizard['questions'] ?? []) as $question) {
			$key = (string) ($question['key'] ?? '');
			$value = null;
			if ($found === true) {
				$value = $this->suggestion(question: (array) $question, ref: $ref, data: $data);
			}

			if ($value === null) {
				$unresolved[] = $key;
				continue;
			}

			$answers[$key] = $value;
		}

		return ['answers' => $answers, 'unresolved' => $unresolved];

	}//end prefill()

	/**
	 * The suggested answer of one question, or null.
	 *
	 * @param array<string, mixed> $question The question.
	 * @param array<string, string> $ref     The entry object.
	 * @param array<string, mixed> $data     The resolved data, keyed by schema.
	 *
	 * @return mixed The suggestion.
	 */
	private function suggestion(array $question, array $ref, array $data): mixed {
		if (($question['type'] ?? '') === 'registerObject') {
			$fits = (string) ($question['register'] ?? '') === $ref['register'] && (string) ($question['schema'] ?? '') === $ref['schema'];
			if ($fits === false) {
				return null;
			}

			return $ref['id'];
		}

		$path = trim((string) ($question['mapsTo'] ?? ''));
		if ($path === '') {
			return null;
		}

		$value = WizardAnswers::readPath(data: $data, path: $path);
		if ($value === null) {
			// A path may also be written relative to the entry object.
			$value = WizardAnswers::readPath(data: (array) ($data[$ref['schema']] ?? []), path: $path);
		}

		if (is_scalar($value) === false) {
			return null;
		}

		return $value;

	}//end suggestion()

	/**
	 * The fields of a wizard as stored.
	 *
	 * @param array<string, mixed> $wizard The request body.
	 *
	 * @return array<string, mixed> The fields.
	 */
	private function fields(array $wizard): array {
		$fields = array_intersect_key($wizard, array_flip(['name', 'description', 'namespace', 'templateId', 'active', 'questions']));
		$fields['active'] = (($wizard['active'] ?? true) !== false);
		$fields['questions'] = array_values((array) ($wizard['questions'] ?? []));

		return $fields;

	}//end fields()

	/**
	 * The template, or null.
	 *
	 * @param string $templateId The uuid.
	 *
	 * @return array<string, mixed>|null The template.
	 */
	private function template(string $templateId): ?array {
		if (trim($templateId) === '') {
			return null;
		}

		try {
			return $this->templates->getTemplate(id: $templateId);
		} catch (Throwable) {
			return null;
		}

	}//end template()

	/**
	 * The property names of the schema a template is bound to.
	 *
	 * Only a template that declares `boundSchema` (office-template-authoring)
	 * has one; for every other template the binding check is skipped.
	 *
	 * @param array<string, mixed>|null $template The template.
	 *
	 * @return string[]|null The names, or null when unknown.
	 */
	private function boundProperties(?array $template): ?array {
		$slug = trim((string) ($template['boundSchema'] ?? ''));
		if ($slug === '') {
			return null;
		}

		try {
			$schema = $this->schemas->find(id: $slug);
		} catch (Throwable) {
			return null;
		}

		if (is_object($schema) === false || method_exists($schema, 'getProperties') === false) {
			return null;
		}

		return array_map('strval', array_keys((array) $schema->getProperties()));

	}//end boundProperties()

	/**
	 * Whether someone else holds the template's edit lock.
	 *
	 * @param array<string, mixed> $template The template.
	 * @param string               $userId   The caller.
	 *
	 * @return bool True when locked by another user and not expired.
	 */
	private function lockedByOther(array $template, string $userId): bool {
		$holder = (string) ($template['lockedBy'] ?? '');
		if ($holder === '' || $holder === $userId) {
			return false;
		}

		try {
			$since = new DateTimeImmutable((string) ($template['lockedAt'] ?? ''));
		} catch (Throwable) {
			return true;
		}

		return $since > new DateTimeImmutable('-' . self::LOCK_MINUTES . ' minutes');

	}//end lockedByOther()

	/**
	 * Another active wizard on the template.
	 *
	 * @param string      $templateId The template.
	 * @param string|null $uuid       The wizard being saved, which does not count.
	 *
	 * @return array<string, mixed>|null The other active wizard.
	 */
	private function otherActive(string $templateId, ?string $uuid): ?array {
		foreach ($this->repository->forTemplate(templateId: $templateId) as $wizard) {
			if (($wizard['active'] ?? true) !== false && ($wizard['uuid'] ?? '') !== $uuid) {
				return $wizard;
			}
		}

		return null;

	}//end otherActive()
}//end class
