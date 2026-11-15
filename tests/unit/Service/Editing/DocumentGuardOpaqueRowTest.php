<?php

/**
 * Unit tests for DocumentGuard on signing-request rows it cannot read.
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

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\Editing\DocumentGuard;
use OCA\Filinq\Service\FinalDocumentService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\Files\File;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use stdClass;

/**
 * A row that is neither an array nor serialisable still counts as a signing request.
 *
 * @covers \OCA\Filinq\Service\Editing\DocumentGuard
 */
class DocumentGuardOpaqueRowTest extends TestCase {

	/**
	 * An opaque row has no readable status, so the guard refuses rather than assumes it is cancelled.
	 *
	 * @return void
	 */
	public function testAnOpaqueRowIsAnUnknownSigningRequest(): void {
		$row = new stdClass();
		$row->status = 'cancelled';

		$objects = $this->createMock(ObjectService::class);
		$objects->expects($this->once())->method('searchObjectsBySlug')->willReturn([$row]);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);

		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(4711);

		$guard = new DocumentGuard(
			objectResolver: $resolver,
			logger: $this->createMock(LoggerInterface::class),
			finalDocuments: $this->createMock(FinalDocumentService::class)
		);

		$this->assertSame(
			'This document is part of a signing request (status "unknown") and cannot be edited. '
			. 'Editing a document under signature invalidates the signature.',
			$guard->signatureRefusal(file: $file)
		);
	}//end testAnOpaqueRowIsAnUnknownSigningRequest()
}//end class
