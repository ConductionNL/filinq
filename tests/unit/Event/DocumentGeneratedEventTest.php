<?php

/**
 * Unit tests for DocumentGeneratedEvent
 *
 * Verifies how the event normalises the generated-document record it carries:
 * the object reference, file id, file path, template and metadata accessors,
 * including the null/default branches a field-only generation produces.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Event
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Event;

use OCA\Filinq\Event\DocumentGeneratedEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for DocumentGeneratedEvent.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
#[CoversClass(DocumentGeneratedEvent::class)]
class DocumentGeneratedEventTest extends TestCase {

	/**
	 * A stored document exposes its file, object, template and metadata.
	 *
	 * @return void
	 */
	public function testStoredDocumentIsNormalised(): void {
		$document = [
			'fileId' => '42',
			'path' => '/admin/files/Filinq/offer.pdf',
			'template' => ['id' => 7, 'slug' => 'offer', 'source' => 'slug'],
			'object' => ['register' => 'crm', 'schema' => 'deal', 'id' => 15],
			'metadata' => ['documentType' => 'offerte'],
		];
		$event = new DocumentGeneratedEvent($document, 'pipelinq');

		$this->assertSame($document, $event->getDocument());
		$this->assertSame('pipelinq', $event->getRequestingApp());
		$this->assertSame(42, $event->getFileId());
		$this->assertSame('/admin/files/Filinq/offer.pdf', $event->getFilePath());
		$this->assertSame(['register' => 'crm', 'schema' => 'deal', 'id' => '15'], $event->getObject());
		$this->assertSame(['id' => 7, 'slug' => 'offer', 'source' => 'slug'], $event->getTemplate());
		$this->assertSame(['documentType' => 'offerte'], $event->getMetadata());
	}//end testStoredDocumentIsNormalised()

	/**
	 * A field-only generation has no file, no object and empty template/metadata.
	 *
	 * @return void
	 */
	public function testFieldOnlyGenerationYieldsNullsAndEmptyArrays(): void {
		$event = new DocumentGeneratedEvent(['targetField' => 'summary'], 'dossiq');

		$this->assertNull($event->getFileId());
		$this->assertNull($event->getFilePath());
		$this->assertNull($event->getObject());
		$this->assertSame([], $event->getTemplate());
		$this->assertSame([], $event->getMetadata());
	}//end testFieldOnlyGenerationYieldsNullsAndEmptyArrays()

	/**
	 * A non-array object reference is treated as no object at all.
	 *
	 * @return void
	 */
	public function testNonArrayObjectIsNull(): void {
		$event = new DocumentGeneratedEvent(['object' => 'crm/deal/15'], 'pipelinq');

		$this->assertNull($event->getObject());
	}//end testNonArrayObjectIsNull()

	/**
	 * Missing object keys default to empty strings rather than disappearing.
	 *
	 * @return void
	 */
	public function testPartialObjectDefaultsMissingKeysToEmptyStrings(): void {
		$event = new DocumentGeneratedEvent(['object' => ['id' => 'abc']], 'pipelinq');

		$this->assertSame(['register' => '', 'schema' => '', 'id' => 'abc'], $event->getObject());
	}//end testPartialObjectDefaultsMissingKeysToEmptyStrings()

	/**
	 * Scalar paths are cast to string; an explicit zero file id stays zero.
	 *
	 * @return void
	 */
	public function testScalarValuesAreCast(): void {
		$event = new DocumentGeneratedEvent(['fileId' => 0, 'path' => 123], 'pipelinq');

		$this->assertSame(0, $event->getFileId());
		$this->assertSame('123', $event->getFilePath());
	}//end testScalarValuesAreCast()
}//end class
