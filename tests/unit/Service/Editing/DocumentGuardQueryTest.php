<?php

/**
 * Unit tests for DocumentGuard's register query edges and the final-version check.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Editing;

use JsonSerializable;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\Editing\DocumentGuard;
use OCA\Filinq\Service\FinalDocumentService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Files\File;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Absent schemas, odd result shapes and the delegated final-version refusal.
 *
 * @covers \OCA\Filinq\Service\Editing\DocumentGuard
 */
class DocumentGuardQueryTest extends TestCase {

	/**
	 * The object service double.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objects;

	/**
	 * The logger double.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * The final-document double.
	 *
	 * @var FinalDocumentService&MockObject
	 */
	private FinalDocumentService&MockObject $finalDocuments;

	/**
	 * Set up the doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->objects = $this->createMock(ObjectService::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->finalDocuments = $this->createMock(FinalDocumentService::class);
	}//end setUp()

	/**
	 * Build the guard.
	 *
	 * @return DocumentGuard
	 */
	private function guard(): DocumentGuard {
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->objects);
		return new DocumentGuard(objectResolver: $resolver, logger: $this->logger, finalDocuments: $this->finalDocuments);
	}//end guard()

	/**
	 * A file with id 4711.
	 *
	 * @return File
	 */
	private function file(): File {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(4711);
		return $file;
	}//end file()

	/**
	 * The final-version refusal is the final-document service's answer for this file.
	 *
	 * @return void
	 */
	public function testFinalRefusalDelegates(): void {
		$this->finalDocuments->expects($this->exactly(2))->method('refusalFor')
			->with(4711, 'edit this document')
			->willReturnOnConsecutiveCalls('Version is final', null);

		$guard = $this->guard();
		$this->assertSame('Version is final', $guard->finalRefusal(file: $this->file()));
		$this->assertNull($guard->finalRefusal(file: $this->file()));
	}//end testFinalRefusalDelegates()

	/**
	 * An absent signing schema means no signing request: the edit is allowed and a warning logged.
	 *
	 * @return void
	 */
	public function testAbsentSchemaIsNoRowsNotUnreachable(): void {
		$this->objects->method('searchObjectsBySlug')->willThrowException(new DoesNotExistException('gone'));
		$this->logger->expects($this->once())->method('warning')->with(
			'Filinq document guard found no signingRequest schema on this instance, so nothing is guarded by it',
			['schema' => 'signingRequest', 'error' => 'gone']
		);

		$this->assertNull($this->guard()->signatureRefusal(file: $this->file()));
	}//end testAbsentSchemaIsNoRowsNotUnreachable()

	/**
	 * The guard queries the filinq register by slug, without RBAC, filtered on the file id.
	 *
	 * @return void
	 */
	public function testQueriesBySlugWithTheFileFilter(): void {
		$this->objects->expects($this->once())->method('searchObjectsBySlug')
			->with('filinq', 'anonymizationLink', ['anonymizedFileId' => 4711], false)
			->willReturn('not a list');

		$this->assertNull($this->guard()->anonymisationRefusal(file: $this->file()));
	}//end testQueriesBySlugWithTheFileFilter()

	/**
	 * A serialisable row with an empty status refuses as "unknown"; nested object fields are read.
	 *
	 * @return void
	 */
	public function testSerialisableRowsAndUnknownStatus(): void {
		$row = new class implements JsonSerializable {
			/**
			 * Serialise.
			 *
			 * @return array<string, mixed>
			 */
			public function jsonSerialize(): array {
				return ['object' => ['status' => '']];
			}//end jsonSerialize()
		};
		$this->objects->method('searchObjectsBySlug')->willReturn([$row]);

		$refusal = $this->guard()->signatureRefusal(file: $this->file());

		$this->assertSame(
			'This document is part of a signing request (status "unknown") and cannot be edited. '
			. 'Editing a document under signature invalidates the signature.',
			$refusal
		);
	}//end testSerialisableRowsAndUnknownStatus()

	/**
	 * A nested status is read case-insensitively, so a CANCELLED request does not block.
	 *
	 * @return void
	 */
	public function testNestedCancelledStatusPasses(): void {
		$this->objects->method('searchObjectsBySlug')->willReturn([['object' => ['status' => 'CANCELLED']]]);

		$this->assertNull($this->guard()->signatureRefusal(file: $this->file()));
	}//end testNestedCancelledStatusPasses()

	/**
	 * Plain bytes that are not a zip carry no macros and pass the format check.
	 *
	 * @return void
	 */
	public function testNonZipBytesPassTheFormatCheck(): void {
		$file = $this->createMock(File::class);
		$file->method('getMimeType')->willReturn('text/plain');
		$file->method('getContent')->willReturn('just text');

		$this->assertNull($this->guard()->formatRefusal(file: $file));
	}//end testNonZipBytesPassTheFormatCheck()
}//end class
