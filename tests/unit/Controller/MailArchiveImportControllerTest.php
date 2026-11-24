<?php

/**
 * Unit tests for MailArchiveImportController
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use InvalidArgumentException;
use OCA\Filinq\Controller\MailArchiveImportController;
use OCA\Filinq\Service\MailArchive\MailArchiveImportService;
use OCA\Filinq\Service\MailArchive\MailArchiveSettings;
use OCA\Filinq\Settings\FilinqAdmin;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use RuntimeException;

/**
 * The routes act as the session user, keep a refusal's status, and hide anything else behind a 500.
 *
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-4
 */
class MailArchiveImportControllerTest extends TestCase {

	/**
	 * The imports.
	 *
	 * @var MailArchiveImportService&MockObject
	 */
	private MailArchiveImportService $imports;

	/**
	 * The settings.
	 *
	 * @var MailArchiveSettings&MockObject
	 */
	private MailArchiveSettings $settings;

	/**
	 * The session.
	 *
	 * @var IUserSession&MockObject
	 */
	private IUserSession $session;

	/**
	 * Build the doubles with a signed-in archivist.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->imports = $this->createMock(MailArchiveImportService::class);
		$this->settings = $this->createMock(MailArchiveSettings::class);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('archivaris');
		$this->session = $this->createMock(IUserSession::class);
		$this->session->method('getUser')->willReturn($user);

	}//end setUp()

	/**
	 * The controller under test.
	 *
	 * @param LoggerInterface|null $logger The logger.
	 *
	 * @return MailArchiveImportController The controller.
	 */
	private function controller(?LoggerInterface $logger = null): MailArchiveImportController {
		return new MailArchiveImportController(
			appName: 'filinq',
			request: $this->createMock(IRequest::class),
			imports: $this->imports,
			settings: $this->settings,
			userSession: $this->session,
			logger: ($logger ?? $this->createMock(LoggerInterface::class))
		);

	}//end controller()

	/**
	 * Starting passes the session user and the file, and answers 201 with the import.
	 *
	 * @return void
	 */
	public function testCreateStartsAnImportAsTheSessionUser(): void {
		$this->imports->expects($this->once())->method('start')->with('archivaris', 42)->willReturn(['uuid' => 'import-1', 'status' => 'mapping']);

		$response = $this->controller()->create(fileId: 42);

		$this->assertSame(201, $response->getStatus());
		$this->assertSame(['uuid' => 'import-1', 'status' => 'mapping'], $response->getData());

	}//end testCreateStartsAnImportAsTheSessionUser()

	/**
	 * Without a file there is nothing to start.
	 *
	 * @return void
	 */
	public function testCreateWithoutAFileIs400(): void {
		$this->imports->expects($this->never())->method('start');

		$response = $this->controller()->create();

		$this->assertSame(400, $response->getStatus());
		$this->assertSame(['error' => 'file_required'], $response->getData());

	}//end testCreateWithoutAFileIs400()

	/**
	 * Read, list, map and cancel all act as the session user.
	 *
	 * @return void
	 */
	public function testEveryRouteActsAsTheSessionUser(): void {
		$this->imports->expects($this->once())->method('mine')->with('archivaris')->willReturn([['uuid' => 'import-1']]);
		$this->imports->expects($this->once())->method('get')->with('archivaris', 'import-1')->willReturn(['uuid' => 'import-1']);
		$this->imports->expects($this->once())->method('map')
			->with('archivaris', 'import-1', [['archiveFolder' => 'Inbox', 'dossier' => 'd-1']])
			->willReturn(['status' => 'importing']);
		$this->imports->expects($this->once())->method('cancel')->with('archivaris', 'import-1')->willReturn(['status' => 'cancelled']);

		$controller = $this->controller();
		$this->assertSame(['results' => [['uuid' => 'import-1']]], $controller->index()->getData());
		$this->assertSame(['uuid' => 'import-1'], $controller->show(uuid: 'import-1')->getData());
		$this->assertSame(['status' => 'importing'], $controller->start(uuid: 'import-1', folderMap: [['archiveFolder' => 'Inbox', 'dossier' => 'd-1']])->getData());
		$this->assertSame(['status' => 'cancelled'], $controller->cancel(uuid: 'import-1')->getData());

	}//end testEveryRouteActsAsTheSessionUser()

