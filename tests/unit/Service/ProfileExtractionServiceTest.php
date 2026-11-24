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
 * @spec openspec/changes/extraction-cv-profile/specs/cv-profile-extraction/spec.md
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
		$source = $this->createMock(DocumentTextSource::class);
		$source->method('resolveText')->willReturnCallback(static fn (?int $fileId): array => ['text' => ($fileId === 42 ? $text : ''), 'ocrConfidence' => 1.0]);
		$time = $this->createMock(ITimeFactory::class);
		$time->method('now')->willReturn(new DateTimeImmutable('2026-11-15'));

		return new ProfileExtractionService($source, new CvProfileParser(), $time);
	}//end service()

	/**
	 * A readable CV answers its profile.
	 *
	 * @return void
	 */
	public function testAReadableCvAnswersItsProfile(): void {
		$text = "Opleiding\nMaster Bedrijfskunde\n\nWerkervaring\n2018 - 2021 Acme\n2021 - heden Beta\nloonheffing\n";

		$answer = $this->service($text)->extractProfile(['fileId' => '42', 'competenceCodes' => ['loonheffing', 'excel', 3]], 'hr');

		$this->assertSame('extracted', $answer['status']);
		$this->assertSame(7, $answer['educationLevel']);
		$this->assertSame(8.8, $answer['experienceYears']);
		$this->assertSame(['loonheffing'], $answer['competenceCodes']);
	}//end testAReadableCvAnswersItsProfile()

	/**
	 * A documentUri ending in the file id is read the same way.
	 *
	 * @return void
	 */
	public function testADocumentUriNamesTheFile(): void {
		$answer = $this->service('HBO Personeel en Arbeid')->extractProfile(['documentUri' => 'nc://file/42'], 'hr');

		$this->assertSame('extracted', $answer['status']);
		$this->assertSame(6, $answer['educationLevel']);
		$this->assertNull($answer['experienceYears']);
		$this->assertSame([], $answer['competenceCodes']);
	}//end testADocumentUriNamesTheFile()

	/**
	 * Without a file reference there is nothing to read.
	 *
	 * @return void
	 */
	public function testAMissingFileReferenceIsUnreadable(): void {
		$answer = $this->service('MSc')->extractProfile(['fileId' => 0], 'hr');

		$this->assertSame('unreadable', $answer['status']);
		$this->assertSame('No CV file was given.', $answer['message']);
		$this->assertNull($answer['educationLevel']);
	}//end testAMissingFileReferenceIsUnreadable()

	/**
	 * REQ-CVP-002: a file without text is unreadable and carries no profile.
	 *
	 * @return void
	 */
	public function testAFileWithoutTextIsUnreadable(): void {
		$answer = $this->service('   ')->extractProfile(['fileId' => 42, 'competenceCodes' => ['excel']], 'hr');

		$this->assertSame('unreadable', $answer['status']);
		$this->assertStringContainsString('could not be read', $answer['message']);
		$this->assertSame([], $answer['competenceCodes']);
		$this->assertNull($answer['experienceYears']);
	}//end testAFileWithoutTextIsUnreadable()
}//end class
