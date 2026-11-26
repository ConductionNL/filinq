<?php

/**
 * Unit tests for BridgeResultRecorder (zgw-document-bridge REQ-DDZGW-003).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/zgw-document-bridge/tasks.md#task-2.6
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\BridgeResultRecorder;
use OCA\Filinq\Service\BridgeService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The recorder hands the run's files to the bridge and never lets a failure
 * reach the anonymisation run.
 *
 * @covers \OCA\Filinq\Service\BridgeResultRecorder
 */
class BridgeResultRecorderTest extends TestCase {

	/**
	 * It says how many external documents became processed.
	 *
	 * @return void
	 */
	public function testItCountsTheProcessedDocuments(): void {
		$bridge = $this->createMock(BridgeService::class);
		$bridge->expects($this->once())->method('processed')->with(1101, 2201)->willReturn([['uuid' => 'd-1']]);

		$recorder = new BridgeResultRecorder(bridge: $bridge, logger: $this->createMock(LoggerInterface::class));

		$this->assertSame(1, $recorder->record(sourceFileId: 1101, resultFileId: 2201));
	}//end testItCountsTheProcessedDocuments()

	/**
	 * A failing save is logged with both file ids and records nothing.
	 *
	 * @return void
	 */
	public function testAFailureIsLoggedAndSwallowed(): void {
		$bridge = $this->createMock(BridgeService::class);
		$bridge->method('processed')->willThrowException(new RuntimeException('lifecycle refused'));
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('warning')->with(
			$this->stringContains('not recorded'),
			$this->callback(static fn (array $context): bool => $context['sourceFileId'] === 1101 && $context['resultFileId'] === 2201 && $context['exception'] === 'lifecycle refused')
		);

		$recorder = new BridgeResultRecorder(bridge: $bridge, logger: $logger);

		$this->assertSame(0, $recorder->record(sourceFileId: 1101, resultFileId: 2201));
	}//end testAFailureIsLoggedAndSwallowed()
}//end class
