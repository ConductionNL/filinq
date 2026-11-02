<?php

/**
 * Unit tests for DocumentSanitizationService
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
 * @spec openspec/changes/document-sanitization/tasks.md#3-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Sanitization;

use InvalidArgumentException;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCA\Filinq\Service\Sanitization\DocumentSanitizationService;
use OCA\Filinq\Service\Sanitization\SanitizationRecordRepository;
use OCA\OpenRegister\Exception\SanitizationException;
use OCA\OpenRegister\Service\File\OfficeDocumentSanitizer;
use OCA\OpenRegister\Service\File\SanitizationReport;
use OCA\OpenRegister\Service\File\SanitizationResult;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Office files are sanitised by OpenRegister into a derivative beside the
 * source; PDFs are refused fail-flagged until OpenRegister has a PDF seam;
 * every run keeps a content-free record, validated against the real schema.
 */
class DocumentSanitizationServiceTest extends TestCase {

	/** @var array<int, array<string, mixed>> Records written. */
	private array $records = [];

	/** @var array<int, array{0: string, 1: string}> Files created beside the source, [name, content]. */
	private array $created = [];

	/** @var OfficeDocumentSanitizer&\PHPUnit\Framework\MockObject\MockObject */
	private $office;

	/** @var array<int, File> Files the user can read, by id. */
	private array $files = [];

	/**
	 * A file in the user's folder.
	 *
	 * @param int    $id   The id.
	 * @param string $name The name.
	 * @param string $mime The MIME type.
	 *
	 * @return void
	 */
	private function file(int $id, string $name, string $mime): void {
		$parent = $this->createMock(Folder::class);
		$parent->method('getNonExistingName')->willReturnArgument(0);
		$parent->method('newFile')->willReturnCallback(
			function (string $path, mixed $content=null): File {
				$this->created[] = [$path, is_resource($content) === true ? stream_get_contents($content) : (string) $content];
				$new = $this->createMock(File::class);
				$new->method('getId')->willReturn(900 + count($this->created));
				$new->method('getName')->willReturn($path);
				return $new;
			}
		);
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn($id);
		$file->method('getName')->willReturn($name);
		$file->method('getMimeType')->willReturn($mime);
		$file->method('getParent')->willReturn($parent);
		$file->expects($this->never())->method('putContent');
		$this->files[$id] = $file;

	}//end file()

	/**
	 * The service, with OpenRegister's office sanitizer and no PDF seam.
	 *
	 * @return DocumentSanitizationService
	 */
	private function service(): DocumentSanitizationService {
		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getById')->willReturnCallback(fn (int $id): array => isset($this->files[$id]) === true ? [$this->files[$id]] : []);
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->willReturn($userFolder);

		$this->office = $this->createMock(OfficeDocumentSanitizer::class);
		$this->office->method('isSanitizable')->willReturnCallback(fn (string $mime): bool => str_contains($mime, 'wordprocessingml') || str_contains($mime, 'opendocument.text'));
		$locator = $this->createMock(OpenRegisterServiceLocator::class);
		$locator->method('get')->willReturnCallback(
			fn (string $class): mixed => $class === OfficeDocumentSanitizer::class ? $this->office : throw new \RuntimeException($class . ' is not available.')
		);
		$records = $this->createMock(SanitizationRecordRepository::class);
		$records->method('save')->willReturnCallback(
			function (array $record): array {
				$this->records[] = $record;
				return $record + ['uuid' => 'rec-' . count($this->records)];
			}
		);

		return new DocumentSanitizationService(
			rootFolder: $root,
			locator: $locator,
			records: $records,
			logger: $this->createMock(LoggerInterface::class)
		);

	}//end service()

	/**
	 * Validate a record against the real sanitizationRecord fragment.
	 *
	 * @param array<string, mixed> $record The record.
	 *
	 * @return void
	 */
	private function assertValidRecord(array $record): void {
		$register = json_decode((string) file_get_contents(__DIR__ . '/../../../../lib/Settings/filinq_register.json'));
		$schema = $register->components->schemas->sanitizationRecord;
		$properties = new \stdClass();
		foreach ($schema->properties as $name => $property) {
			$copy = clone $property;
			unset($copy->required);
			$properties->{$name} = $copy;
		}

		$json = (object) ['type' => 'object', 'required' => $schema->required, 'properties' => $properties, 'additionalProperties' => false];
		$result = (new Validator())->validate(json_decode((string) json_encode($record)), json_encode($json));
		$message = '';
		if ($result->isValid() === false) {
			$message = (string) json_encode((new \Opis\JsonSchema\Errors\ErrorFormatter())->format($result->error()));
		}

		$this->assertTrue($result->isValid(), 'record refused: ' . $message);

	}//end assertValidRecord()

