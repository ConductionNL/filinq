<?php

/**
 * Unit tests for MdtoController: who may export and transfer, and how each
 * transfer outcome answers.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-e-depot-handoff-rides-openregisters-transfer-stack-req-ddtmm-006
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\MdtoController;
use OCA\Filinq\Service\MdtoTransferService;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

#[CoversClass(MdtoController::class)]
class MdtoControllerTest extends TestCase {

	private const UUID = '6f1c2d3e-4b5a-4c6d-8e7f-0a1b2c3d4e5f';

	/**
	 * The transfer service.
	 *
	 * @var MdtoTransferService&MockObject
	 */
	private MdtoTransferService $transfers;

	protected function setUp(): void {
		$this->transfers = $this->createMock(MdtoTransferService::class);

	}//end setUp()

	/**
	 * The controller for a user.
	 *
	 * @param string|null $uid     The signed-in user, null for nobody.
	 * @param bool        $admin   Whether they are an administrator.
	 * @param bool        $archive Whether they are in filinq-policy-admins.
	 * @param string      $format  The `format` parameter.
	 *
	 * @return MdtoController
	 */
	private function controller(?string $uid, bool $admin=false, bool $archive=false, string $format='xml'): MdtoController {
		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnCallback(static fn (string $key, mixed $default=null): mixed => ($key === 'format' ? $format : $default));
		$session = $this->createMock(IUserSession::class);
		$user    = null;
		if ($uid !== null) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($uid);
		}

		$session->method('getUser')->willReturn($user);
		$groups = $this->createMock(IGroupManager::class);
		$groups->method('isAdmin')->willReturn($admin);
		$groups->method('isInGroup')->willReturnCallback(
			static fn (string $who, string $group): bool => $archive === true && $group === 'filinq-policy-admins'
		);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		return new MdtoController('filinq', $request, $this->transfers, $session, $groups, $l10n, new NullLogger());

	}//end controller()

	public function testTheStatusSaysWhetherTheCallerMayTransfer(): void {
		$this->transfers->method('status')->with(self::UUID)->willReturn(['complete' => false, 'missing' => ['naam']]);

		$this->assertSame(['complete' => false, 'missing' => ['naam'], 'mayTransfer' => false], $this->controller('reader')->status(self::UUID)->getData());
		$this->assertTrue($this->controller('archivist', archive: true)->status(self::UUID)->getData()['mayTransfer']);

	}//end testTheStatusSaysWhetherTheCallerMayTransfer()

	/**
	 * Callers outside the transfer group.
	 *
	 * @return array<string, array{0: string|null}>
	 */
	public static function outsiders(): array {
		return ['a plain user' => ['reader'], 'nobody signed in' => [null]];

	}//end outsiders()

	/**
	 * Export and transfer refuse with 403 for anyone outside filinq-policy-admins, and nothing runs.
	 *
	 * @param string|null $uid The caller.
	 *
	 * @return void
	 */
	#[DataProvider('outsiders')]
	public function testOutsidersMayNeitherExportNorTransfer(?string $uid): void {
		$this->transfers->expects($this->never())->method('export');
		$this->transfers->expects($this->never())->method('transfer');

		$this->assertSame(403, $this->controller($uid)->export(self::UUID)->getStatus());
		$this->assertSame(403, $this->controller($uid)->transfer(self::UUID)->getStatus());

	}//end testOutsidersMayNeitherExportNorTransfer()

	public function testAnAdministratorMayTransfer(): void {
		$this->transfers->expects($this->once())->method('transfer')->willReturn(['status' => 'transferred', 'transferList' => 'tl-1']);

		$this->assertSame(200, $this->controller('root', admin: true)->transfer(self::UUID)->getStatus());

	}//end testAnAdministratorMayTransfer()

	/**
	 * Each transfer outcome and its HTTP status.
	 *
	 * @return array<string, array{0: string, 1: int}>
	 */
	public static function outcomes(): array {
		return ['blocked' => ['blocked', 422], 'manual delivery' => ['manual', 200], 'transferred' => ['transferred', 200], 'not confirmed' => ['failed', 502]];

	}//end outcomes()

	/**
	 * A blocked transfer is a 422 with the missing fields; an unconfirmed one a 502.
	 *
	 * @param string $outcome The service's status.
	 * @param int    $http    The HTTP status.
	 *
	 * @return void
	 */
	#[DataProvider('outcomes')]
	public function testEachOutcomeAnswersWithItsStatus(string $outcome, int $http): void {
		$this->transfers->method('transfer')->willReturn(['status' => $outcome, 'missing' => ['aggregatieniveau']]);

		$response = $this->controller('archivist', archive: true)->transfer(self::UUID);

		$this->assertSame($http, $response->getStatus());
		$this->assertSame($outcome, $response->getData()['status']);

	}//end testEachOutcomeAnswersWithItsStatus()

	public function testTheExportIsADownloadInTheRequestedFormat(): void {
		$this->transfers->expects($this->once())->method('export')->with(self::UUID, 'json')
			->willReturn(['fileName' => self::UUID . '.mdto.json', 'mimeType' => 'application/json', 'content' => '{}']);

		$response = $this->controller('archivist', archive: true, format: 'json')->export(self::UUID);

		$this->assertInstanceOf(DataDownloadResponse::class, $response);
		$this->assertStringContainsString(self::UUID . '.mdto.json', (string) $response->getHeaders()['Content-Disposition']);

	}//end testTheExportIsADownloadInTheRequestedFormat()

	public function testAnUnknownFormatFallsBackToXml(): void {
		$this->transfers->expects($this->once())->method('export')->with(self::UUID, 'xml')
			->willReturn(['fileName' => 'x.mdto.xml', 'mimeType' => 'application/xml', 'content' => '<x/>']);

		$this->controller('archivist', archive: true, format: 'pdf')->export(self::UUID);

	}//end testAnUnknownFormatFallsBackToXml()

	public function testAnUnreadableRecordIs404AndOtherFailuresHideTheirText(): void {
		$this->transfers->method('status')->willThrowException(new RuntimeException('Record x cannot be read.', 404));
		$this->transfers->method('transfer')->willThrowException(new RuntimeException('secret detail'));

		$this->assertSame(404, $this->controller('reader')->status(self::UUID)->getStatus());
		$failed = $this->controller('archivist', archive: true)->transfer(self::UUID);
		$this->assertInstanceOf(JSONResponse::class, $failed);
		$this->assertSame(500, $failed->getStatus());
		$this->assertStringNotContainsString('secret', (string) json_encode($failed->getData()));

	}//end testAnUnreadableRecordIs404AndOtherFailuresHideTheirText()
}//end class
