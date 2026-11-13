<?php

/**
 * Unit tests for OperationAnswer.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller\Support
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

namespace OCA\Filinq\Tests\Unit\Controller\Support;


use OCA\Filinq\Controller\Support\OperationAnswer;
use OCP\AppFramework\Http\JSONResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * A null outcome is a 404, 400/409 failures carry their message, anything
 * else is logged and answered as a 500 with the fixed failure text.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Controller\Support\OperationAnswer
 */
class OperationAnswerTest extends TestCase {

	/**
	 * The logger double.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * The answer under test.
	 *
	 * @var OperationAnswer
	 */
	private OperationAnswer $answers;

	/**
	 * Build the answer with a fixed failure and not-found text.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->logger  = $this->createMock(LoggerInterface::class);
		$this->answers = new OperationAnswer(logger: $this->logger, failure: 'Envelope request failed', notFound: 'Envelope not found');

	}//end setUp()

	/**
	 * A result is answered as-is under the default 200.
	 *
	 * @return void
	 */
	public function testAResultIsAnsweredWithTheDefaultStatus(): void {
		$this->logger->expects($this->never())->method('error');

		$response = $this->answers->answer(operation: static fn (): array => ['id' => 'e-1', 'status' => 'draft']);

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['id' => 'e-1', 'status' => 'draft'], $response->getData());

	}//end testAResultIsAnsweredWithTheDefaultStatus()

	/**
	 * The caller's success status is used, also for an empty result.
	 *
	 * @return void
	 */
	public function testTheGivenSuccessStatusIsUsed(): void {
		$response = $this->answers->answer(operation: static fn (): array => [], status: 201);

		$this->assertSame(201, $response->getStatus());
		$this->assertSame([], $response->getData());

	}//end testTheGivenSuccessStatusIsUsed()

	/**
	 * A null outcome answers 404 with the not-found text, whatever the success status.
	 *
	 * @return void
	 */
	public function testANullOutcomeIsNotFound(): void {
		$this->logger->expects($this->never())->method('error');

		$response = $this->answers->answer(operation: static fn (): ?array => null, status: 201);

		$this->assertSame(404, $response->getStatus());
		$this->assertSame(['error' => 'Envelope not found'], $response->getData());

	}//end testANullOutcomeIsNotFound()

	/**
	 * A 400 and a 409 failure answer their own code and message, unlogged.
	 *
	 * @return void
	 */
	public function testClientFailuresCarryTheirMessage(): void {
		$this->logger->expects($this->never())->method('error');

		foreach ([400 => 'Missing documents', 409 => 'Envelope already sent'] as $code => $message) {
			$response = $this->answers->answer(
				operation: static function () use ($message, $code): array {
					throw new RuntimeException($message, $code);
				}
			);

			$this->assertSame($code, $response->getStatus());
			$this->assertSame(['error' => $message], $response->getData());
		}

	}//end testClientFailuresCarryTheirMessage()

	/**
	 * Any other failure is logged with its message and answered as a
	 * 500 that hides the message.
	 *
	 * @return void
	 */
	public function testOtherFailuresAreLoggedAndHidden(): void {
		$failure = new RuntimeException('db password wrong', 404);
		$this->logger->expects($this->once())->method('error')->with(
			'Envelope request failed: db password wrong',
			['exception' => $failure]
		);

		$response = $this->answers->answer(
			operation: static function () use ($failure): array {
				throw $failure;
			}
		);

		$this->assertSame(500, $response->getStatus());
		$this->assertSame(['error' => 'Envelope request failed'], $response->getData());

	}//end testOtherFailuresAreLoggedAndHidden()

	/**
	 * A failure without a code is hidden too.
	 *
	 * @return void
	 */
	public function testAFailureWithoutCodeIsHidden(): void {
		$this->logger->expects($this->once())->method('error');

		$response = $this->answers->answer(
			operation: static function (): array {
				throw new \TypeError('bad');
			}
		);

		$this->assertSame(500, $response->getStatus());
		$this->assertSame(['error' => 'Envelope request failed'], $response->getData());

	}//end testAFailureWithoutCodeIsHidden()
}//end class
