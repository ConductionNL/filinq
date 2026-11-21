<?php

/**
 * Unit tests for HandlerAnswer.
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


use OCA\Filinq\Controller\Support\HandlerAnswer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * A domain result is a 200; any failure is a 400 with its message.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Controller\Support\HandlerAnswer
 */
class HandlerAnswerTest extends TestCase {

	/**
	 * The handler's result is answered as a 200.
	 *
	 * @return void
	 */
	public function testTheResultIsAnswered(): void {
		$response = (new HandlerAnswer())->answer(handler: static fn (): array => ['documents' => [1, 2]]);

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['documents' => [1, 2]], $response->getData());

	}//end testTheResultIsAnswered()

	/**
	 * Any failure, whatever its code, is a 400 carrying its message.
	 *
	 * @return void
	 */
	public function testAnyFailureIsABadRequestWithItsMessage(): void {
		foreach ([new RuntimeException('Case not found', 404), new RuntimeException('boom', 500), new \Error('fatal')] as $failure) {
			$response = (new HandlerAnswer())->answer(
				handler: static function () use ($failure): array {
					throw $failure;
				}
			);

			$this->assertSame(400, $response->getStatus());
			$this->assertSame(['error' => $failure->getMessage()], $response->getData());
		}

	}//end testAnyFailureIsABadRequestWithItsMessage()
}//end class
