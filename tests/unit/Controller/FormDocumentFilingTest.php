<?php

/**
 * Unit tests for FormDocumentFiling
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-24-forms-as-documents/specs/document-creatie-sjablonen/spec.md#requirement-a-file-is-a-value-a-field-can-hold-and-a-form-becomes-a-document-req-dft-05
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\FormDocumentFiling;
use OCA\Filinq\Controller\FormDocumentRefused;
use OCA\Filinq\Kernel\Contracts\DocumentServiceInterface;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * A document reference is checked as the caller reads it, and a submitted form is filed from its values.
 *
 * @covers \OCA\Filinq\Controller\FormDocumentFiling
 */
class FormDocumentFilingTest extends TestCase {

	/**
	 * Records the caller may read, by id.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $readable = [];

	/**
	 * The files stored through the double of OpenRegister's FileService.
	 *
	 * @var list<array{objectEntity: string, fileName: string, content: string}>
	 */
	private array $filed = [];

	/**
	 * Whether storing a file fails.
	 *
	 * @var bool
	 */
	private bool $fileFails = false;

	/**
	 * The template render.
	 *
	 * @var DocumentServiceInterface&MockObject
	 */
	private DocumentServiceInterface&MockObject $documents;

	/**
	 * The service under test.
	 *
	 * @var FormDocumentFiling
	 */
	private FormDocumentFiling $service;

	/**
	 * Wire the service over doubles of OpenRegister and the render.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$objects = $this->getMockBuilder(className: ObjectService::class)->onlyMethods(['find'])->getMock();
		$objects->method('find')->willReturnCallback(
			fn (string $id = '') => ($this->readable[$id] ?? null)
		);
		$resolver = $this->createMock(originalClassName: DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);

		$test = $this;
		$files = new class($test) {
			/**
			 * Constructor.
			 *
			 * @param FormDocumentFilingTest $test The test that records the files.
			 */
			public function __construct(private readonly FormDocumentFilingTest $test) {
			}