	/**
	 * A refused dossier keeps its 403, a missing import its 404, a wrong state its 409.
	 *
	 * @return void
	 */
	public function testARefusalKeepsItsStatus(): void {
		$this->imports->method('map')->willThrowException(new InvalidArgumentException('dossier_refused', 403));
		$this->imports->method('get')->willThrowException(new InvalidArgumentException('not_found', 404));
		$this->imports->method('cancel')->willThrowException(new InvalidArgumentException('already_ended', 409));

		$controller = $this->controller();
		$refused = $controller->start(uuid: 'import-1', folderMap: []);
		$this->assertSame(403, $refused->getStatus());
		$this->assertSame(['error' => 'dossier_refused'], $refused->getData());
		$this->assertSame(404, $controller->show(uuid: 'import-1')->getStatus());
		$this->assertSame(409, $controller->cancel(uuid: 'import-1')->getStatus());

	}//end testARefusalKeepsItsStatus()

	/**
	 * Anything else is a 500 with a generic body; the reason goes to the log only.
	 *
	 * @return void
	 */
	public function testAnUnexpectedErrorIsAGeneric500(): void {
		$this->imports->method('get')->willThrowException(new RuntimeException('register down at db-7'));
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('error')->with($this->anything(), ['exception' => 'register down at db-7']);

		$response = $this->controller(logger: $logger)->show(uuid: 'import-1');

		$this->assertSame(500, $response->getStatus());
		$this->assertSame(['error' => 'failed'], $response->getData());

	}//end testAnUnexpectedErrorIsAGeneric500()

	/**
	 * Without a session nothing runs.
	 *
	 * @return void
	 */
	public function testWithoutASessionNothingRuns(): void {
		$this->session = $this->createMock(IUserSession::class);
		$this->session->method('getUser')->willReturn(null);
		$this->imports->expects($this->never())->method('mine');

		$this->assertSame(401, $this->controller()->index()->getStatus());

	}//end testWithoutASessionNothingRuns()

	/**
	 * The admin routes answer the settings from MailArchiveSettings.
	 *
	 * @return void
	 */
	public function testTheAdminRoutesAnswerTheSettings(): void {
		$this->settings->method('toArray')->willReturn(['messagesPerRun' => 200, 'readpstInstalled' => false]);
		$this->settings->expects($this->once())->method('update')->with(50)->willReturn(['messagesPerRun' => 50, 'readpstInstalled' => false]);

		$this->assertSame(['messagesPerRun' => 200, 'readpstInstalled' => false], $this->controller()->settings()->getData());
		$this->assertSame(['messagesPerRun' => 50, 'readpstInstalled' => false], $this->controller()->updateSettings(messagesPerRun: 50)->getData());

	}//end testTheAdminRoutesAnswerTheSettings()

	/**
	 * The import routes are for signed-in users; the settings routes for admins.
	 *
	 * @return void
	 */
	public function testEachRouteDeclaresItsAccess(): void {
		foreach (['index', 'create', 'show', 'start', 'cancel'] as $method) {
			$this->assertCount(1, (new ReflectionMethod(MailArchiveImportController::class, $method))->getAttributes(NoAdminRequired::class), $method);
		}

		foreach (['settings', 'updateSettings'] as $method) {
			$attributes = (new ReflectionMethod(MailArchiveImportController::class, $method))->getAttributes(AuthorizedAdminSetting::class);
			$this->assertCount(1, $attributes, $method);
			$this->assertSame(['settings' => FilinqAdmin::class], $attributes[0]->getArguments());
			$this->assertCount(0, (new ReflectionMethod(MailArchiveImportController::class, $method))->getAttributes(NoAdminRequired::class), $method);
		}

	}//end testEachRouteDeclaresItsAccess()
}//end class
