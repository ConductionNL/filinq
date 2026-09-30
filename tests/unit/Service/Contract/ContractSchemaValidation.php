<?php

/**
 * Validates a contract payload against the real register fragment
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Contract
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#4-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Contract;

use Opis\JsonSchema\Validator;

/**
 * Every payload the contract code writes must pass the schema it is written to.
 */
trait ContractSchemaValidation {

	/**
	 * Assert the payload validates against `contract` in filinq_register.json.
	 *
	 * @param array<string, mixed> $payload The object as written.
	 *
	 * @return void
	 */
	private function assertValidContract(array $payload): void {
		$register = json_decode((string) file_get_contents(__DIR__ . '/../../../../lib/Settings/filinq_register.json'));
		$schema = $register->components->schemas->contract;
		$properties = new \stdClass();
		foreach ($schema->properties as $name => $property) {
			$copy = clone $property;
			// A boolean `required` on a property is OpenRegister's, not JSON Schema's.
			unset($copy->required);
			$properties->{$name} = $copy;
		}

		$jsonSchema = (object) [
			'type' => 'object',
			'required' => $schema->required,
			'properties' => $properties,
			'additionalProperties' => false,
		];
		unset($payload['uuid']);
		$result = (new Validator())->validate(json_decode((string) json_encode($payload)), json_encode($jsonSchema));
		$message = '';
		if ($result->isValid() === false) {
			$message = json_encode((new \Opis\JsonSchema\Errors\ErrorFormatter())->format($result->error()));
		}

		$this->assertTrue($result->isValid(), 'contract payload refused: ' . $message);

	}//end assertValidContract()
}//end trait
