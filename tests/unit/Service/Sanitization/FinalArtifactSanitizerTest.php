<?php

/**
 * Unit tests for FinalArtifactSanitizer.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\Sanitization
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-sanitization/tasks.md#3-4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Sanitization;

use InvalidArgumentException;
use OCA\Filinq\Kernel\Contracts\DocumentSanitizationServiceInterface;
use OCA\Filinq\Service\Sanitization\FinalArtifactSanitizer;
use OCP\Files\File;
use OCP\IUser;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * The delivered artifact of an anonymise run is sanitized, and every failure
 * keeps the anonymised file and answers with a warning instead of an error.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Sanitization
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Service\Sanitization\FinalArtifactSanitizer
 */
class FinalArtifactSanitizerTest extends TestCase {

	/**
	 * A run result as the runner leaves it.
	 *
	 * @return array<string, mixed>
	 */
	private static function runResult(): array {
		return ['anonymizedFileId' => 812201, 'anonymizedFileName' => 'besluit_anonymized.pdf', 'complete' => true];

	}//end runResult()

	/**
	 * A clean pass: the delivered file is sanitized for the acting user, and the derivative and its record are on the result.
	 *
	 * @return void
	 */
	public function testTheDeliveredFileIsSanitizedForTheActingUser(): void {
		$service = $this->createMock(DocumentSanitizationServiceInterface::class);
		$service->expects($this->once())->method('sanitize')
			->with(812201, 'alice', 'anonymisation')
			->willReturn(
				[
					'sanitized' => true,
					'sanitizedFileId' => 812202,
					'sanitizedFileName' => 'besluit_anonymized_sanitized.pdf',
					'report' => ['metadataFieldsScrubbed' => 4],
					'recordId' => 'rec-9',
				]
			);

		$out = (new FinalArtifactSanitizer(sanitizer: $service, logger: new NullLogger()))
			->apply(resultInfo: self::runResult(), context: ['userId' => 'alice']);

		$this->assertSame(
			[
				'sanitized' => true,
				'fileId' => 812201,
				'sanitizedFileId' => 812202,
				'sanitizedFileName' => 'besluit_anonymized_sanitized.pdf',
				'report' => ['metadataFieldsScrubbed' => 4],
				'recordId' => 'rec-9',
			],
			$out['sanitization']
		);
		$this->assertArrayNotHasKey('sanitizationWarning', $out);
		$this->assertSame(812201, $out['anonymizedFileId']);
		$this->assertTrue($out['complete']);

	}//end testTheDeliveredFileIsSanitizedForTheActingUser()

	/**
	 * Without an acting user (batch, folder job) the pass runs as the owner of the delivered file.
	 *
	 * @return void
	 */
	public function testWithoutAnActingUserTheFileOwnerIsUsed(): void {
		$owner = $this->createMock(IUser::class);
		$owner->method('getUID')->willReturn('bob');
		$node = $this->createMock(File::class);
		$node->method('getOwner')->willReturn($owner);

		$service = $this->createMock(DocumentSanitizationServiceInterface::class);
		$service->expects($this->once())->method('sanitize')
			->with(812201, 'bob', 'anonymisation')
			->willReturn(['sanitized' => true, 'sanitizedFileId' => 5, 'sanitizedFileName' => 'x.pdf', 'report' => [], 'recordId' => 'r']);

		$out = (new FinalArtifactSanitizer(sanitizer: $service, logger: new NullLogger()))
			->apply(resultInfo: self::runResult(), context: ['userId' => '', 'anonymisedNode' => $node]);

		$this->assertTrue($out['sanitization']['sanitized']);
		$this->assertSame(5, $out['sanitization']['sanitizedFileId']);

	}//end testWithoutAnActingUserTheFileOwnerIsUsed()

