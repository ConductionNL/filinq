<?php

/**
 * The link of an anonymise run records whether image content is pending and
 * how many regions were burned, and nothing else; a failed write never turns
 * the run into a clean success.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Review
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/image-redaction/specs/image-redaction/spec.md#requirement-burn-runs-are-honestly-reported-req-ddimr-006
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Review;

use OCA\Filinq\Service\AnonymizationPersistenceService;
use OCA\Filinq\Service\ConsentCrudService;
use OCA\Filinq\Service\ConsentService;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Service\AnonymizationPersistenceService
 * @uses   \OCA\Filinq\Service\ObjectResultExtractor
 */
class AnonymizationLinkImageRedactionTest extends TestCase {

	/**
	 * The stored links.
	 *
	 * @var list<array<string, mixed>>
	 */
	public array $links = [];

	/**
	 * The saved objects.
	 *
	 * @var list<array<string, mixed>>
	 */
	public array $saved = [];

	/**
	 * Whether a save throws.
	 *
	 * @var bool
	 */
	public bool $failSave = false;

	/**
	 * The persistence over an in-memory ObjectService.
	 *
	 * @return AnonymizationPersistenceService The service.
	 */
	private function persistence(): AnonymizationPersistenceService {
		$objects = new class ($this) {

			public function __construct(private readonly AnonymizationLinkImageRedactionTest $test) {
			}

			public function searchObjectsBySlug(string $registerSlug, string $schemaSlug, array $filters = []): array {
				return $this->test->links;
			}

			public function saveObject(array $object, ObjectSaveOptions $options, bool $_rbac = true): array {
				if ($this->test->failSave === true) {
					throw new RuntimeException('down');
				}

				$this->test->saved[] = $object;

				return $object;
			}
		};
		$locator = $this->createMock(OpenRegisterServiceLocator::class);
		$locator->method('get')->willReturn($objects);

		return new AnonymizationPersistenceService(
			logger: new NullLogger(),
			locator: $locator,
			consentCrud: $this->createMock(ConsentCrudService::class),
			consentService: $this->createMock(ConsentService::class)
		);

	}//end persistence()

	public function testTheRunsImageStateIsRecordedOnItsLink(): void {
		$this->links = [['sourceFileId' => 812004, 'anonymizedFileId' => 812441, 'runCount' => 1]];

		$written = $this->persistence()->recordImageRedaction(
			fileId: 812004,
			fields: ['imageRedactionPending' => true, 'burnedRegionCount' => 0, 'withheld' => true]
		);

		$this->assertTrue($written);
		$this->assertSame(
			['sourceFileId' => 812004, 'anonymizedFileId' => 812441, 'runCount' => 1, 'imageRedactionPending' => true, 'burnedRegionCount' => 0],
			$this->saved[0],
			'Only the two image fields are written.'
		);

	}//end testTheRunsImageStateIsRecordedOnItsLink()

	public function testWithoutALinkNothingIsWritten(): void {
		$this->assertFalse($this->persistence()->recordImageRedaction(fileId: 812004, fields: ['imageRedactionPending' => true]));
		$this->assertSame([], $this->saved);

	}//end testWithoutALinkNothingIsWritten()

	public function testAFailedWriteIsReportedAsNotWritten(): void {
		$this->links = [['sourceFileId' => 812004]];
		$this->failSave = true;

		$this->assertFalse($this->persistence()->recordImageRedaction(fileId: 812004, fields: ['imageRedactionPending' => true]));

	}//end testAFailedWriteIsReportedAsNotWritten()
}//end class
