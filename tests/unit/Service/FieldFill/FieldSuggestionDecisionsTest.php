<?php

/**
 * Unit tests for FieldSuggestionDecisions
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\FieldFill
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\FieldFill;

use OCA\Filinq\Exception\FieldFillRefused;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\FieldFill\FieldFillProfileService;
use OCA\Filinq\Service\FieldFill\FieldSuggestionDecisions;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IGroupManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Reading the record a suggestion is for, and a stored object as its fields.
 * The decide path itself is asserted in SchemaFieldFillServiceTest, which
 * goes through this class.
 */
class FieldSuggestionDecisionsTest extends TestCase {

	/**
	 * The decisions over an object service that answers $find.
	 *
	 * @param mixed $find What find() returns, or a Throwable it throws.
	 *
	 * @return FieldSuggestionDecisions
	 */
	private function decisions(mixed $find): FieldSuggestionDecisions {
		$objects = $this->createMock(ObjectService::class);
		if ($find instanceof \Throwable) {
			$objects->method('find')->willThrowException($find);
		} else {
			$objects->method('find')->willReturn($find);
		}

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);

		return new FieldSuggestionDecisions(
			objectResolver: $resolver,
			profiles: $this->createMock(FieldFillProfileService::class),
			groupManager: $this->createMock(IGroupManager::class),
			logger: $this->createMock(LoggerInterface::class)
		);
	}//end decisions()

	/**
	 * The record comes back as its fields with its uuid.
	 *
	 * @return void
	 */
	public function testTheRecordIsReadAsItsFields(): void {
		$record = $this->decisions(find: ['@self' => ['id' => 'rec-1'], 'object' => ['applicant' => 'Jansen']])
			->record(register: 'subsidie', schema: 'aanvraag', objectId: 'rec-1');

		$this->assertSame(['applicant' => 'Jansen', 'uuid' => 'rec-1'], $record);
	}//end testTheRecordIsReadAsItsFields()

	/**
	 * A missing name, an unknown record and a refused read are refused.
	 *
	 * @return void
	 */
	public function testAMissingOrUnreadableRecordIsRefused(): void {
		foreach ([[null, 404], [new RuntimeException('no rights'), 404]] as [$find, $code]) {
			try {
				$this->decisions(find: $find)->record(register: 'subsidie', schema: 'aanvraag', objectId: 'rec-1');
				$this->fail('expected a refusal');
			} catch (FieldFillRefused $refused) {
				$this->assertSame($code, $refused->getCode());
				$this->assertSame('The record was not found or you may not read it.', $refused->getMessage());
			}
		}

		try {
			$this->decisions(find: [])->record(register: '', schema: 'aanvraag', objectId: 'rec-1');
			$this->fail('expected a refusal');
		} catch (FieldFillRefused $refused) {
			$this->assertSame(400, $refused->getCode());
		}
	}//end testAMissingOrUnreadableRecordIsRefused()

	/**
	 * An entity is read through its serialisation, and a plain row keeps its id.
	 *
	 * @return void
	 */
	public function testARowIsReadFromAnEntityOrAnArray(): void {
		$entity = new class implements \JsonSerializable {
			/**
			 * @return array<string, mixed>
			 */
			public function jsonSerialize(): array {
				return ['uuid' => 'sug-1', 'status' => 'suggested'];
			}
		};

		$decisions = $this->decisions(find: null);
		$this->assertSame(['uuid' => 'sug-1', 'status' => 'suggested'], $decisions->row(object: $entity));
		$this->assertSame(['status' => 'decided', 'id' => 'x-1', 'uuid' => 'x-1'], $decisions->row(object: ['status' => 'decided', 'id' => 'x-1']));
	}//end testARowIsReadFromAnEntityOrAnArray()
}//end class
