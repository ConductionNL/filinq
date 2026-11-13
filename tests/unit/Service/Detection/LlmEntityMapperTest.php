<?php

/**
 * Tests for LlmEntityMapper: LLM output into OpenRegister's entity shape.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Detection
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/llm-entity-detection-provider/specs/llm-entity-detection-provider/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Detection;

use OCA\Filinq\Service\Detection\LlmEntityMapper;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

/**
 * @covers \OCA\Filinq\Service\Detection\LlmEntityMapper
 */
class LlmEntityMapperTest extends TestCase {

	/**
	 * A known type is mapped, value and positions are kept, the method is llm.
	 *
	 * @return void
	 */
	public function testAKnownEntityIsMappedIntoOpenRegistersShape(): void {
		$mapped = (new LlmEntityMapper())->map(
			items: [['value' => 'Jan Jansen', 'type' => 'per', 'start' => 4, 'end' => 14, 'score' => 0.91]],
			defaultConfidence: 0.8
		);

		$this->assertSame(
			[[
				'value'           => 'Jan Jansen',
				'type'            => 'PERSON',
				'category'        => 'personal_data',
				'positionStart'   => 4,
				'positionEnd'     => 14,
				'confidence'      => 0.91,
				'detectionMethod' => 'llm',
			]],
			$mapped
		);
	}

	/**
	 * A score above 1 is clamped, below 0 is clamped, and a missing score takes the default.
	 *
	 * @return void
	 */
	public function testConfidenceClampAndDefault(): void {
		$mapped = (new LlmEntityMapper())->map(
			items: [
				['value' => 'a', 'type' => 'EMAIL', 'score' => 1.4],
				['value' => 'b', 'type' => 'EMAIL'],
				['value' => 'c', 'type' => 'EMAIL', 'score' => -2],
				['value' => 'd', 'type' => 'EMAIL', 'score' => 'high'],
			],
			defaultConfidence: 0.65
		);

		$this->assertSame([1.0, 0.65, 0.0, 0.65], array_column($mapped, 'confidence'));
	}

	/**
	 * An unrecognised type is kept verbatim (upper case) with the contextual category.
	 *
	 * @return void
	 */
	public function testAnUnknownTypeIsKeptNotDropped(): void {
		$mapped = (new LlmEntityMapper())->map(items: [['value' => 'Project X', 'type' => 'codename']], defaultConfidence: 0.8);

		$this->assertCount(1, $mapped);
		$this->assertSame('CODENAME', $mapped[0]['type']);
		$this->assertSame('contextual_data', $mapped[0]['category']);
	}

	/**
	 * Missing or invalid positions give a position-less entity, still mapped.
	 *
	 * @return void
	 */
	public function testInvalidPositionsGiveAPositionLessEntity(): void {
		$mapped = (new LlmEntityMapper())->map(
			items: [
				['value' => 'a', 'type' => 'PHONE'],
				['value' => 'b', 'type' => 'PHONE', 'start' => 9, 'end' => 3],
				['value' => 'c', 'type' => 'PHONE', 'start' => '1', 'end' => 4],
				['value' => 'd', 'type' => 'PHONE', 'start' => -1, 'end' => 4],
			],
			defaultConfidence: 0.8
		);

		$this->assertSame([null, null, null, null], array_column($mapped, 'positionStart'));
		$this->assertSame([null, null, null, null], array_column($mapped, 'positionEnd'));
	}

	/**
	 * Aliases map onto the type names Filinq's other detections use.
	 *
	 * @return void
	 */
	public function testAliasesMapToFilinqTypeNames(): void {
		$mapper = new LlmEntityMapper();

		$this->assertSame('ORGANIZATION', $mapper->mapType(type: ' org '));
		$this->assertSame('LOCATION', $mapper->mapType(type: 'GPE'));
		$this->assertSame('EMAIL', $mapper->mapType(type: 'email_address'));
		$this->assertSame('STREET_ADDRESS', $mapper->mapType(type: 'address'));
		$this->assertSame('IBAN', $mapper->mapType(type: 'IBAN_CODE'));
	}

	/**
	 * Output that is not a list is refused as a whole.
	 *
	 * @return void
	 */
	public function testANonListIsRefused(): void {
		$this->expectException(UnexpectedValueException::class);
		$this->expectExceptionMessage('The agentflow did not return an entity list.');

		(new LlmEntityMapper())->map(items: ['value' => 'x', 'type' => 'PERSON'], defaultConfidence: 0.8);
	}

	/**
	 * One malformed item refuses the whole list, naming the item.
	 *
	 * @return void
	 */
	public function testAMalformedItemRefusesTheList(): void {
		$mapper = new LlmEntityMapper();

		$cases = [
			'Entity 1 is not an object.' => [['value' => 'ok', 'type' => 'PERSON'], 'no object'],
			'Entity 0 has no value.'     => [['value' => '  ', 'type' => 'PERSON']],
			'Entity 0 has no type.'      => [['value' => 'x']],
		];
		foreach ($cases as $message => $items) {
			try {
				$mapper->map(items: $items, defaultConfidence: 0.8);
				$this->fail('Expected a refusal: '.$message);
			} catch (UnexpectedValueException $e) {
				$this->assertSame($message, $e->getMessage());
			}
		}
	}
}