	/**
	 * A DOCX is sanitised into a derivative beside it; the source is untouched; the record holds counts only.
	 *
	 * @return void
	 */
	public function testAnOfficeFileBecomesASanitizedDerivative(): void {
		$this->file(id: 41, name: 'Concept besluit Demostad.docx', mime: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
		$service = $this->service();
		$temp = tempnam(sys_get_temp_dir(), 'san');
		file_put_contents($temp, 'CLEAN BYTES');
		$this->office->expects($this->once())->method('sanitize')->with(41)->willReturn(
			new SanitizationResult(path: $temp, report: new SanitizationReport(commentsRemoved: 4, metadataFieldsScrubbed: 6))
		);

		$result = $service->sanitize(fileId: 41, userId: 'alice');

		$this->assertTrue($result['sanitized']);
		$this->assertSame([['Concept besluit Demostad_sanitized.docx', 'CLEAN BYTES']], $this->created);
		$this->assertSame(901, $result['sanitizedFileId']);
		$this->assertSame(4, $result['report']['commentsRemoved']);
		$this->assertFileDoesNotExist($temp, 'the temporary file is removed');
		$record = $this->records[0];
		$this->assertSame(41, $record['fileId']);
		$this->assertSame(901, $record['sanitizedFileId']);
		$this->assertSame('manual', $record['trigger']);
		$this->assertSame('OfficeDocumentSanitizer', $record['engine']);
		$this->assertSame('alice', $record['sanitizedBy']);
		$this->assertValidRecord($record);

	}//end testAnOfficeFileBecomesASanitizedDerivative()

	/**
	 * The persisted office report is OpenRegister's serialisation, field for field.
	 *
	 * @return void
	 */
	public function testTheReportShapeIsOpenRegisters(): void {
		$this->file(id: 41, name: 'a.docx', mime: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
		$service = $this->service();
		$temp = tempnam(sys_get_temp_dir(), 'san');
		$this->office->method('sanitize')->willReturn(new SanitizationResult(path: $temp, report: new SanitizationReport()));

		$service->sanitize(fileId: 41, userId: 'alice');

		$this->assertSame(array_keys((new SanitizationReport())->jsonSerialize()), array_keys($this->records[0]['report']));
		$register = json_decode((string) file_get_contents(__DIR__ . '/../../../../lib/Settings/filinq_register.json'), true);
		$declared = array_keys($register['components']['schemas']['sanitizationRecord']['properties']['report']['properties']);
		foreach (array_keys((new SanitizationReport())->jsonSerialize()) as $field) {
			$this->assertContains($field, $declared, 'the schema declares every field OpenRegister reports');
		}

	}//end testTheReportShapeIsOpenRegisters()

	/**
	 * A PDF without OpenRegister's PDF seam is skipped fail-flagged: no file, no record.
	 *
	 * @return void
	 */
	public function testAPdfIsSkippedWithoutThePdfSeam(): void {
		$this->file(id: 42, name: 'besluit.pdf', mime: 'application/pdf');
		$service = $this->service();
		$this->office->expects($this->never())->method('sanitize');

		$result = $service->sanitize(fileId: 42, userId: 'alice');

		$this->assertFalse($result['sanitized']);
		$this->assertTrue($result['sanitizationSkipped']);
		$this->assertSame('pdf_sanitizer_unavailable', $result['reason']);
		$this->assertSame([], $this->created);
		$this->assertSame([], $this->records);

	}//end testAPdfIsSkippedWithoutThePdfSeam()

	/**
	 * An encrypted document is 422 with the engine's reason; nothing is written.
	 *
	 * @return void
	 */
	public function testAnEncryptedDocumentFailsClosed(): void {
		$this->file(id: 43, name: 'geheim.docx', mime: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
		$service = $this->service();
		$this->office->method('sanitize')->willThrowException(new SanitizationException(reason: SanitizationException::REASON_ENCRYPTED));

		try {
			$service->sanitize(fileId: 43, userId: 'alice');
			$this->fail('an encrypted document was not refused');
		} catch (InvalidArgumentException $e) {
			$this->assertSame(422, $e->getCode());
			$this->assertSame('encrypted', $e->getMessage());
		}

		$this->assertSame([], $this->created);
		$this->assertSame([], $this->records);

	}//end testAnEncryptedDocumentFailsClosed()

	/**
	 * A file outside the user's folder is 404; a format nothing sanitises is 415.
	 *
	 * @return void
	 */
	public function testUnreachableAndUnsupportedFiles(): void {
		$this->file(id: 44, name: 'foto.png', mime: 'image/png');
		$service = $this->service();

		foreach ([[999, 404], [44, 415]] as [$fileId, $status]) {
			try {
				$service->sanitize(fileId: $fileId, userId: 'alice');
				$this->fail('not refused: ' . $fileId);
			} catch (InvalidArgumentException $e) {
				$this->assertSame($status, $e->getCode());
			}
		}

	}//end testUnreachableAndUnsupportedFiles()
}//end class
