<?php

/**
 * Unit tests for EmployeeDocumentExtractionService
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
 * @spec openspec/changes/archive/2026-11-24-extraction-employee-document/specs/employee-document-extraction/spec.md#REQ-EDE-003
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use DateTimeImmutable;
use OCA\Filinq\Service\Contract\ContractTermExtractor;
use OCA\Filinq\Service\DocumentTextSource;
use OCA\Filinq\Service\EmployeeDocumentExtractionService;
use OCA\Filinq\Service\Extraction\EmploymentContractParser;
use OCA\Filinq\Service\Extraction\MrzParser;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\TestCase;

/**
 * Tests for EmployeeDocumentExtractionService.
 */
class EmployeeDocumentExtractionServiceTest extends TestCase {

	/**
	 * The service over a text source that answers `$text` for file 42.
	 *
	 * @param string $text The file's text.
	 *
	 * @return EmployeeDocumentExtractionService
	 */
	private function service(string $text): EmployeeDocumentExtractionService {
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

		$contract = new EmploymentContractParser(terms: new ContractTermExtractor());

		return new EmployeeDocumentExtractionService(textSource: $source, mrz: new MrzParser(), contract: $contract, time: $time);
	}//end service()

	/**
	 * An identity document is read from its zone.
	 *
	 * @return void
	 */
	public function testAnIdentityDocumentIsRead(): void {
		$text = "P<UTOERIKSSON<<ANNA<MARIA<<<<<<<<<<<<<<<<<<<\nL898902C36UTO7408122F1204159ZE184226B<<<<<10";
		$request = ['fileId' => 42, 'docType' => 'identity-document'];

		$answer = $this->service(text: $text)->extractEmployeeDocument(data: $request, requestedBy: 'hr');

		$this->assertSame(expected: 'extracted', actual: $answer['status']);
		$this->assertSame(expected: 'ERIKSSON', actual: $answer['fields']['lastName']['value']);
		$this->assertSame(expected: 0.95, actual: $answer['fields']['dateOfBirth']['confidence']);
	}//end testAnIdentityDocumentIsRead()

	/**
	 * A contract is read into its terms.
	 *
	 * @return void
	 */
	public function testAContractIsRead(): void {
		$request = ['fileId' => '42', 'docType' => 'employment-contract'];

		$answer = $this->service(text: "Startdatum: 01-05-2026\n24 uur per week")->extractEmployeeDocument(data: $request, requestedBy: 'hr');

		$this->assertSame(expected: 'extracted', actual: $answer['status']);
		$this->assertSame(expected: '2026-05-01', actual: $answer['fields']['startDate']['value']);
		$this->assertSame(expected: 24.0, actual: $answer['fields']['hoursPerWeek']['value']);
	}//end testAContractIsRead()

	/**
	 * A contract read as an identity document has no zone: nothing is read.
	 *
	 * @return void
	 */
	public function testAWrongTypeReadsNothing(): void {
		$request = ['fileId' => 42, 'docType' => 'identity-document'];

		$answer = $this->service(text: "Startdatum: 01-05-2026\n24 uur per week")->extractEmployeeDocument(data: $request, requestedBy: 'hr');

		$this->assertSame(expected: 'unreadable', actual: $answer['status']);
		$this->assertSame(expected: [], actual: $answer['fields']);
	}//end testAWrongTypeReadsNothing()

	/**
	 * Another document type is refused.
	 *
	 * @return void
	 */
	public function testAnUnknownTypeIsRefused(): void {
		$answer = $this->service(text: 'x')->extractEmployeeDocument(data: ['fileId' => 42, 'docType' => 'receipt'], requestedBy: 'hr');

		$this->assertSame(expected: 'refused', actual: $answer['status']);
	}//end testAnUnknownTypeIsRefused()

	/**
	 * REQ-EDE-003: a file without text is unreadable.
	 *
	 * @return void
	 */
	public function testAFileWithoutTextIsUnreadable(): void {
		$answer = $this->service(text: '')->extractEmployeeDocument(data: ['fileId' => 7, 'docType' => 'employment-contract'], requestedBy: 'hr');

		$this->assertSame(expected: 'unreadable', actual: $answer['status']);
		$this->assertStringContainsString(needle: 'could not be read', haystack: $answer['message']);
	}//end testAFileWithoutTextIsUnreadable()
}//end class
