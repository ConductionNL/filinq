<?php

/**
 * Unit tests for DocumentReviewRefusedException
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Exception
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-4.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Exception;

use OCA\Filinq\Exception\DocumentReviewRefusedException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * A refused checked mark carries its reason, its message and what it is about.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#requirement-per-document-checked-gate-req-ddarw-007
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[CoversClass(DocumentReviewRefusedException::class)]
class DocumentReviewRefusedExceptionTest extends TestCase {

	/**
	 * The reason and the message are kept; without details the details are empty.
	 *
	 * @return void
	 */
	public function testTheReasonAndMessageAreKeptAndDetailsDefaultToEmpty(): void {
		$e = new DocumentReviewRefusedException(reason: 'not_extracted', message: 'Run the detection first.');

		$this->assertInstanceOf(expected: RuntimeException::class, actual: $e);
		$this->assertSame(expected: 'not_extracted', actual: $e->getReason());
		$this->assertSame(expected: 'Run the detection first.', actual: $e->getMessage());
		$this->assertSame(expected: [], actual: $e->getDetails());

	}//end testTheReasonAndMessageAreKeptAndDetailsDefaultToEmpty()

	/**
	 * The details name what the refusal is about, such as the undecided uncertain findings.
	 *
	 * @return void
	 */
	public function testTheDetailsNameWhatTheRefusalIsAbout(): void {
		$details = ['undecided' => [['value' => 'Jan Jansen', 'score' => 0.65]]];
		$e       = new DocumentReviewRefusedException(reason: 'undecided_uncertain', message: 'Decide the uncertain findings.', details: $details);

		$this->assertSame(expected: 'undecided_uncertain', actual: $e->getReason());
		$this->assertSame(expected: $details, actual: $e->getDetails());

	}//end testTheDetailsNameWhatTheRefusalIsAbout()
}//end class
