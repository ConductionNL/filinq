<?php

/**
 * Golden results of validating a batch-anonymise request body.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\BatchRequestService;
use OCP\IL10N;
use PHPUnit\Framework\TestCase;

/**
 * Each batch body's first refusal or normalised request, compared with JSON
 * recorded from the implementation before the shared request helpers.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class BatchRequestBodyGoldenTest extends TestCase {

	/**
	 * The request bodies.
	 *
	 * @return array<string, array{0: array<string, mixed>}> Name => [params].
	 */
	public static function bodies(): array {
		$entities = [['text' => 'Jan', 'type' => 'PERSON'], ['text' => 'Delft', 'bases' => ['5.1.2e']]];
		$plain = [['text' => 'Jan']];
		$consent = [['entityId' => 3, 'entityText' => 'Jan', 'entityType' => 'PERSON', 'publicationBases' => ['5.1.2e']]];
		return [
			'no entities' => [[]],
			'entities not a list' => [['entities' => 'Jan']],
			'empty entities' => [['entities' => []]],
			'only entities' => [['entities' => $plain]],
			'stray bases' => [['entities' => $entities]],
			'a string entry carries no bases' => [['entities' => ['Jan', ['text' => 'Delft']]]],
			'a string flag' => [['entities' => $plain, 'appendBasisSummary' => 'true']],
			'a null flag' => [['entities' => $plain, 'appendBasisSummary' => null]],
			'the flag set' => [['entities' => $plain, 'appendBasisSummary' => true]],
			'reversible is not read' => [['entities' => $plain, 'reversible' => 'yes']],
			'unredacted not a list' => [['entities' => $plain, 'unredactedEntities' => 'Jan']],
			'unredacted entry not an object is left for later' => [['entities' => $plain, 'unredactedEntities' => ['Jan']]],
			'unredacted entries' => [['entities' => $plain, 'unredactedEntities' => $consent]],
			'a wrong flag and wrong unredacted' => [['entities' => $plain, 'appendBasisSummary' => 1, 'unredactedEntities' => 'x']],
			'everything' => [['entities' => $entities, 'appendBasisSummary' => true, 'unredactedEntities' => $consent]],
		];
	}//end bodies()

	/**
	 * The result matches the recorded one.
	 *
	 * @param array<string, mixed> $params The request body.
	 *
	 * @return void
	 *
	 * @dataProvider bodies
	 */
	public function testResultMatchesTheRecordedOne(array $params): void {
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(
			static fn (string $text, array $parameters = []): string => '[t] ' . vsprintf($text, $parameters)
		);

		$result = (new BatchRequestService(l10n: $l10n))->validateBody(params: $params);

		$path = __DIR__ . '/golden/batch-request-body.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $result;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, $result);
	}//end testResultMatchesTheRecordedOne()
}//end class
