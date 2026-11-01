<?php

/**
 * Characterization tests for BatchAnonymizationController::batchAnonymize()
 *
 * Pins each refusal, the arguments that reach the batch service, the answer
 * status, and the failure body.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\BatchAnonymizationController;
use OCA\Filinq\Kernel\Contracts\AnonymizationProfileServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchAnonymizeServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchExtractionServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchReportServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchRequestServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchStateServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchUploadServiceInterface;
use OCA\Filinq\Kernel\Contracts\EntityConsolidationServiceInterface;
use OCA\Filinq\Kernel\Contracts\FolderBatchServiceInterface;
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
 * Characterization of the batch anonymise endpoint.
 *
 * @covers \OCA\Filinq\Controller\BatchAnonymizationController
 */
class BatchAnonymizationControllerAnonymizeTraceTest extends TestCase {

	private const REQUEST = [
		'entities' => [['text' => 'Jan']],
		'unredactedEntities' => [['text' => 'Gemeente']],
		'appendBasisSummary' => false,
		'hasStrayBases' => false,
	];

	private BatchAnonymizeServiceInterface&MockObject $anon;

	private BatchRequestServiceInterface&MockObject $batchRequest;

	private LoggerInterface&MockObject $logger;

	/**
	 * Fresh doubles: a valid body, scope `selected`, status 207.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->anon = $this->createMock(BatchAnonymizeServiceInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->batchRequest = $this->createMock(BatchRequestServiceInterface::class);
		$this->batchRequest->method('validateBody')->willReturn(['error' => null, 'request' => self::REQUEST]);
		$this->batchRequest->method('resolveScope')->willReturnCallback(static fn (array $params): string => ($params['scope'] ?? 'all'));
		$this->batchRequest->method('resolveHttpStatus')->willReturn(207);

	}//end setUp()

	/**
	 * The request reaches the batch service with its format and scope, and the
	 * answer carries the service's status.
	 *
	 * @return void
	 */
	public function testRequestReachesTheServiceAndTheStatusIsKept(): void {
		$this->anon->expects($this->once())->method('anonymizeBatch')
			->with('b-1', self::REQUEST['entities'], self::REQUEST['unredactedEntities'], 'preserve', 'selected')
			->willReturn(['processed' => 2]);
		$this->anon->expects($this->never())->method('anonymizeBatchWithBasisSummary');

		$response = $this->controller(params: ['outputFormat' => 'preserve', 'scope' => 'selected'])->batchAnonymize('b-1');

		$this->assertSame(207, $response->getStatus());
		$this->assertSame(['processed' => 2], $response->getData());

	}//end testRequestReachesTheServiceAndTheStatusIsKept()

	/**
	 * Without a session nothing is validated or run.
	 *
	 * @return void
	 */
	public function testNoSessionAnswers401(): void {
		$this->batchRequest->expects($this->never())->method('validateBody');
		$this->anon->expects($this->never())->method('anonymizeBatch');

		$response = $this->controller(params: [], signedIn: false)->batchAnonymize('b-1');

		$this->assertSame(401, $response->getStatus());
		$this->assertSame(['error' => 'Not authenticated'], $response->getData());

	}//end testNoSessionAnswers401()

	/**
	 * An unknown output format answers 400 naming the valid ones.
	 *
	 * @return void
	 */
	public function testUnknownOutputFormatAnswers400(): void {
		$this->anon->expects($this->never())->method('anonymizeBatch');

		$response = $this->controller(params: ['outputFormat' => 'docx'])->batchAnonymize('b-1');

		$this->assertSame(400, $response->getStatus());
		$this->assertSame(['error' => 'Invalid outputFormat: must be one of pdf-only, pdf, preserve'], $response->getData());

	}//end testUnknownOutputFormatAnswers400()

	/**
	 * A malformed unredacted entry answers with the validator's own refusal.
	 *
	 * @return void
	 */
	public function testUnredactedRefusalIsPassedThrough(): void {
		$batchRequest = $this->createMock(BatchRequestServiceInterface::class);
		$batchRequest->method('validateBody')->willReturn(['error' => null, 'request' => self::REQUEST]);
		$batchRequest->method('validateUnredactedEntries')->with(self::REQUEST['unredactedEntities'])->willReturn(['status' => 422, 'body' => ['error' => 'entry 0 has no text']]);
		$this->batchRequest = $batchRequest;
		$this->anon->expects($this->never())->method('anonymizeBatch');

		$response = $this->controller(params: [])->batchAnonymize('b-1');

		$this->assertSame(422, $response->getStatus());
		$this->assertSame(['error' => 'entry 0 has no text'], $response->getData());

	}//end testUnredactedRefusalIsPassedThrough()

	/**
	 * A failing run answers with its code and the prefixed message, and logs.
	 *
	 * @return void
	 */
	public function testFailureIsAnsweredAndLogged(): void {
		$this->anon->method('anonymizeBatch')->willThrowException(new RuntimeException('batch gone', 404));
		$this->logger->expects($this->once())->method('error')->with('Anonymization failed: batch gone', $this->anything());

		$response = $this->controller(params: [])->batchAnonymize('b-1');

		$this->assertSame(404, $response->getStatus());
		$this->assertSame(['error' => 'Anonymization failed: batch gone'], $response->getData());

	}//end testFailureIsAnsweredAndLogged()

	/**
	 * The controller over a request with $params.
	 *
	 * @param array<string, mixed> $params The request body.
	 * @param bool $signedIn Whether a session user exists.
	 *
	 * @return BatchAnonymizationController The controller.
	 */
	private function controller(array $params, bool $signedIn = true): BatchAnonymizationController {
		$request = $this->createMock(IRequest::class);
		$request->method('getParams')->willReturn($params);
		$request->method('getParam')->willReturnCallback(
			static fn (string $key, mixed $default = null): mixed => (array_key_exists($key, $params) === true ? $params[$key] : $default)
		);

		$session = $this->createMock(IUserSession::class);
		$user = null;
		if ($signedIn === true) {
			$user = $this->createMock(IUser::class);
		}

		$session->method('getUser')->willReturn($user);

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')->willReturnArgument(2);

		return new BatchAnonymizationController(
			appName: 'filinq',
			request: $request,
			logger: $this->logger,
			stateService: $this->createMock(BatchStateServiceInterface::class),
			uploadService: $this->createMock(BatchUploadServiceInterface::class),
			extractService: $this->createMock(BatchExtractionServiceInterface::class),
			anonService: $this->anon,
			reportService: $this->createMock(BatchReportServiceInterface::class),
			entityService: $this->createMock(EntityConsolidationServiceInterface::class),
			profileService: $this->createMock(AnonymizationProfileServiceInterface::class),
			folderBatchService: $this->createMock(FolderBatchServiceInterface::class),
			l10n: $l10n,
			appConfig: $appConfig,
			userSession: $session,
			batchRequest: $this->batchRequest
		);

	}//end controller()
}//end class
