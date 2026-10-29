<?php

/**
 * The anonymise endpoint answers a document nobody checked with a 409 and a
 * message the reviewer can act on, and lets a checked one through.
 *
 * The service graph behind the controller is real down to the runner; the
 * review guard is real over a stubbed mark store. The mark in the passing
 * case is written through the same guard method the review endpoint calls,
 * over the entities the screen sends, so the test also proves that a mark the
 * screen records is a mark the write path accepts.
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
 * @spec openspec/changes/anonymise-asks-for-the-review-mark/tasks.md#task-1
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\AnonymizationController;
use OCA\Filinq\Service\AnonymizeRequestService;
use OCA\Filinq\Service\DocumentAnonymizeRunner;
use OCA\Filinq\Service\FileListingService;
use OCA\Filinq\Service\Redaction\DetectionRunIdentity;
use OCA\Filinq\Service\Redaction\RedactionOutputGuard;
use OCA\Filinq\Service\Redaction\RedactionReviewGate;
use OCA\Filinq\Service\Redaction\RedactionReviewMarkRepository;
use OCA\Filinq\Tests\Unit\Service\BuildsAnonymizationService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * AnonymizationController::anonymize() over the human review gate.
 */
class AnonymizationControllerReviewGateTest extends TestCase {

	use BuildsAnonymizationService;

	/**
	 * The entities as the review screen sends them.
	 */
	private const SENT = [
		['type' => 'PERSON', 'value' => 'Jan de Wit', 'confidence' => 0.92],
		['type' => 'BSN', 'value' => '123456782', 'confidence' => 0.99],
	];

	/**
	 * No mark: 409, the reason by name, the message to translate, and no file.
	 *
	 * @return void
	 */
	public function testAnUncheckedDocumentIsAConflictNotAServerError(): void {
		$response = $this->controllerOver(mark: null, runner: $this->runnerThatMustNotRun())
			->anonymize(fileId: 42);

		$this->assertSame(409, $response->getStatus());
		$this->assertSame(RedactionReviewGate::NEVER_CHECKED, $response->getData()['reviewRequired']);
		$this->assertSame('Check the detected data first', $response->getData()['error']);
		$this->assertSame('42', $response->getData()['document']);

	}//end testAnUncheckedDocumentIsAConflictNotAServerError()

	/**
	 * A mark over different findings: 409 with its own instruction.
	 *
	 * @return void
	 */
	public function testAMarkOverOtherFindingsIsAConflictToo(): void {
		$stale = $this->guardOver(marks: $this->storeThatEchoes())
			->markChecked(fileId: 42, entities: [self::SENT[0]], checkedBy: 'ingrid');

		$response = $this->controllerOver(mark: $stale, runner: $this->runnerThatMustNotRun())
			->anonymize(fileId: 42);

		$this->assertSame(409, $response->getStatus());
		$this->assertSame(RedactionReviewGate::CHECKED_AN_OLDER_RUN, $response->getData()['reviewRequired']);
		$this->assertStringContainsString('changed after the check', $response->getData()['error']);

	}//end testAMarkOverOtherFindingsIsAConflictToo()

	/**
	 * Control: the mark the review endpoint records over the sent entities opens the door.
	 *
	 * @return void
	 */
	public function testAMarkOverTheSentEntitiesLetsTheRunThrough(): void {
		$mark = $this->guardOver(marks: $this->storeThatEchoes())
			->markChecked(fileId: 42, entities: array_reverse(self::SENT), checkedBy: 'ingrid');

		$runner = $this->getMockBuilder(DocumentAnonymizeRunner::class)
			->disableOriginalConstructor()
			->onlyMethods(['run'])
			->getMock();
		$runner->expects($this->once())->method('run')->willReturn(['anonymizedFileId' => 99]);

		$response = $this->controllerOver(mark: $mark, runner: $runner)->anonymize(fileId: 42);

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(99, $response->getData()['anonymizedFileId']);

	}//end testAMarkOverTheSentEntitiesLetsTheRunThrough()

	/**
	 * A real guard over the given mark store.
	 *
	 * @param RedactionReviewMarkRepository $marks The store.
	 *
	 * @return RedactionOutputGuard The guard.
	 */
	private function guardOver(RedactionReviewMarkRepository $marks): RedactionOutputGuard {
		return new RedactionOutputGuard(
			gate: new RedactionReviewGate(),
			marks: $marks,
			runs: new DetectionRunIdentity()
		);

	}//end guardOver()

	/**
	 * A mark store that hands back what it was given.
	 *
	 * @return RedactionReviewMarkRepository The double.
	 */
	private function storeThatEchoes(): RedactionReviewMarkRepository {
		$marks = $this->getMockBuilder(RedactionReviewMarkRepository::class)
			->disableOriginalConstructor()
			->onlyMethods(['findFor', 'save'])
			->getMock();
		$marks->method('save')->willReturnArgument(0);

		return $marks;

	}//end storeThatEchoes()

	/**
	 * A runner that fails the test when it is reached.
	 *
	 * @return DocumentAnonymizeRunner The double.
	 */
	private function runnerThatMustNotRun(): DocumentAnonymizeRunner {
		$runner = $this->getMockBuilder(DocumentAnonymizeRunner::class)
			->disableOriginalConstructor()
			->onlyMethods(['run'])
			->getMock();
		$runner->expects($this->never())->method('run');

		return $runner;

	}//end runnerThatMustNotRun()

	/**
	 * The controller over a real service graph whose guard finds the given mark.
	 *
	 * @param array<string, mixed>|null $mark   The stored mark, or none.
	 * @param DocumentAnonymizeRunner   $runner The runner.
	 *
	 * @return AnonymizationController The controller.
	 */
	private function controllerOver(?array $mark, DocumentAnonymizeRunner $runner): AnonymizationController {
		$marks = $this->getMockBuilder(RedactionReviewMarkRepository::class)
			->disableOriginalConstructor()
			->onlyMethods(['findFor', 'save'])
			->getMock();
		$marks->method('findFor')->willReturn($mark);

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willThrowException(new RuntimeException('not registered'));

		$request = $this->createMock(IRequest::class);
		$request->method('getParams')->willReturn(['entities' => self::SENT, 'outputFormat' => 'preserve']);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('ingrid');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		$folder = $this->createMock(Folder::class);
		$folder->method('getById')->willReturn([$this->createMock(File::class)]);
		$rootFolder = $this->createMock(IRootFolder::class);
		$rootFolder->method('getUserFolder')->willReturn($folder);

		$logger = new NullLogger();
		$anonymizationService = $this->makeAnonymizationServiceFrom(
			deps: [
				'container' => $container,
				'anonymizeRunner' => $runner,
				'reviewGuard' => $this->guardOver(marks: $marks),
			]
		);

		return new AnonymizationController(
			appName: 'filinq',
			request: $request,
			logger: $logger,
			anonymizationService: $anonymizationService,
			fileListingService: $this->createMock(FileListingService::class),
			l10n: $l10n,
			appConfig: $this->createMock(IAppConfig::class),
			anonymizeRequest: new AnonymizeRequestService(logger: $logger, anonymizationService: $anonymizationService, l10n: $l10n, rootFolder: $rootFolder, userSession: $session)
		);

	}//end controllerOver()
}//end class
