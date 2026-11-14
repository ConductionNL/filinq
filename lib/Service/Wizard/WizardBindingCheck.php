<?php

/**
 * Warnings for wizard questions that do not fit the template's data
 *
 * The advisory half of the save-time check. A question that picks from
 * another register or schema than the template is bound to, a data path the
 * bound schema does not have, or an answer written over an object another
 * question picked: each is a warning, never a refusal, because the template
 * may still be bound or changed later.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Wizard
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Wizard;

/**
 * Binding and collision warnings for wizard definitions.
 *
 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-1
 */
class WizardBindingCheck {

	/**
	 * Warnings for a question that does not fit the template's binding.
	 *
	 * @param array<string, mixed>      $question        The question.
	 * @param string                    $name            How to name it.
	 * @param array<string, mixed>|null $template        The template.
	 * @param string[]|null             $boundProperties The bound schema's property names.
	 *
	 * @return string[] The warnings.
	 *
	 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-1
	 */
	public function warnings(array $question, string $name, ?array $template, ?array $boundProperties): array {
		$register = trim((string) ($template['boundRegister'] ?? ''));
		$schema = trim((string) ($template['boundSchema'] ?? ''));
		if ($template === null || $schema === '') {
			return [];
		}

		if (($question['type'] ?? '') === 'registerObject') {
			return $this->objectWarnings(question: $question, name: $name, register: $register, schema: $schema);
		}

		return $this->pathWarnings(question: $question, name: $name, schema: $schema, boundProperties: $boundProperties);

	}//end warnings()

	/**
	 * Warnings for a scalar answer written under a picked object's key.
	 *
	 * Ad-hoc data wins per top-level key (DataResolverService merges with
	 * array_merge), so an answer mapped to `dossier.title` replaces the whole
	 * dossier a register object question picked. The author is told; the
	 * wizard still saves.
	 *
	 * @param array<int, array<string, mixed>> $questions The questions.
	 *
	 * @return string[] The warnings.
	 *
	 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-1
	 */
	public function collisionWarnings(array $questions): array {
		$picked = [];
		foreach ($questions as $question) {
			if (($question['type'] ?? '') === 'registerObject' && trim((string) ($question['schema'] ?? '')) !== '') {
				$picked[(string) $question['schema']] = (string) ($question['key'] ?? '');
			}
		}

		$warnings = [];
		foreach ($questions as $question) {
			$path = trim((string) ($question['mapsTo'] ?? ''));
			$top = explode('.', $path)[0];
			if (($question['type'] ?? '') !== 'registerObject' && $path !== '' && isset($picked[$top]) === true) {
				$warnings[] = "Question \"{$question['key']}\" writes into {$top}, which replaces the whole {$top}"
					. " picked in question \"{$picked[$top]}\" in the template data.";
			}
		}

		return $warnings;

	}//end collisionWarnings()

	/**
	 * A register object question that picks from another register or schema.
	 *
	 * @param array<string, mixed> $question The question.
	 * @param string               $name     How to name it.
	 * @param string               $register The template's bound register ('' when unbound).
	 * @param string               $schema   The template's bound schema.
	 *
	 * @return string[] The warnings.
	 */
	private function objectWarnings(array $question, string $name, string $register, string $schema): array {
		$fits = (string) ($question['schema'] ?? '') === $schema
			&& ($register === '' || (string) ($question['register'] ?? '') === $register);
		if ($fits === true) {
			return [];
		}

		return ["Question \"{$name}\" picks from {$question['register']}/{$question['schema']}, not the template's {$register}/{$schema}."];

	}//end objectWarnings()

	/**
	 * A data path the template's bound schema does not have.
	 *
	 * @param array<string, mixed> $question        The question.
	 * @param string               $name            How to name it.
	 * @param string               $schema          The template's bound schema.
	 * @param string[]|null        $boundProperties The bound schema's property names.
	 *
	 * @return string[] The warnings.
	 */
	private function pathWarnings(array $question, string $name, string $schema, ?array $boundProperties): array {
		$path = trim((string) ($question['mapsTo'] ?? ''));
		if ($path === '' || $boundProperties === null) {
			return [];
		}

		$segments = explode('.', $path);
		$first = $segments[0];
		if ($first === $schema && count($segments) > 1) {
			$first = $segments[1];
		}

		if (in_array($first, $boundProperties, true) === true) {
			return [];
		}

		return ["Question \"{$name}\" maps to \"{$path}\", which the template's schema {$schema} does not have."];

	}//end pathWarnings()
}//end class
