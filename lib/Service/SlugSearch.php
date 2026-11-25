<?php

/**
 * SlugSearch: one slug search against OpenRegister, in the signature the installed OpenRegister declares.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @category Service
 * @package  OCA\Filinq\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://conduction.nl
 *
 * @spec openspec/specs/openregister-bridge/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\OpenRegister\Contract\RegisterSchemaSlugs;
use ReflectionMethod;

/**
 * Runs `searchObjectsBySlug()` on an OpenRegister object service.
 *
 * OpenRegister main (21 Nov 2026) takes the register and schema slugs as one
 * published value, `searchObjectsBySlug(target: RegisterSchemaSlugs, ...)`;
 * earlier releases took `registerSlug:` and `schemaSlug:` as two strings. A
 * named call in the old form fails on main with "Unknown named parameter
 * $registerSlug", and the services resolve the object service untyped, so no
 * static check sees it. This class reads which form the service declares
 * (once per class) and calls it in that form.
 *
 * @spec openspec/specs/openregister-bridge/spec.md
 */
final class SlugSearch {

	/**
	 * Per object-service class: whether its first parameter is the `target` value.
	 *
	 * @var array<string, bool>
	 */
	private static array $takesTarget = [];

	/**
	 * Constructor.
	 *
	 * @param object $objects The OpenRegister object service (or a test double of it).
	 *
	 * @spec openspec/specs/openregister-bridge/spec.md
	 */
	public function __construct(
		private readonly object $objects,
	) {
	}//end __construct()

	/**
	 * Search the objects of one schema in one register, both named by slug.
	 *
	 * @param string               $register      The register slug.
	 * @param string               $schema        The schema slug.
	 * @param array<string, mixed> $filters       Search filters, as OpenRegister takes them.
	 * @param bool                 $_rbac         Whether OpenRegister applies role-based access.
	 * @param bool                 $_multitenancy Whether OpenRegister scopes to the active organisation.
	 *
	 * @return array|int What OpenRegister returns: the rows, or a count when the filters ask for one.
	 *
	 * @SuppressWarnings(PHPMD.CamelCaseParameterName) The names mirror OpenRegister's own parameters.
	 * @SuppressWarnings(PHPMD.CamelCaseVariableName)  The names mirror OpenRegister's own parameters.
	 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)    OpenRegister's own flags, passed through.
	 *
	 * @spec openspec/specs/openregister-bridge/spec.md
	 */
	public function search(
		string $register,
		string $schema,
		array $filters=[],
		bool $_rbac=true,
		bool $_multitenancy=true
	): array|int {
		if ($this->takesTarget() === true) {
			return $this->objects->searchObjectsBySlug(
				target: new RegisterSchemaSlugs(register: $register, schema: $schema),
				filters: $filters,
				_rbac: $_rbac,
				_multitenancy: $_multitenancy
			);
		}

		return $this->objects->searchObjectsBySlug(
			registerSlug: $register,
			schemaSlug: $schema,
			filters: $filters,
			_rbac: $_rbac,
			_multitenancy: $_multitenancy
		);
	}//end search()

	/**
	 * Whether the object service declares the `target` form (OpenRegister main).
	 *
	 * @return bool
	 *
	 * @spec openspec/specs/openregister-bridge/spec.md
	 */
	private function takesTarget(): bool {
		$class = $this->objects::class;
		if (isset(self::$takesTarget[$class]) === false) {
			$parameters = (new ReflectionMethod($this->objects, 'searchObjectsBySlug'))->getParameters();
			self::$takesTarget[$class] = $parameters !== [] && $parameters[0]->getName() === 'target';
		}

		return self::$takesTarget[$class];
	}//end takesTarget()
}//end class
