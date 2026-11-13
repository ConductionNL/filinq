<?php

/**
 * Unit tests for ServerErrorAnswer and TranslatedErrorAnswer.
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


use Exception;
use OCA\Filinq\Controller\Support\ServerErrorAnswer;
use OCA\Filinq\Controller\Support\TranslatedErrorAnswer;
use OCP\IL10N;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The two logged error answers: the server error echoes the exception
 * text under a 500; the translated one never does and keeps an HTTP code.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Controller\Support\ServerErrorAnswer
 * @covers \OCA\Filinq\Controller\Support\TranslatedErrorAnswer
 */
class ErrorAnswersTest extends TestCase {

	/**
	 * A translator that marks its output and substitutes %s.
	 *
	 * @return IL10N
	 */
	private function l10n(): IL10N {
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(
			static fn (string $text, array $params = []): string => 'T('.vsprintf($text, $params).')'
		);
		return $l10n;

	}//end l10n()

	/**
	 * The server error logs prefix + message and answers the translated
	 * prefix with the message, always as a 500.
	 *
	 * @return void
	 */
	public function testServerErrorLogsAndAnswersA500WithTheMessage(): void {
		$exception = new Exception('disk full', 409);
		$logger    = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('error')->with('Failed to save policy: disk full', ['exception' => $exception]);

		$response = (new ServerErrorAnswer(logger: $logger, l10n: $this->l10n()))->answer(message: 'Failed to save policy: ', exception: $exception);

		$this->assertSame(500, $response->getStatus());
		$this->assertSame(['error' => 'T(Failed to save policy: disk full)'], $response->getData());

	}//end testServerErrorLogsAndAnswersA500WithTheMessage()

	/**
	 * The translated error logs the exception text but answers only the
	 * translated message, under the exception's HTTP code.
	 *
	 * @return void
	 */
	public function testTranslatedErrorHidesTheExceptionAndKeepsItsCode(): void {
		$exception = new Exception('secret detail', 422);
		$logger    = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('error')->with('Extraction failed: secret detail', ['exception' => $exception]);

		$response = (new TranslatedErrorAnswer(logger: $logger, l10n: $this->l10n()))->answer(message: 'Extraction failed', exception: $exception);

		$this->assertSame(422, $response->getStatus());
		$this->assertSame(['error' => 'T(Extraction failed)'], $response->getData());

	}//end testTranslatedErrorHidesTheExceptionAndKeepsItsCode()

	/**
	 * Codes outside 400-599 answer 500; the range bounds are inclusive at
	 * 400 and 599.
	 *
	 * @return void
	 */
	public function testTranslatedErrorClampsTheCode(): void {
		$answers = new TranslatedErrorAnswer(logger: $this->createMock(LoggerInterface::class), l10n: $this->l10n());

		foreach ([0 => 500, 200 => 500, 399 => 500, 400 => 400, 599 => 599, 600 => 500] as $code => $status) {
			$response = $answers->answer(message: 'Failed', exception: new Exception('x', $code));
			$this->assertSame($status, $response->getStatus(), 'code '.$code);
		}

	}//end testTranslatedErrorClampsTheCode()
}//end class
