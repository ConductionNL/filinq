<?php
/**
 * Test helper: read an anonymise call's options back as named fields.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\AnonymizeOptions;
use PHPUnit\Framework\Constraint\Callback;

/**
 * Turns the (fileId, entities, options) of an anonymise call into the nine
 * named fields it carries, so a mock can check them one by one.
 *
 * A test class with coverage metadata that uses it also names AnonymizeOptions
 * and AnonymizeRun as used classes.
 */
trait ReadsAnonymizeOptions {

	/**
	 * The nine fields of one anonymise call.
	 *
	 * @param int                         $fileId   The file id passed.
	 * @param array<array<string, mixed>> $entities The entities passed.
	 * @param AnonymizeOptions|null       $options  The options passed, null for the defaults.
	 *
	 * @return array<string, mixed>
	 */
	private static function anonymizeFields(int $fileId, array $entities, ?AnonymizeOptions $options = null): array {
		$run = ($options ?? new AnonymizeOptions())->runFor(fileId: $fileId, entities: $entities);

		return [
			'fileId' => $fileId,
			'entities' => $entities,
			'outputFormat' => $run->options['outputFormat'],
			'unredactedEntities' => $run->options['unredactedEntities'],
			'overrides' => $run->overrides,
			'userId' => $run->options['userId'],
			'scope' => $run->options['scope'],
			'dossierKey' => $run->options['dossierKey'],
			'reversible' => $run->options['reversible'],
		];

	}//end anonymizeFields()

	/**
	 * A constraint on the options argument: each named field has the given value.
	 *
	 * @param array<string, mixed> $expected Field name => value, from the nine of anonymizeFields().
	 *
	 * @return Callback<AnonymizeOptions|null>
	 */
	private function optionsWith(array $expected): Callback {
		return $this->callback(
			static function (?AnonymizeOptions $options) use ($expected): bool {
				$fields = self::anonymizeFields(0, [], $options);
				foreach ($expected as $name => $value) {
					if ($fields[$name] !== $value) {
						return false;
					}
				}

				return true;
			}
		);

	}//end optionsWith()
}//end trait