			/**
			 * Store a file on an object.
			 *
			 * @param string $objectEntity The object.
			 * @param string $fileName     The name.
			 * @param string $content      The bytes.
			 *
			 * @return object The stored file.
			 */
			public function addFile(string $objectEntity, string $fileName, string $content): object {
				return $this->test->recordFile(objectEntity: $objectEntity, fileName: $fileName, content: $content);
			}
		};
		$container = $this->createMock(originalClassName: ContainerInterface::class);
		$container->method('get')->willReturn($files);

		$this->documents = $this->createMock(originalClassName: DocumentServiceInterface::class);
		$this->service   = new FormDocumentFiling(
			objects: $resolver,
			container: $container,
			documents: $this->documents,
			logger: $this->createMock(originalClassName: LoggerInterface::class)
		);

	}//end setUp()

	/**
	 * Record a stored file, or fail when the test says so.
	 *
	 * @param string $objectEntity The object.
	 * @param string $fileName     The name.
	 * @param string $content      The bytes.
	 *
	 * @return object A file with an id.
	 */
	public function recordFile(string $objectEntity, string $fileName, string $content): object {
		if ($this->fileFails === true) {
			throw new RuntimeException('disk full');
		}

		$this->filed[] = ['objectEntity' => $objectEntity, 'fileName' => $fileName, 'content' => $content];

		return new class {
			/**
			 * The file id.
			 *
			 * @return int The id.
			 */
			public function getId(): int {
				return 4711;
			}
		};

	}//end recordFile()

	/**
	 * A readable reference answers the reference and its title, defaulting to filinq's document record.
	 *
	 * @return void
	 */
	public function testAReadableReferenceIsTheReferenceNotTheFile(): void {
		$this->readable['doc-1'] = ['title' => 'Overzicht DigiD-aansluitingen', 'content' => 'bytes'];

		$checked = $this->service->checkReference(reference: ['id' => 'doc-1']);

		$this->assertSame(
			expected: ['register' => 'filinq', 'schema' => 'document', 'id' => 'doc-1', 'title' => 'Overzicht DigiD-aansluitingen'],
			actual: $checked
		);
		$this->assertSame(expected: [], actual: $this->filed, message: 'checking a reference stores no file');

	}//end testAReadableReferenceIsTheReferenceNotTheFile()

	/**
	 * A reference the caller may not read answers 404, the same as one that does not exist.
	 *
	 * @return void
	 */
	public function testAnUnreadableReferenceIsRefusedAs404(): void {
		try {
			$this->service->checkReference(reference: ['register' => 'filinq', 'schema' => 'document', 'id' => 'secret']);
			$this->fail(message: 'an unreadable reference must be refused');
		} catch (FormDocumentRefused $refused) {
			$this->assertSame(expected: 404, actual: $refused->getCode());
			$this->assertSame(expected: FormDocumentFiling::REFERENCE_NOT_READABLE, actual: $refused->getMessage());
		}

	}//end testAnUnreadableReferenceIsRefusedAs404()

	/**
	 * A reference without an id is a 400.
	 *
	 * @return void
	 */
	public function testAReferenceWithoutAnIdIsMalformed(): void {
		$this->expectException(exception: FormDocumentRefused::class);
		$this->expectExceptionCode(code: 400);

		$this->service->checkReference(reference: ['register' => 'filinq', 'id' => '  ']);

	}//end testAReferenceWithoutAnIdIsMalformed()

	/**
	 * A submitted form renders from the values as submitted and is filed on the record.
	 *
	 * @return void
	 */
	public function testASubmittedFormIsRenderedFromItsValuesAndFiledOnTheRecord(): void {
		$this->readable['case-1'] = ['title' => 'Omgevingsvergunning', 'applicant' => 'changed later'];
		$this->readable['doc-1']  = ['name' => 'DigiD.pdf'];

		$this->documents->expects($this->once())->method('generateDocument')->with(
			'tpl-aanvraag',
			[],
			[
				'format' => 'pdf',
				'filename' => 'aanvraagformulier.pdf',
				'adHocData' => [
					'form' => ['applicant' => 'J. Jansen', 'evidence' => 'doc-1'],
					'documents' => ['evidence' => ['register' => 'filinq', 'schema' => 'document', 'id' => 'doc-1', 'title' => 'DigiD.pdf']],
					'record' => ['register' => 'dossiq', 'schema' => 'case', 'id' => 'case-1'],
				],
			]
		)->willReturn(['content' => '%PDF-1.7 form', 'sha256' => 'abc', 'templateVersion' => 3, 'warnings' => []]);

		$filed = $this->service->renderSubmission(
			submission: [
				'templateId' => 'tpl-aanvraag',
				'record' => ['register' => 'dossiq', 'schema' => 'case', 'id' => 'case-1'],
				'values' => ['applicant' => 'J. Jansen', 'evidence' => 'doc-1'],
				'documentFields' => ['evidence'],
				'filename' => 'aanvraagformulier.pdf',
			]
		);

		$this->assertSame(
			expected: [['objectEntity' => 'case-1', 'fileName' => 'aanvraagformulier.pdf', 'content' => '%PDF-1.7 form']],
			actual: $this->filed
		);
		$this->assertSame(expected: 4711, actual: $filed['fileId']);
		$this->assertSame(expected: 'abc', actual: $filed['sha256']);
		$this->assertSame(expected: 3, actual: $filed['templateVersion']);
		$this->assertSame(expected: 'doc-1', actual: $filed['references']['evidence']['id']);

	}//end testASubmittedFormIsRenderedFromItsValuesAndFiledOnTheRecord()

	/**
	 * A document field holding a reference the caller may not read stops the render.
	 *
	 * @return void
	 */
	public function testAnUnreadableDocumentFieldStopsTheRender(): void {
		$this->readable['case-1'] = ['title' => 'Case'];
		$this->documents->expects($this->never())->method('generateDocument');

		$this->expectException(exception: FormDocumentRefused::class);
		$this->expectExceptionCode(code: 404);

		$this->service->renderSubmission(
			submission: [
				'templateId' => 'tpl',
				'record' => ['register' => 'dossiq', 'schema' => 'case', 'id' => 'case-1'],
				'values' => ['evidence' => ['id' => 'not-mine']],
				'documentFields' => ['evidence'],
			]
		);

	}//end testAnUnreadableDocumentFieldStopsTheRender()

	/**
	 * A record the caller may not read is refused before anything renders.
	 *
	 * @return void
	 */
	public function testARecordTheCallerMayNotReadIsRefused(): void {
		$this->documents->expects($this->never())->method('generateDocument');

		$this->expectException(exception: FormDocumentRefused::class);
		$this->expectExceptionCode(code: 404);

		$this->service->renderSubmission(
			submission: ['templateId' => 'tpl', 'record' => ['register' => 'dossiq', 'schema' => 'case', 'id' => 'other'], 'values' => []]
		);

	}//end testARecordTheCallerMayNotReadIsRefused()

	/**
	 * A submission without its record or values, or in an unknown format, is a 400.
	 *
	 * @return void
	 */
	public function testAMalformedSubmissionIsRefused(): void {
		$cases = [
			['record' => ['register' => 'r', 'schema' => 's', 'id' => 'i'], 'values' => []],
			['templateId' => 't', 'record' => ['register' => 'r', 'id' => 'i'], 'values' => []],
			['templateId' => 't', 'record' => ['register' => 'r', 'schema' => 's', 'id' => 'i'], 'values' => 'text'],
			['templateId' => 't', 'record' => ['register' => 'r', 'schema' => 's', 'id' => 'i'], 'values' => [], 'format' => 'exe'],
		];
		foreach ($cases as $index => $submission) {
			try {
				$this->service->renderSubmission(submission: $submission);
				$this->fail(message: 'case ' . $index . ' must be refused');
			} catch (FormDocumentRefused $refused) {
				$this->assertSame(expected: 400, actual: $refused->getCode(), message: 'case ' . $index);
			}
		}

	}//end testAMalformedSubmissionIsRefused()

	/**
	 * Without a filename the form gets a generated one with the format's extension; odf files as .odt.
	 *
	 * @return void
	 */
	public function testTheFileNameDefaultsAndCarriesTheFormatsExtension(): void {
		$this->readable['case-1'] = ['title' => 'Case'];
		$this->documents->method('generateDocument')->willReturn(['content' => 'odt bytes']);

		$filed = $this->service->renderSubmission(
			submission: [
				'templateId' => 't',
				'record' => ['register' => 'r', 'schema' => 's', 'id' => 'case-1'],
				'values' => [],
				'format' => 'odf',
				'filename' => 'a/b:c.odt',
			]
		);
		$this->assertSame(expected: 'a-b-c.odt', actual: $filed['fileName']);
		$this->assertSame(expected: hash('sha256', 'odt bytes'), actual: $filed['sha256']);

		$generated = $this->service->renderSubmission(
			submission: ['templateId' => 't', 'record' => ['register' => 'r', 'schema' => 's', 'id' => 'case-1'], 'values' => []]
		);
		$this->assertMatchesRegularExpression(pattern: '/^form-\d{8}-\d{6}\.pdf$/', string: $generated['fileName']);

	}//end testTheFileNameDefaultsAndCarriesTheFormatsExtension()

	/**
	 * A file that cannot be stored on the record is a 502, never a silent success.
	 *
	 * @return void
	 */
	public function testAFilingFailureIsA502(): void {
		$this->readable['case-1'] = ['title' => 'Case'];
		$this->documents->method('generateDocument')->willReturn(['content' => 'x']);
		$this->fileFails = true;

		$this->expectException(exception: FormDocumentRefused::class);
		$this->expectExceptionCode(code: 502);

		$this->service->renderSubmission(
			submission: ['templateId' => 't', 'record' => ['register' => 'r', 'schema' => 's', 'id' => 'case-1'], 'values' => []]
		);

	}//end testAFilingFailureIsA502()
}//end class
