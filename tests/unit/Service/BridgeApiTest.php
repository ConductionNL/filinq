<?php

/**
 * Unit tests for BridgeApi's anonymisation-result hand-off (zgw-document-bridge 2.6).
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

use OCA\Filinq\Service\BridgeApi;
use OCA\Filinq\Service\BridgeDossierPickUp;
use OCA\Filinq\Service\BridgeService;
use PHPUnit\Framework\TestCase;

/**
 * The api hands an anonymisation's files to the bridge register and returns what it saved.
 *
 * @covers \OCA\Filinq\Service\BridgeApi
 */
class BridgeApiTest extends TestCase {

	/**
	 * The source and output file reach BridgeService::processed().
	 *
	 * @return void
	 */
	public function testProcessedIsHandedToTheBridge(): void {
		$bridge = $this->createMock(originalClassName: BridgeService::class);
		$bridge->expects($this->once())->method('processed')->with(1101, 2201)->willReturn([['uuid' => 'd-1']]);
		$api = new BridgeApi(bridge: $bridge, pickUp: $this->createMock(originalClassName: BridgeDossierPickUp::class));

		$this->assertSame(expected: [['uuid' => 'd-1']], actual: $api->processed(sourceFileId: 1101, resultFileId: 2201));
	}//end testProcessedIsHandedToTheBridge()
}//end class
