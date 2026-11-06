<?php

/**
 * Characterization tests for ComparisonController::compare()
 *
 * Pins the 401 and 400 refusals, the subjects the comparison service
 * receives, the answer for every comparison refusal reason, and the logged
 * 500 on any other failure.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-comparison/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use Error;
use OCA\Filinq\Controller\ComparisonController;
use OCA\Filinq\Exception\ComparisonException;
use OCA\Filinq\Kernel\Contracts\DocumentComparisonServiceInterface;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Characterization of the comparison endpoint.
 *
 * @covers \OCA\Filinq\Controller\ComparisonController
 * @uses   \OCA\Filinq\Exception\ComparisonException
 */
class ComparisonControllerTraceTest extends TestCase {

	private const LEFT = ['fileId' => 1, 'versionTimestamp' => 100];

	private const RIGHT = ['fileId' => 2];

	private DocumentComparisonServiceInterface&MockObject $service;

	private LoggerInterface&MockObject $logger;

	/**
	 * Fresh doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->service = $this->createMock(DocumentComparisonServiceInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * Two subjects with a file id are compared as given.
	 *
	 * @return void
	 */
	public function testSubjectsAreCompared(): void {
		$this->service->expects($this->once())->method('compare')
			->with($this->identicalTo(self::LEFT), $this->identicalTo(self::RIGHT))
			->willReturn(['changes' => 3]);

		$response = $this->controller(params: ['left' => self::LEFT, 'right' => self::RIGHT])->compare();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['changes' => 3], $response->getData());

	}//end testSubjectsAreCompared()

	/**
	 * A missing, non-array or id-less subject on either side is a 400 before any comparison.
	 *
	 * @return void
	 */
	public function testIncompleteSubjectsAreBadRequest(): void {
		$this->service->expects($this->never())->method('compare');
		$cases = [
			[],
			['left' => self::LEFT],
			['right' => self::RIGHT],
			['left' => 'x', 'right' => self::RIGHT],
			['left' => self::LEFT, 'right' => 'x'],
			['left' => ['versionTimestamp' => 1], 'right' => self::RIGHT],
			['left' => self::LEFT, 'right' => ['versionTimestamp' => 1]],
			['left' => ['fileId' => null], 'right' => self::RIGHT],
		];

		foreach ($cases as $params) {
			$response = $this->controller(params: $params)->compare();
			$this->assertSame(400, $response->getStatus());
			$this->assertSame(['error' => 'Both "left" and "right" subjects with a fileId are required'], $response->getData());
		}

	}//end testIncompleteSubjectsAreBadRequest()

	/**
	 * Each refusal reason answers with its message, its reason and its status.
	 *
	 * @return void
	 */
	public function testRefusalsAnswerTheirReason(): void {
		$cases = [
			['not-found', 404, 'Subject not found'],
			['versions-unavailable', 501, 'File versions are not available on this instance'],
			['too-large', 413, 'A subject is too large to compare'],
			['unsupported-format', 415, 'A subject has an unsupported format'],
			['something-else', 409, 'Comparison failed'],
		];
		$this->logger->expects($this->never())->method('error');

		foreach ($cases as [$reason, $status, $message]) {
			$service = $this->createMock(DocumentComparisonServiceInterface::class);
			$service->method('compare')->willThrowException(new ComparisonException($status, $reason, 'internal'));
			$this->service = $service;

			$response = $this->controller(params: ['left' => self::LEFT, 'right' => self::RIGHT])->compare();

			$this->assertSame($status, $response->getStatus());
			$this->assertSame(['error' => $message, 'reason' => $reason], $response->getData());
		}

	}//end testRefusalsAnswerTheirReason()

	/**
	 * Any other failure, an Error included, is logged and answered 500.
	 *
	 * @return void
	 */
	public function testOtherFailureAnswers500(): void {
		$this->service->method('compare')->willThrowException(new Error('diff engine crashed'));
		$this->logger->expects($this->once())->method('error')->with(
			$this->identicalTo('Comparison failed'),
			$this->identicalTo(['exception' => 'diff engine crashed'])
		);

		$response = $this->controller(params: ['left' => self::LEFT, 'right' => self::RIGHT])->compare();

		$this->assertSame(500, $response->getStatus());
		$this->assertSame(['error' => 'Comparison failed'], $response->getData());

	}//end testOtherFailureAnswers500()

	/**
	 * Without a session user nothing is compared.
	 *
	 * @return void
	 */
	public function testNotAuthenticated(): void {
		$this->service->expects($this->never())->method('compare');

		$response = $this->controller(params: ['left' => self::LEFT, 'right' => self::RIGHT], signedIn: false)->compare();

		$this->assertSame(401, $response->getStatus());
		$this->assertSame(['error' => 'Not authenticated'], $response->getData());

	}//end testNotAuthenticated()

	/**
	 * The controller over a request with the given parameters.
	 *
	 * @param array<string, mixed> $params   The request values.
	 * @param bool                 $signedIn Whether a session user exists.
	 *
	 * @return ComparisonController The controller.
	 */
	private function controller(array $params, bool $signedIn = true): ComparisonController {
		$request = $this->createMock(IRequest::class);
		$request->method('getParams')->willReturn($params);
		$request->method('getParam')->willReturnCallback(
			fn (string $key, mixed $default = null): mixed => (array_key_exists($key, $params) === true ? $params[$key] : $default)
		);

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(fn (string $text, array $parameters = []): string => vsprintf($text, $parameters));

		$session = $this->createMock(IUserSession::class);
		$user = null;
		if ($signedIn === true) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn('alice');
		}

		$session->method('getUser')->willReturn($user);

		return new ComparisonController(
			appName: 'filinq',
			request: $request,
			logger: $this->logger,
			service: $this->service,
			l10n: $l10n,
			userSession: $session,
		);
	}//end controller()
}//end class
