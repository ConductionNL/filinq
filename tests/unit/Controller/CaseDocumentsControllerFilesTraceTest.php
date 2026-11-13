<?php

/**
 * Characterization tests for CaseDocumentsController::files()
 *
 * Pins the domain triple and paging handed to the flat list, and the 401 and 500 answers.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\CaseDocumentsController;
use OCA\Filinq\Kernel\Contracts\DocumentDomainServiceInterface;
use OCA\Filinq\Kernel\Contracts\FlatFileListServiceInterface;
use OCA\Filinq\Kernel\Contracts\UploadPolicyServiceInterface;
use OCP\AppFramework\Http;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Characterization of the flat file list endpoint.
 *
 * @covers \OCA\Filinq\Controller\CaseDocumentsController
 */
class CaseDocumentsControllerFilesTraceTest extends TestCase {

	private FlatFileListServiceInterface&MockObject $flatList;

	/**
	 * Fresh doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->flatList = $this->createMock(FlatFileListServiceInterface::class);

	}//end setUp()

	/**
	 * The controller, with or without a session user.
	 *
	 * @param bool $signedIn Whether somebody is logged in.
	 *
	 * @return CaseDocumentsController The controller.
	 */
	private function controller(bool $signedIn = true): CaseDocumentsController {
		$session = $this->createMock(IUserSession::class);
		if ($signedIn === true) {
			$session->method('getUser')->willReturn($this->createMock(IUser::class));
		} else {
			$session->method('getUser')->willReturn(null);
		}

		return new CaseDocumentsController(
			'filinq',
			$this->createMock(IRequest::class),
			$this->flatList,
			$this->createMock(DocumentDomainServiceInterface::class),
			$this->createMock(UploadPolicyServiceInterface::class),
			$session
		);

	}//end controller()

	/**
	 * The object's register, schema and id become the domain; paging and search pass through.
	 *
	 * @return void
	 */
	public function testFilesHandsTheDomainAndPagingToTheFlatList(): void {
		$page = ['results' => [['fileId' => 7]], 'total' => 1, 'page' => 2];
		$this->flatList->expects($this->once())
			->method('listFor')
			->with(['register' => 'zaken', 'schema' => 'zaak', 'id' => 'z-1'], 2, 25, 'brief')
			->willReturn($page);

		$response = $this->controller()->files(
			register: 'zaken',
			schema: 'zaak',
			id: 'z-1',
			page: 2,
			limit: 25,
			search: 'brief'
		);

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame($page, $response->getData());

	}//end testFilesHandsTheDomainAndPagingToTheFlatList()

	/**
	 * No session: 401 before the list is read.
	 *
	 * @return void
	 */
	public function testFilesWithoutASessionIs401(): void {
		$this->flatList->expects($this->never())->method('listFor');

		$response = $this->controller(signedIn: false)->files(register: 'zaken', schema: 'zaak', id: 'z-1');

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());
		$this->assertSame(['error' => 'You must be logged in to read case documents.'], $response->getData());

	}//end testFilesWithoutASessionIs401()

	/**
	 * A failing list is a 500 carrying the failure's message.
	 *
	 * @return void
	 */
	public function testAFailingListIsA500(): void {
		$this->flatList->method('listFor')->willThrowException(new RuntimeException('register unreachable'));

		$response = $this->controller()->files(register: 'zaken', schema: 'zaak', id: 'z-1');

		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
		$this->assertSame(['error' => 'register unreachable'], $response->getData());

	}//end testAFailingListIsA500()
}//end class
