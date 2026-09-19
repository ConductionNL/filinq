<?php

/**
 * The scan intake surface, asserted from the caller.
 *
 * 🔴 THESE ARE CALLER-SIDE ASSERTIONS ON PURPOSE. The profile store and the
 * separator renderer have their own suites, and nothing in those can see
 * whether a route ever reaches them.
 *
 * 🔴 A REFUSED RENDER ANSWERS 400, NOT A PDF OF ZERO BYTES. A caller that got
 * a download response back would file an empty separator sheet and the batch
 * would be cut in the wrong place.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\ScanIntakeController;
use OCA\Filinq\Service\ScanBatchRepository;
use OCA\Filinq\Service\ScanBatchService;
use OCA\Filinq\Service\ScanProfileService;
use OCA\Filinq\Service\SeparatorSheetService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * `ScanIntakeController`.
 *
 * @covers \OCA\Filinq\Controller\ScanIntakeController
 */
class ScanIntakeControllerTest extends TestCase {

	/**
	 * A separator-sheet double.
	 *
	 * `onlyMethods` so it cannot answer a question the real service lacks.
	 *
	 * @return SeparatorSheetService The double.
	 */
	private function sheets(): SeparatorSheetService {
		return $this->getMockBuilder(SeparatorSheetService::class)
			->disableOriginalConstructor()
			->onlyMethods(['render'])
			->getMock();
	}//end sheets()

	/**
	 * A profile-store double.
	 *
	 * @return ScanProfileService The double.
	 */
	private function profiles(): ScanProfileService {
		return $this->getMockBuilder(ScanProfileService::class)
			->disableOriginalConstructor()
			->onlyMethods(['all', 'declare', 'find', 'forPath'])
			->getMock();
	}//end profiles()

	/**
	 * A controller over the given collaborators.
	 *
	 * @param SeparatorSheetService $sheets   The sheet renderer.
	 * @param ScanProfileService    $profiles The profile store.
	 * @param bool                  $loggedIn Whether anybody is logged in.
	 *
	 * @return ScanIntakeController The controller.
	 */
	private function controller(
		SeparatorSheetService $sheets,
		ScanProfileService $profiles,
		bool $loggedIn = true,
	): ScanIntakeController {
		$session = $this->createMock(IUserSession::class);
		if ($loggedIn === true) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn('beheerder');
			$session->method('getUser')->willReturn($user);
		} else {
			$session->method('getUser')->willReturn(null);
		}

		return new ScanIntakeController(
			'filinq',
			$this->createMock(IRequest::class),
			$sheets,
			$profiles,
			$this->getMockBuilder(ScanBatchService::class)
				->disableOriginalConstructor()
				->onlyMethods(['receive', 'split'])
				->getMock(),
			$this->getMockBuilder(ScanBatchRepository::class)
				->disableOriginalConstructor()
				->onlyMethods(['findByFile'])
				->getMock(),
			$this->createMock(IRootFolder::class),
			$session
		);
	}//end controller()

	/**
	 * The declared profiles are served, with the count beside them.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function testTheDeclaredProfilesAreServed(): void {
		$profiles = $this->profiles();
		$profiles->expects($this->once())
			->method('all')
			->willReturn([['id' => 'post', 'folder' => '/Scans/Post']]);

		$response = $this->controller($this->sheets(), $profiles)->listProfiles();

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(1, $response->getData()['total']);
	}//end testTheDeclaredProfilesAreServed()

	/**
	 * With nobody logged in the profiles are not served at all.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function testTheProfilesAreNotServedToNobody(): void {
		$profiles = $this->profiles();
		$profiles->expects($this->never())->method('all');

		$response = $this->controller($this->sheets(), $profiles, false)->listProfiles();

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());
	}//end testTheProfilesAreNotServedToNobody()

	/**
	 * Rendered sheets come back as a download, named as a PDF.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function testRenderedSheetsComeBackAsADownload(): void {
		$sheets = $this->sheets();
		$sheets->expects($this->once())
			->method('render')
			->with('post', ['ZAAK-1'])
			->willReturn('%PDF-1.4 fake');

		$response = $this->controller($sheets, $this->profiles())
			->separators(profileId: 'post', caseNumbers: ['ZAAK-1']);

		$this->assertInstanceOf(DataDownloadResponse::class, $response);
		$this->assertSame('%PDF-1.4 fake', $response->getData());
		$this->assertSame('application/pdf', $response->getHeaders()['Content-Type']);
	}//end testRenderedSheetsComeBackAsADownload()

	/**
	 * A render that refuses answers 400, never an empty download.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function testARefusedRenderIsNotAnEmptyDownload(): void {
		$sheets = $this->sheets();
		$sheets->method('render')->willThrowException(new RuntimeException('No profile by that name.'));

		$response = $this->controller($sheets, $this->profiles())->separators(profileId: 'nope');

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertSame('No profile by that name.', $response->getData()['error']);
	}//end testARefusedRenderIsNotAnEmptyDownload()

	/**
	 * With nobody logged in no sheet is printed.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function testNoSheetIsPrintedForNobody(): void {
		$sheets = $this->sheets();
		$sheets->expects($this->never())->method('render');

		$response = $this->controller($sheets, $this->profiles(), false)->separators(profileId: 'post');

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());
	}//end testNoSheetIsPrintedForNobody()
}//end class
