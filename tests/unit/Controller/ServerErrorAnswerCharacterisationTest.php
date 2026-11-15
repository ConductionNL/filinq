<?php

/**
 * Unit tests for the 500 answer of the dictionary, policy and standing consent controllers
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

use Exception;
use OCA\Filinq\Controller\CustomDictionaryController;
use OCA\Filinq\Controller\PolicyController;
use OCA\Filinq\Controller\StandingConsentController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * Pins what error() logs and answers on each controller that maps a caught
 * exception to a 500.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ServerErrorAnswerCharacterisationTest extends TestCase {

	/**
	 * The controllers with the 500 mapper.
	 *
	 * @return array<string,array{0:class-string}>
	 */
	public static function controllers(): array {
		return [
			'dictionary'       => [CustomDictionaryController::class],
			'policy'           => [PolicyController::class],
			'standing consent' => [StandingConsentController::class],
		];

	}//end controllers()

	/**
	 * The exception message is logged after the prefix, with the exception
	 * as context, and answered as a translated 500 envelope.
	 *
	 * @param class-string $class The controller under test.
	 *
	 * @return void
	 */
	#[DataProvider('controllers')]
	public function testErrorLogsAndAnswers500(string $class): void {
		$exception = new Exception('disk full');

		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('error')
			->with('Failed to save: disk full', ['exception' => $exception]);

		$l10n = $this->createMock(IL10N::class);
		$l10n->expects($this->once())->method('t')
			->with('Failed to save: %s', ['disk full'])
			->willReturn('Opslaan mislukt: disk full');

		$response = $this->callError($this->controllerWith($class, [$logger, $l10n]), $exception);

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(500, $response->getStatus());
		$this->assertSame(['error' => 'Opslaan mislukt: disk full'], $response->getData());

	}//end testErrorLogsAndAnswers500()

	/**
	 * Build the controller with only its logger and translator wired in.
	 *
	 * @param class-string                     $class The controller under test.
	 * @param array{0:LoggerInterface,1:IL10N} $deps  The logger and the translator.
	 *
	 * @return object The controller.
	 */
	private function controllerWith(string $class, array $deps): object {
		$controller = (new ReflectionClass($class))->newInstanceWithoutConstructor();
		(function (LoggerInterface $logger, IL10N $l10n): void {
			$this->logger = $logger;
			$this->l10n   = $l10n;
		})->call($controller, ...$deps);

		return $controller;

	}//end controllerWith()

	/**
	 * Call the controller's private error() with the 'Failed to save: ' prefix.
	 *
	 * @param object    $controller The controller under test.
	 * @param Exception $exception  The caught exception.
	 *
	 * @return mixed The answer.
	 */
	private function callError(object $controller, Exception $exception): mixed {
		return (function (Exception $exception): mixed {
			return $this->error(message: 'Failed to save: ', exception: $exception);
		})->call($controller, $exception);

	}//end callError()

}//end class
