<?php

/**
 * Unit tests for the AnswersIntakeRefusals controller trait.
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

use OCA\Filinq\Controller\AnswersIntakeRefusals;
use OCA\Filinq\Exception\IntakeRefusedException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Controller\AnswersIntakeRefusals
 * @uses   \OCA\Filinq\Exception\IntakeRefusedException
 */
class AnswersIntakeRefusalsTest extends TestCase {

	/**
	 * A host exposing the trait's private answers.
	 *
	 * @return object
	 */
	private function host(): object {
		return new class() {
			use AnswersIntakeRefusals {
				badRequest as public;
				failure as public;
			}
		};

	}//end host()

	public function testBadRequestIsABare400(): void {
		$response = $this->host()->badRequest();

		$this->assertNotInstanceOf(JSONResponse::class, $response);
		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());

	}//end testBadRequestIsABare400()

	public function testARefusalAnswersWithItsOwnStatus(): void {
		$response = $this->host()->failure(new IntakeRefusedException('Not your intake.', 404));

		$this->assertSame(404, $response->getStatus());
		$this->assertSame(['error' => 'Not your intake.'], $response->getData());

	}//end testARefusalAnswersWithItsOwnStatus()

	public function testARefusalDefaultsTo403(): void {
		$response = $this->host()->failure(new IntakeRefusedException('Forbidden.'));

		$this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());

	}//end testARefusalDefaultsTo403()

	public function testAnyOtherErrorIsA500CarryingItsMessage(): void {
		$response = $this->host()->failure(new RuntimeException('database gone', 404));

		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
		$this->assertSame(['error' => 'database gone'], $response->getData());

	}//end testAnyOtherErrorIsA500CarryingItsMessage()
}//end class
