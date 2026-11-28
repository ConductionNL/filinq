<?php

/**
 * Unit tests for ProfileExtractionService
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-24-extraction-cv-profile/specs/cv-profile-extraction/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use DateTimeImmutable;
use OCA\Filinq\Service\DocumentTextSource;
use OCA\Filinq\Service\Extraction\CvProfileParser;
use OCA\Filinq\Service\ProfileExtractionService;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ProfileExtractionService.
 */
class ProfileExtractionServiceTest extends TestCase {

	/**
	 * The service over a text source that answers `$text` for file 42.
	 *
	 * @param string $text The file's text.
	 *
	 * @return ProfileExtractionService
	 */
	private function service(string $text): ProfileExtractionService {
		$source = $this->createMock(originalClassName: DocumentTextSource::class);
		$source->method('resolveText')->willReturnCallback(
			static function (?int $fileId) use ($text): array {
				if ($fileId !== 42) {
					return ['text' => '', 'ocrConfidence' => 0.0];
				}

				return ['text' => $text, 'ocrConfidence' => 1.0];
			}
		);
		$time = $this->createMock(originalClassName: ITimeFactory::class);
		$time->method('now')->willReturn(new DateTimeImmutable('2026-11-15'));

		return new ProfileExtractionService(textSource: $source, parser: new CvProfileParser(), time: $time);
	}//end service()

	/**
	 * A readable CV answers its profile.
	 *
	 * @return void
	 */
	public function testAReadableCvAnswersItsProfile(): void {
		$text = "Opleiding\nMaster Bedrijfskunde\n\nWerkervaring\n2018 - 2021 Acme\n2021 - heden Beta\nloonheffing\n";

		$request = ['fileId' => '42', 'competenceCodes' => ['loonheffing', 'excel', 3]];

		$answer = $this->service(text: $text)->extractProfile(data: $request, requestedBy: 'hr');

		$this->assertSame(expected: 'extracted', actual: $answer['status']);
		$this->assertSame(expected: 7, actual: $answer['educationLevel']);
		$this->assertSame(expected: 8.8, actual: $answer['experienceYears']);
		$this->assertSame(expected: ['loonheffing'], actual: $answer['competenceCodes']);
	}//end testAReadableCvAnswersItsProfile()

	/**
	 * A documentUri ending in the file id is read the same way.
	 *
	 * @return void
	 */
	public function testADocumentUriNamesTheFile(): void {
		$answer = $this->service(text: 'HBO Personeel en Arbeid')->extractProfile(data: ['documentUri' => 'nc://file/42'], requestedBy: 'hr');

		$this->assertSame(expected: 'extracted', actual: $answer['status']);
		$this->assertSame(expected: 6, actual: $answer['educationLevel']);
		$this->assertNull(actual: $answer['experienceYears']);
		$this->assertSame(expected: [], actual: $answer['competenceCodes']);
	}//end testADocumentUriNamesTheFile()

	/**
	 * Without a file reference there is nothing to read.
	 *
	 * @return void
	 */
	public function testAMissingFileReferenceIsUnreadable(): void {
		$answer = $this->service(text: 'MSc')->extractProfile(data: ['fileId' => 0], requestedBy: 'hr');

		$this->assertSame(expected: 'unreadable', actual: $answer['status']);
		$this->assertSame(expected: 'No CV file was given.', actual: $answer['message']);
		$this->assertNull(actual: $answer['educationLevel']);
	}//end testAMissingFileReferenceIsUnreadable()

	/**
	 * REQ-CVP-002: a file without text is unreadable and carries no profile.
	 *
	 * @return void
	 */
	public function testAFileWithoutTextIsUnreadable(): void {
		$answer = $this->service(text: '   ')->extractProfile(data: ['fileId' => 42, 'competenceCodes' => ['excel']], requestedBy: 'hr');

		$this->assertSame(expected: 'unreadable', actual: $answer['status']);
		$this->assertStringContainsString(needle: 'could not be read', haystack: $answer['message']);
		$this->assertSame(expected: [], actual: $answer['competenceCodes']);
		$this->assertNull(actual: $answer['experienceYears']);
	}//end testAFileWithoutTextIsUnreadable()
}//end class
