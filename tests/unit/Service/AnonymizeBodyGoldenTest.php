<?php

/**
 * Golden results of validating an anonymise request body.
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
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\AnonymizeRequestValidator;
use OCP\IL10N;
use PHPUnit\Framework\TestCase;

/**
 * Each body's first refusal or normalised request, compared with JSON
 * recorded from the implementation before validateBody() was split.
 *
 * @covers \OCA\Filinq\Service\AnonymizeRequestValidator
 * @uses   \OCA\Filinq\Service\UnredactedEntitiesValidator
 */
class AnonymizeBodyGoldenTest extends TestCase {

	/**
	 * The bodies.
	 *
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public static function bodies(): array {
		$entities = [['text' => 'Jan', 'type' => 'PERSON'], ['text' => 'Delft', 'bases' => ['5.1.2e']]];
		$plain = [['text' => 'Jan']];
		$override = ['ruleId' => 'R-1', 'entityId' => 7, 'reason' => 'ok'];
		$consent = [['entityId' => 3, 'entityText' => 'Jan', 'entityType' => 'PERSON', 'publicationBases' => ['5.1.2e']]];
		return [
			'no entities' => [[]],
			'entities not a list' => [['entities' => 'Jan']],
			'empty entities' => [['entities' => []]],
			'only entities' => [['entities' => $plain]],
			'stray bases' => [['entities' => $entities]],
			'a string flag' => [['entities' => $plain, 'appendBasisSummary' => 'true']],
			'both flags' => [['entities' => $plain, 'appendBasisSummary' => true, 'reversible' => false]],
			'a string reversible' => [['entities' => $plain, 'reversible' => 'false']],
			'both flags wrong' => [['entities' => $plain, 'appendBasisSummary' => 1, 'reversible' => 1]],
			'unredacted not a list' => [['entities' => $plain, 'unredactedEntities' => 'Jan']],
			'unredacted entry not an object' => [['entities' => $plain, 'unredactedEntities' => ['Jan']]],
			'unredacted entries' => [['entities' => $plain, 'unredactedEntities' => $consent]],
			'overrides not a list' => [['entities' => $plain, 'acknowledgedOverrides' => 'R-1']],
			'an override not an object' => [['entities' => $plain, 'acknowledgedOverrides' => ['R-1']]],
			'an override without a rule' => [['entities' => $plain, 'acknowledgedOverrides' => [['entityId' => 7]]]],
			'an override with a string entity' => [['entities' => $plain, 'acknowledgedOverrides' => [['ruleId' => 'R-1', 'entityId' => '7']]]],
			'an override with a numeric reason' => [['entities' => $plain, 'acknowledgedOverrides' => [['ruleId' => 'R-1', 'entityId' => 7, 'reason' => 5]]]],
			'an override with an empty rule' => [['entities' => $plain, 'acknowledgedOverrides' => [['ruleId' => '', 'entityId' => 7]]]],
			'an override with a zero rule' => [['entities' => $plain, 'acknowledgedOverrides' => [['ruleId' => '0', 'entityId' => 7]]]],
			'an override with a numeric rule' => [['entities' => $plain, 'acknowledgedOverrides' => [['ruleId' => 5, 'entityId' => 7]]]],
			'an override without an entity' => [['entities' => $plain, 'acknowledgedOverrides' => [['ruleId' => 'R-1']]]],
			'an override with a null entity' => [['entities' => $plain, 'acknowledgedOverrides' => [['ruleId' => 'R-1', 'entityId' => null]]]],
			'an override with a null reason' => [['entities' => $plain, 'acknowledgedOverrides' => [['ruleId' => 'R-1', 'entityId' => 7, 'reason' => null]]]],
			'the second override wrong' => [['entities' => $plain, 'acknowledgedOverrides' => [$override, ['ruleId' => 'R-2', 'entityId' => 8, 'reason' => []]]]],
			'the second override without a rule' => [['entities' => $plain, 'acknowledgedOverrides' => [$override, ['entityId' => 8]]]],
			'the second override with a string entity' => [['entities' => $plain, 'acknowledgedOverrides' => [$override, ['ruleId' => 'R-2', 'entityId' => '8']]]],
			'a named override wrong' => [['entities' => $plain, 'acknowledgedOverrides' => ['first' => 'R-1']]],
			'everything' => [['entities' => $entities, 'appendBasisSummary' => true, 'reversible' => true, 'unredactedEntities' => $consent, 'acknowledgedOverrides' => [$override]]],
			'unredacted and overrides both wrong' => [['entities' => $plain, 'unredactedEntities' => 'x', 'acknowledgedOverrides' => 'y']],
			'a wrong flag and wrong overrides' => [['entities' => $plain, 'reversible' => 'yes', 'acknowledgedOverrides' => 'y']],
		];
	}//end bodies()

	/**
	 * The result matches the recorded one.
	 *
	 * @param array<string, mixed> $params The request body.
	 *
	 * @dataProvider bodies
	 *
	 * @return void
	 */
	public function testResultMatchesTheRecordedOne(array $params): void {
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(
			static fn (string $text, array $parameters = []): string => '[t] ' . vsprintf($text, $parameters)
		);

		$result = (new AnonymizeRequestValidator(l10n: $l10n))->validateBody(params: $params);

		$path = __DIR__ . '/golden/anonymize-body.json';
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
