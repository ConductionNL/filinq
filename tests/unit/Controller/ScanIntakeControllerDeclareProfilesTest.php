<?php

/**
 * Unit tests for ScanIntakeController::declareProfiles().
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\ScanIntakeController;
use OCA\Filinq\Controller\Support\ScanSetupServices;
use OCA\Filinq\Service\ScanBatchRepository;
use OCA\Filinq\Service\ScanBatchService;
use OCA\Filinq\Service\ScanProfileService;
use OCA\Filinq\Service\SeparatorSheetService;
use OCP\AppFramework\Http;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * declareProfiles() passes the profiles to the store and answers with what it
 * stored, or with the store's refusal as a 400.
 *
 * @covers \OCA\Filinq\Controller\ScanIntakeController
 * @uses   \OCA\Filinq\Controller\Support\ScanSetupServices
 */
class ScanIntakeControllerDeclareProfilesTest extends TestCase {

	/**
	 * @var ScanProfileService&MockObject
	 */
	private ScanProfileService $profiles;

	/**
	 * Build the controller over a profile-store double.
	 *
	 * @return ScanIntakeController
	 */
	private function controller(): ScanIntakeController {
		$this->profiles = $this->getMockBuilder(ScanProfileService::class)
			->disableOriginalConstructor()
			->onlyMethods(['all', 'declare', 'find', 'forPath'])
			->getMock();

		$sheets = $this->getMockBuilder(SeparatorSheetService::class)
			->disableOriginalConstructor()
			->onlyMethods(['render'])
			->getMock();

		return new ScanIntakeController(
			$this->createMock(IRequest::class),
			new ScanSetupServices(sheets: $sheets, profiles: $this->profiles),
			$this->getMockBuilder(ScanBatchService::class)
				->disableOriginalConstructor()
				->onlyMethods(['receive', 'split'])
				->getMock(),
			$this->getMockBuilder(ScanBatchRepository::class)
				->disableOriginalConstructor()
				->onlyMethods(['findByFile'])
				->getMock(),
			$this->createMock(IRootFolder::class),
			$this->createMock(IUserSession::class)
		);

	}//end controller()

	public function testAnswersWithWhatTheStoreKept(): void {
		$controller = $this->controller();
		$sent = [
			['id' => 'post', 'folder' => '/Scans/Post'],
			['id' => 'facturen', 'folder' => '/Scans/Facturen', 'label' => 'Facturen'],
		];
		$stored = [['id' => 'post', 'folder' => '/Scans/Post', 'normalised' => true]];
		$this->profiles->expects($this->once())->method('declare')->with($sent)->willReturn($stored);

		$response = $controller->declareProfiles($sent);

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['results' => $stored], $response->getData());

	}//end testAnswersWithWhatTheStoreKept()

	public function testNoProfilesDeclaresAnEmptyList(): void {
		$controller = $this->controller();
		$this->profiles->expects($this->once())->method('declare')->with([])->willReturn([]);

		$response = $controller->declareProfiles();

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['results' => []], $response->getData());

	}//end testNoProfilesDeclaresAnEmptyList()

	public function testARefusedProfileIsA400CarryingTheReason(): void {
		$controller = $this->controller();
		$this->profiles->method('declare')
			->willThrowException(new RuntimeException('A scan profile needs an id and a folder.'));

		$response = $controller->declareProfiles([['folder' => '/Scans']]);

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertSame(['error' => 'A scan profile needs an id and a folder.'], $response->getData());

	}//end testARefusedProfileIsA400CarryingTheReason()
}//end class
