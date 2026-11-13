<?php

/**
 * Template Scope
 *
 * Which templates apply to an object of a given register and schema.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/template-library-leaf/specs/template-management/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * Matches a template's `scope` against a host object's register and schema.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/template-library-leaf/specs/template-management/spec.md#requirement-template-scope-per-register-and-schema-req-tmpl-13
 */
class TemplateScope {

	/**
	 * The templates, in order, whose scope is empty or holds this register and schema.
	 *
	 * Scope entries match on slugs, which each app keeps frozen, so a scope
	 * written today still matches tomorrow. Entities are read through
	 * `jsonSerialize()`; anything that is not a template is skipped.
	 *
	 * @param array<string, mixed> $page     A listing page as OpenRegister returns it, templates under `results`.
	 * @param string               $register The host object's register slug.
	 * @param string               $schema   The host object's schema slug.
	 *
	 * @return list<array<string, mixed>> The applicable templates.
	 *
	 * @spec openspec/changes/template-library-leaf/specs/template-management/spec.md#requirement-template-scope-per-register-and-schema-req-tmpl-13
	 */
	public function applicable(array $page, string $register, string $schema): array {
		$register = trim($register);
		$schema = trim($schema);
		$applicable = [];

		$templates = ($page['results'] ?? []);
		if (is_array($templates) === false) {
			return [];
		}

		foreach ($templates as $template) {
			if ($template instanceof \JsonSerializable) {
				$template = $template->jsonSerialize();
			}

			if (is_array($template) === true && $this->appliesTo(template: $template, register: $register, schema: $schema) === true) {
				$applicable[] = $template;
			}
		}

		return $applicable;
	}//end applicable()

	/**
	 * Whether a template's scope is empty or names this register and schema.
	 *
	 * @param array<string, mixed> $template The template.
	 * @param string               $register The register slug.
	 * @param string               $schema   The schema slug.
	 *
	 * @return bool True when the template applies.
	 *
	 * @spec openspec/changes/template-library-leaf/specs/template-management/spec.md#requirement-template-scope-per-register-and-schema-req-tmpl-13
	 */
	public function appliesTo(array $template, string $register, string $schema): bool {
		$scope = ($template['scope'] ?? []);
		if ($scope === []) {
			return true;
		}

		// A scope that is not a list says nothing a host can match, so the
		// template is offered nowhere rather than everywhere.
		if (is_array($scope) === false) {
			return false;
		}

		foreach ($scope as $pair) {
			if (is_array($pair) === true
				&& trim((string)($pair['register'] ?? '')) === $register
				&& trim((string)($pair['schema'] ?? '')) === $schema
			) {
				return true;
			}
		}

		return false;
	}//end appliesTo()
}//end class