	/**
	 * No user at all: nothing runs, the file is kept and flagged.
	 *
	 * @return void
	 */
	public function testNoUserAtAllWarnsWithoutRunning(): void {
		$service = $this->createMock(DocumentSanitizationServiceInterface::class);
		$service->expects($this->never())->method('sanitize');

		$out = (new FinalArtifactSanitizer(sanitizer: $service, logger: new NullLogger()))
			->apply(resultInfo: self::runResult(), context: ['userId' => '', 'anonymisedNode' => 'not-a-node']);

		$this->assertSame('no_acting_user', $out['sanitizationWarning']['code']);
		$this->assertSame(812201, $out['sanitizationWarning']['unsanitizedFileId']);
		$this->assertFalse($out['sanitization']['sanitized']);

	}//end testNoUserAtAllWarnsWithoutRunning()

	/**
	 * A run that delivered no file has nothing to sanitize: a warning, no call.
	 *
	 * @return void
	 */
	public function testNoDeliveredFileWarnsWithoutRunning(): void {
		$service = $this->createMock(DocumentSanitizationServiceInterface::class);
		$service->expects($this->never())->method('sanitize');

		$out = (new FinalArtifactSanitizer(sanitizer: $service, logger: new NullLogger()))
			->apply(resultInfo: ['anonymizedFileId' => null], context: ['userId' => 'alice']);

		$this->assertSame('no_final_artifact', $out['sanitizationWarning']['code']);
		$this->assertNull($out['sanitization']['unsanitizedFileId']);

	}//end testNoDeliveredFileWarnsWithoutRunning()

	/**
	 * An OpenRegister without the PDF sanitizer skips: the anonymised PDF is kept, flagged, with the skip reason.
	 *
	 * @return void
	 */
	public function testASkippedPassKeepsTheFileWithItsReason(): void {
		$service = $this->createMock(DocumentSanitizationServiceInterface::class);
		$service->method('sanitize')->willReturn(['sanitized' => false, 'sanitizationSkipped' => true, 'reason' => 'pdf_sanitizer_unavailable', 'fileId' => 812201]);

		$out = (new FinalArtifactSanitizer(sanitizer: $service, logger: new NullLogger()))
			->apply(resultInfo: self::runResult(), context: ['userId' => 'alice']);

		$this->assertSame('pdf_sanitizer_unavailable', $out['sanitizationWarning']['code']);
		$this->assertSame(812201, $out['sanitization']['unsanitizedFileId']);
		$this->assertSame(812201, $out['anonymizedFileId']);

	}//end testASkippedPassKeepsTheFileWithItsReason()

	/**
	 * An engine failure (encrypted, internal) is a warning on the result, never an exception out of the run.
	 *
	 * @return void
	 */
	public function testAnEngineFailureIsAWarningNotAnError(): void {
		$service = $this->createMock(DocumentSanitizationServiceInterface::class);
		$service->method('sanitize')->willThrowException(new InvalidArgumentException(message: 'encrypted', code: 422));

		$out = (new FinalArtifactSanitizer(sanitizer: $service, logger: new NullLogger()))
			->apply(resultInfo: self::runResult(), context: ['userId' => 'alice']);

		$this->assertSame('encrypted', $out['sanitizationWarning']['code']);
		$this->assertStringContainsString('kept but not sanitized', $out['sanitizationWarning']['message']);
		$this->assertFalse($out['sanitization']['sanitized']);
		$this->assertSame('besluit_anonymized.pdf', $out['anonymizedFileName']);

	}//end testAnEngineFailureIsAWarningNotAnError()

	/**
	 * A failure without a message still names a code.
	 *
	 * @return void
	 */
	public function testAFailureWithoutAMessageReadsInternal(): void {
		$service = $this->createMock(DocumentSanitizationServiceInterface::class);
		$service->method('sanitize')->willThrowException(new \RuntimeException(''));

		$out = (new FinalArtifactSanitizer(sanitizer: $service, logger: new NullLogger()))
			->apply(resultInfo: self::runResult(), context: ['userId' => 'alice']);

		$this->assertSame('internal', $out['sanitizationWarning']['code']);

	}//end testAFailureWithoutAMessageReadsInternal()
}//end class
