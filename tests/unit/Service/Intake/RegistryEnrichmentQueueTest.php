<?php

/**
 * The registry lookup is queued for a stored document with a file, and for
 * nothing else.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Intake
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Intake;

use OCA\Filinq\Kernel\JobQueueInterface;
use OCA\Filinq\Service\Intake\RegistryEnrichmentQueue;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OCA\Filinq\Service\Intake\RegistryEnrichmentQueue
 */
class RegistryEnrichmentQueueTest extends TestCase {

	/**
	 * A stored document with a file gets one job naming it.
	 *
	 * @return void
	 */
	public function testAStoredDocumentWithAFileIsQueued(): void {
		$jobs = $this->createMock(JobQueueInterface::class);
		$jobs->expects($this->once())->method('add')->with(JobQueueInterface::INTAKE_REGISTRY, ['uuid' => 'intake-1']);

		(new RegistryEnrichmentQueue(jobs: $jobs))->queue(document: ['uuid' => 'intake-1', 'file' => 42]);

	}//end testAStoredDocumentWithAFileIsQueued()

	/**
	 * Without a uuid or a file there is nothing to look up.
	 *
	 * @return void
	 */
	public function testNothingIsQueuedWithoutUuidOrFile(): void {
		$jobs = $this->createMock(JobQueueInterface::class);
		$jobs->expects($this->never())->method('add');
		$queue = new RegistryEnrichmentQueue(jobs: $jobs);

		$queue->queue(document: ['file' => 42]);
		$queue->queue(document: ['uuid' => 'intake-1']);
		$queue->queue(document: ['uuid' => 'intake-1', 'file' => 0]);

	}//end testNothingIsQueuedWithoutUuidOrFile()
}//end class
