<?php

/**
 * Unit tests for AnonymisationSanitizationRecorder
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Sanitization
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/document-sanitization/tasks.md#3-3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Sanitization;

use OCA\Filinq\Service\Sanitization\AnonymisationSanitizationRecorder;
use OCA\Filinq\Service\Sanitization\SanitizationRecordRepository;
use OCA\OpenRegister\Service\File\SanitizationReport;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The office run's report is kept; a text run fabricates none; a PDF output
 * is never called sanitized; an unmet sanitize request is a warning.
 */
class AnonymisationSanitizationRecorderTest extends TestCase {

	/** @var list<array<string, mixed>> Records written. */
	private array $saved = [];

	/**
	 * The recorder.
	 *
	 * @return AnonymisationSanitizationRecorder
	 */
	private function recorder(): AnonymisationSanitizationRecorder {
		$repo = $this->createMock(SanitizationRecordRepository::class);
		$repo->method('save')->willReturnCallback(
			function (array $record): array {
				$this->saved[] = $record;
				return $record + ['uuid' => 'r1'];
			}
		);

		return new AnonymisationSanitizationRecorder(records: $repo, logger: $this->createMock(LoggerInterface::class));

	}//end recorder()

	/**
	 * A DOCX run keeps OpenRegister's report against the anonymised file.
	 *
	 * @return void
	 */
	public function testAnOfficeRunKeepsItsReport(): void {
		$report = (new SanitizationReport(commentsRemoved: 2, trackedChangesDropped: 1))->jsonSerialize();

		$result = $this->recorder()->record(
			resultInfo: ['anonymizedFileId' => 77, 'anonymizedFileName' => 'besluit_anonymized.docx'],
			context: ['fileId' => 70, 'userId' => 'alice', 'sanitizationReport' => $report]
		);

		$this->assertSame(2, $result['sanitizationReport']['commentsRemoved']);
		$this->assertArrayNotHasKey('sanitizationWarning', $result);
		$this->assertSame(70, $this->saved[0]['fileId']);
		$this->assertSame(77, $this->saved[0]['sanitizedFileId']);
		$this->assertSame('anonymisation', $this->saved[0]['trigger']);
		$this->assertSame($report, $this->saved[0]['report']);

	}//end testAnOfficeRunKeepsItsReport()

	/**
	 * A text run made no report and gets no record.
	 *
	 * @return void
	 */
	public function testATextRunFabricatesNothing(): void {
		$result = $this->recorder()->record(
			resultInfo: ['anonymizedFileId' => 78, 'anonymizedFileName' => 'notitie_anonymized.txt'],
			context: ['fileId' => 71, 'userId' => 'alice', 'sanitizationReport' => null]
		);

		$this->assertSame([], $this->saved);
		$this->assertArrayNotHasKey('sanitizationReport', $result);
		$this->assertArrayNotHasKey('sanitizationWarning', $result, 'no flag asked, no warning');

	}//end testATextRunFabricatesNothing()

	/**
	 * A DOCX delivered as PDF shows the report but no record calls the PDF sanitized; asked to sanitize, it warns.
	 *
	 * @return void
	 */
	public function testAPdfOutputIsNeverCalledSanitized(): void {
		$report = (new SanitizationReport(commentsRemoved: 2))->jsonSerialize();

		$result = $this->recorder()->record(
			resultInfo: ['anonymizedFileId' => 79, 'anonymizedFileName' => 'besluit_anonymized.pdf'],
			context: ['fileId' => 72, 'userId' => 'alice', 'sanitizationReport' => $report, 'sanitize' => true]
		);

		$this->assertSame([], $this->saved);
		$this->assertSame(2, $result['sanitizationReport']['commentsRemoved']);
		$this->assertSame(['reason' => 'pdf_sanitizer_unavailable'], $result['sanitizationWarning']);
		$this->assertSame(79, $result['anonymizedFileId'], 'the anonymised file is kept');

	}//end testAPdfOutputIsNeverCalledSanitized()
}//end class
