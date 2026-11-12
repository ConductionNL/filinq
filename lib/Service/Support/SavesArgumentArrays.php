<?php

/**
 * Saves argument arrays
 *
 * Saves a repository's `['object', 'register', 'schema', 'uuid']` save array
 * through OpenRegister's saveObject(), which takes them as ObjectSaveOptions.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec exclude call-shape plumbing — carries no product behaviour of its own
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Support;

use OCA\OpenRegister\Service\Object\ObjectFindTarget;
use OCA\OpenRegister\Service\ObjectService;
use OCA\OpenRegister\Service\Object\ObjectSaveOptions;

/**
 * Saves a repository's save argument array.
 *
 * @spec exclude call-shape plumbing — carries no product behaviour of its own
 */
trait SavesArgumentArrays {
	/**
	 * Save one object: the register and schema, and the uuid when the array names one.
	 *
	 * @param ObjectService        $objects   OpenRegister's object service.
	 * @param array<string, mixed> $arguments The save array: object, register, schema and optional uuid.
	 *
	 * @return mixed What saveObject() returns.
	 */
	private function saveArguments(ObjectService $objects, array $arguments): mixed {
		return $objects->saveObject(
			object: $arguments['object'],
			options: new ObjectSaveOptions(
				target: new ObjectFindTarget(register: $arguments['register'], schema: $arguments['schema']),
				uuid: ($arguments['uuid'] ?? null)
			)
		);
	}//end saveArguments()
}//end trait
