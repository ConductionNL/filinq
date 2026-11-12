<?php

/**
 * Unit tests for how the case-documents and document-production controllers
 * answer a domain call
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

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\CaseDocumentsController;
use OCA\Filinq\Controller\DocumentProductionController;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use TypeError;

/**
 * Pins the answer of a domain call for a signed-in caller: the handler's
 * result as a 200, any failure as a 400 carrying its message, whatever its code.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class HandlerAnswerCharacterisationTest extends TestCase {

	/**
	 * Both controllers and their answering method.
	 *
	 * @return array<string,array{0:array{0:class-string,1:string}}>
	 */
	public static function controllers(): array {
		return [
			'case documents'      => [[CaseDocumentsController::class, 'domainCall']],
			'document production' => [[DocumentProductionController::class, 'answer']],
		];

	}//end controllers()

	/**
	 * The handler's result is answered as a 200.
	 *
	 * @param array{0:class-string,1:string} $target The controller and its method.
	 *
	 * @return void
	 */
	#[DataProvider('controllers')]
	public function testResultIsAnswered200(array $target): void {
		$response = $this->answer($target, static fn (): array => ['results' => [1]]);

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['results' => [1]], $response->getData());

	}//end testResultIsAnswered200()

	/**
	 * An exception or an engine error is answered as a 400 with its message.
	 *
	 * @param array{0:class-string,1:string} $target The controller and its method.
	 *
	 * @return void
	 */
	#[DataProvider('controllers')]
	public function testFailureIsAnswered400(array $target): void {
		foreach ([new RuntimeException('no such layout', 404), new TypeError('bad field')] as $failure) {
			$response = $this->answer($target, static function () use ($failure): array {
				throw $failure;
			});

			$this->assertSame(400, $response->getStatus());
			$this->assertSame(['error' => $failure->getMessage()], $response->getData());
		}

	}//end testFailureIsAnswered400()

	/**
	 * Call the controller's private answering method for a signed-in caller.
	 *
	 * @param array{0:class-string,1:string} $target  The controller and its method.
	 * @param callable                       $handler The domain call.
	 *
	 * @return mixed The answer.
	 */
	private function answer(array $target, callable $handler): mixed {
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($this->createMock(IUser::class));
		$reflection = new ReflectionClass($target[0]);
		$controller = $reflection->newInstanceWithoutConstructor();
		(function (IUserSession $session): void {
			$this->userSession = $session;
		})->call($controller, $session);

		return $reflection->getMethod($target[1])->invoke($controller, $handler);

	}//end answer()

}//end class
