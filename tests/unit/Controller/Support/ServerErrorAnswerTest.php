<?php

/**
 * ServerErrorAnswer: a server failure is a 500, a record OpenRegister refused is a 400
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
use OCA\OpenRegister\Exception\ValidationException;
use OCP\IL10N;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Found live: creating a publish-never rule with an entity type outside the
 * schema's list answered 500 with OpenRegister's own reason.
 *
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-4.1
 */
class ServerErrorAnswerTest extends TestCase {


	/**
	 * A record OpenRegister refused against its schema is answered 400 with the reason, and logged below error.
	 *
	 * @return void
	 */
	public function testARefusedRecordIsA400(): void {
		$logger = $this->createMock(originalClassName: LoggerInterface::class);
		$logger->expects($this->never())->method('error');
		$logger->expects($this->once())->method('info');

		$answer = $this->answerer(logger: $logger)->answer(
			message: 'Failed to create prohibition: ',
			exception: new ValidationException("Property 'entityType' should be one of: 'PERSON', 'ORGANIZATION', 'OTHER'")
		);

		$this->assertSame(expected: 400, actual: $answer->getStatus());
		$this->assertStringContainsString(needle: "Property 'entityType'", haystack: $answer->getData()['error']);

	}//end testARefusedRecordIsA400()


	/**
	 * Anything else stays a logged 500.
	 *
	 * @return void
	 */
	public function testAnyOtherFailureIsA500(): void {
		$logger = $this->createMock(originalClassName: LoggerInterface::class);
		$logger->expects($this->once())->method('error');

		$answer = $this->answerer(logger: $logger)->answer(message: 'Failed: ', exception: new Exception('database gone'));

		$this->assertSame(expected: 500, actual: $answer->getStatus());

	}//end testAnyOtherFailureIsA500()


	/**
	 * The answerer with a pass-through translator.
	 *
	 * @param LoggerInterface $logger The logger.
	 *
	 * @return ServerErrorAnswer
	 */
	private function answerer(LoggerInterface $logger): ServerErrorAnswer {
		$l10n = $this->createMock(originalClassName: IL10N::class);
		$l10n->method('t')->willReturnCallback(
			static fn (string $text, array $parameters=[]): string => vsprintf($text, $parameters)
		);

		return new ServerErrorAnswer(logger: $logger, l10n: $l10n);

	}//end answerer()
}//end class
