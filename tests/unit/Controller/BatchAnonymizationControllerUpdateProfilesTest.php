<?php

/**
 * Unit tests for BatchAnonymizationController::updateProfiles().
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

use OCA\Filinq\Controller\BatchAnonymizationController;
use OCA\Filinq\Kernel\Contracts\AnonymizationProfileServiceInterface;
use OCA\Filinq\Service\BatchAnonymizeService;
use OCA\Filinq\Service\BatchExtractionService;
use OCA\Filinq\Service\BatchReportService;
use OCA\Filinq\Service\BatchRequestService;
use OCA\Filinq\Service\BatchStateService;
use OCA\Filinq\Service\BatchUploadService;
use OCA\Filinq\Service\EntityConsolidationService;
use OCA\Filinq\Service\FolderBatchService;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * updateProfiles() validates the anonymise/keep lists and hands exactly those
 * two lists to the profile store.
 *
 * @covers \OCA\Filinq\Controller\BatchAnonymizationController
 * @uses   \OCA\Filinq\Service\BatchRequestService
 */
class BatchAnonymizationControllerUpdateProfilesTest extends TestCase {

	/**
	 * @var AnonymizationProfileServiceInterface&MockObject
	 */
	private AnonymizationProfileServiceInterface $profiles;

	/**
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface $logger;

	/**
	 * Build the controller.
	 *
	 * @param array<string, mixed> $params   Request parameters
	 * @param bool                 $loggedIn Whether a user is logged in
	 *
	 * @return BatchAnonymizationController
	 */
	private function controller(array $params, bool $loggedIn = true): BatchAnonymizationController {
		$request = $this->createMock(IRequest::class);
		$request->method('getParams')->willReturn($params);

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn (string $s): string => $s);

		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($loggedIn === true ? $this->createMock(IUser::class) : null);

		$this->profiles = $this->createMock(AnonymizationProfileServiceInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		return new BatchAnonymizationController(
			appName: 'filinq',
			request: $request,
			logger: $this->logger,
			stateService: $this->createMock(BatchStateService::class),
			uploadService: $this->createMock(BatchUploadService::class),
			extractService: $this->createMock(BatchExtractionService::class),
			anonService: $this->createMock(BatchAnonymizeService::class),
			reportService: $this->createMock(BatchReportService::class),
			entityService: $this->createMock(EntityConsolidationService::class),
			profileService: $this->profiles,
			folderBatchService: $this->createMock(FolderBatchService::class),
			l10n: $l10n,
			appConfig: $this->createMock(IAppConfig::class),
			userSession: $session,
			batchRequest: new BatchRequestService(l10n: $l10n)
		);

	}//end controller()

	public function testAnonymousCallerGets401AndNothingIsSaved(): void {
		$controller = $this->controller(['anonymize' => [], 'keep' => []], loggedIn: false);
		$this->profiles->expects($this->never())->method('saveProfile');

		$response = $controller->updateProfiles();

		$this->assertSame(401, $response->getStatus());
		$this->assertSame(['error' => 'Not authenticated'], $response->getData());

	}//end testAnonymousCallerGets401AndNothingIsSaved()

	/**
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public static function invalidPayloads(): array {
		return [
			'nothing sent' => [[]],
			'keep missing' => [['anonymize' => ['PERSON']]],
			'anonymize missing' => [['keep' => ['ORG']]],
			'anonymize a string' => [['anonymize' => 'PERSON', 'keep' => []]],
			'keep a string' => [['anonymize' => [], 'keep' => 'ORG']],
			'keep null' => [['anonymize' => [], 'keep' => null]],
		];

	}//end invalidPayloads()

	/**
	 * @dataProvider invalidPayloads
	 *
	 * @param array<string, mixed> $params The request parameters
	 */
	public function testANonListIsRefusedWith400(array $params): void {
		$controller = $this->controller($params);
		$this->profiles->expects($this->never())->method('saveProfile');

		$response = $controller->updateProfiles();

		$this->assertSame(400, $response->getStatus());
		$this->assertSame(['error' => 'Invalid format'], $response->getData());

	}//end testANonListIsRefusedWith400()

	public function testSavesOnlyTheTwoListsAndConfirms(): void {
		$controller = $this->controller(
			[
				'anonymize' => ['PERSON', 'EMAIL'],
				'keep' => [],
				'_route' => 'filinq.batch.updateProfiles',
				'extra' => 'ignored',
			]
		);
		$this->profiles->expects($this->once())->method('saveProfile')
			->with(['anonymize' => ['PERSON', 'EMAIL'], 'keep' => []]);

		$response = $controller->updateProfiles();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['message' => 'Profile updated'], $response->getData());

	}//end testSavesOnlyTheTwoListsAndConfirms()

	/**
	 * @return array<string, array{0: int, 1: int}>
	 */
	public static function failureCodes(): array {
		return [
			'no code' => [0, 500],
			'client error code kept' => [422, 422],
			'below the HTTP error range' => [302, 500],
			'above it' => [600, 500],
			'upper edge kept' => [599, 599],
		];

	}//end failureCodes()

	/**
	 * @dataProvider failureCodes
	 */
	public function testAStoreFailureIsLoggedAndAnsweredWithItsHttpCode(int $code, int $expected): void {
		$controller = $this->controller(['anonymize' => ['PERSON'], 'keep' => ['ORG']]);
		$exception = new RuntimeException('disk full', $code);
		$this->profiles->method('saveProfile')->willThrowException($exception);
		$this->logger->expects($this->once())->method('error')
			->with('Failed to update profile: disk full', ['exception' => $exception]);

		$response = $controller->updateProfiles();

		$this->assertSame($expected, $response->getStatus());
		$this->assertSame(['error' => 'Failed to update profile: disk full'], $response->getData());

	}//end testAStoreFailureIsLoggedAndAnsweredWithItsHttpCode()
}//end class
