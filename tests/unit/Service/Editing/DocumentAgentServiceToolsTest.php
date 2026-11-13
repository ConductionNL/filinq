<?php

/**
 * Unit tests for the spreadsheet, presentation, chart and metadata tools of DocumentAgentService.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Editing;

use OCA\Filinq\Service\DocumentGenerationRecord;
use OCA\Filinq\Service\Editing\AgentArtefactMarker;
use OCA\Filinq\Service\Editing\AgentFileRequest;
use OCA\Filinq\Service\Editing\DocumentAgentService;
use OCA\Filinq\Service\Editing\EditSessionService;
use OCA\Filinq\Service\GeneratedDocumentLogger;
use OCA\Filinq\Service\PdfConversionService;
use OCA\Filinq\Service\Support\AgentArtefacts;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Each tool runs as the signed-in user, forwards its arguments and records writes.
 *
 * @covers \OCA\Filinq\Service\Editing\DocumentAgentService
 * @uses   \OCA\Filinq\Service\Editing\AgentFileRequest
 * @uses   \OCA\Filinq\Service\Support\AgentArtefacts
 * @uses   \OCA\Filinq\Service\DocumentGenerationRecord
 */
class DocumentAgentServiceToolsTest extends TestCase {

	/**
	 * The edit session.
	 *
	 * @var EditSessionService&MockObject
	 */
	private EditSessionService&MockObject $editSession;

	/**
	 * The audit logger.
	 *
	 * @var GeneratedDocumentLogger&MockObject
	 */
	private GeneratedDocumentLogger&MockObject $documentLogger;

	/**
	 * The conversion service.
	 *
	 * @var PdfConversionService&MockObject
	 */
	private PdfConversionService&MockObject $pdfConversion;

	/**
	 * The root folder.
	 *
	 * @var IRootFolder&MockObject
	 */
	private IRootFolder&MockObject $rootFolder;

	/**
	 * Set up the collaborators with "alice" signed in.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->editSession = $this->createMock(EditSessionService::class);
		$this->documentLogger = $this->createMock(GeneratedDocumentLogger::class);
		$this->pdfConversion = $this->createMock(PdfConversionService::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
	}//end setUp()

	/**
	 * Build the service.
	 *
	 * @return DocumentAgentService
	 */
	private function service(): DocumentAgentService {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		return new DocumentAgentService(
			editSession: $this->editSession,
			artefacts: new AgentArtefacts(pdfConversion: $this->pdfConversion, marker: $this->createMock(AgentArtefactMarker::class)),
			documentLogger: $this->documentLogger,
			rootFolder: $this->rootFolder,
			userSession: $session,
			logger: $this->createMock(LoggerInterface::class)
		);
	}//end service()

	/**
	 * Expect one audit row with this format, note and file.
	 *
	 * @param string $format The recorded format.
	 * @param string $note   The recorded note.
	 * @param int    $fileId The recorded file id.
	 * @param string $path   The recorded path.
	 *
	 * @return void
	 */
	private function expectRecord(string $format, string $note, int $fileId, string $path): void {
		$this->documentLogger->expects($this->once())->method('log')->with(
			$this->callback(
				static fn (DocumentGenerationRecord $r): bool => $r->format === $format
					&& $r->userId === 'alice'
					&& $r->template['name'] === $note
			),
			$this->callback(
				static fn (array $o): bool => $o['fileId'] === $fileId && $o['filePath'] === $path
					&& $o['status'] === 'generated' && $o['warnings'] === [$note]
			)
		);
	}//end expectRecord()

	/**
	 * The read tools pass the signed-in user and file id straight through.
	 *
	 * @return void
	 */
	public function testReadToolsRunAsTheSignedInUser(): void {
		$this->editSession->expects($this->once())->method('openSpreadsheetForAgent')->with('alice', 11)->willReturn(['cells' => []]);
		$this->editSession->expects($this->once())->method('openPresentationForAgent')->with('alice', 12)->willReturn(['shapes' => []]);
		$this->editSession->expects($this->once())->method('readMetadataForAgent')->with('alice', 13)->willReturn(['title' => 'T']);
		$this->documentLogger->expects($this->never())->method('log');

		$service = $this->service();
		$this->assertSame(['cells' => []], $service->readSpreadsheet(fileId: 11));
		$this->assertSame(['shapes' => []], $service->readPresentation(fileId: 12));
		$this->assertSame(['title' => 'T'], $service->readDocumentMetadata(fileId: 13));
	}//end testReadToolsRunAsTheSignedInUser()

	/**
	 * A spreadsheet edit forwards the request, records it and adds the artefact descriptor.
	 *
	 * @return void
	 */
	public function testEditSpreadsheetRecordsAndDescribesTheArtefact(): void {
		$edits = [['cell' => 'Sheet1!A1', 'value' => '5']];
		$this->editSession->expects($this->once())->method('editSpreadsheetForAgent')
			->with(
				$this->callback(
					static fn (AgentFileRequest $r): bool => $r->uid === 'alice' && $r->fileId === 21
						&& $r->version === 'v1' && $r->requestedMode === null
				),
				$edits
			)
			->willReturn(['fileId' => 22, 'path' => 'sheets/b.xlsx']);
		$this->expectRecord(format: 'spreadsheet', note: 'Agent cell edit of file 21', fileId: 22, path: 'sheets/b.xlsx');

		$result = $this->service()->editSpreadsheet(fileId: 21, edits: $edits, version: 'v1');

		$this->assertSame(['fileId' => 22, 'path' => 'sheets/b.xlsx', 'artefact' => ['type' => 'file', 'id' => '22']], $result);
	}//end testEditSpreadsheetRecordsAndDescribesTheArtefact()

	/**
	 * A presentation edit forwards the request, records it and adds the artefact descriptor.
	 *
	 * @return void
	 */
	public function testEditPresentationRecordsAndDescribesTheArtefact(): void {
		$edits = [['slide' => 's1', 'shape' => 'sh1', 'text' => 'Hi']];
		$this->editSession->expects($this->once())->method('editPresentationForAgent')
			->with($this->callback(static fn (AgentFileRequest $r): bool => $r->fileId === 31 && $r->version === 'v9'), $edits)
			->willReturn(['fileId' => 31, 'path' => 'deck.pptx']);
		$this->expectRecord(format: 'presentation', note: 'Agent shape edit of file 31', fileId: 31, path: 'deck.pptx');

		$result = $this->service()->editPresentation(fileId: 31, edits: $edits, version: 'v9');

		$this->assertSame(['type' => 'file', 'id' => '31'], $result['artefact']);
	}//end testEditPresentationRecordsAndDescribesTheArtefact()

	/**
	 * Empty anchor and mode mean "not supplied"; given ones are passed on.
	 *
	 * @return void
	 */
	public function testAddDocumentChartMapsEmptyArgumentsToNull(): void {
		$chart = ['type' => 'bar'];
		$seen = [];
		$this->editSession->expects($this->exactly(2))->method('embedChartForAgent')
			->willReturnCallback(static function (AgentFileRequest $request, array $c, ?string $afterAnchor) use (&$seen, $chart): array {
				$seen[] = [$request->uid, $request->fileId, $request->version, $request->requestedMode, $c === $chart, $afterAnchor];
				return ['ok' => true];
			});

		$service = $this->service();
		$this->assertSame(['ok' => true], $service->addDocumentChart(fileId: 41, chart: $chart, version: 'v'));
		$service->addDocumentChart(fileId: 41, chart: $chart, version: 'v', afterAnchor: 'p-3', outputMode: 'copy');

		$this->assertSame(
			[
				['alice', 41, 'v', null, true, null],
				['alice', 41, 'v', 'copy', true, 'p-3'],
			],
			$seen
		);
	}//end testAddDocumentChartMapsEmptyArgumentsToNull()

	/**
	 * Setting metadata maps an empty output mode to null and passes the values on.
	 *
	 * @return void
	 */
	public function testSetDocumentMetadataForwardsValues(): void {
		$values = ['title' => 'Besluit'];
		$modes = [];
		$this->editSession->expects($this->exactly(2))->method('setMetadataForAgent')
			->willReturnCallback(static function (AgentFileRequest $request, array $v) use (&$modes, $values): array {
				$modes[] = [$request->fileId, $request->version, $request->requestedMode, $v === $values];
				return ['version' => 'next'];
			});

		$service = $this->service();
		$this->assertSame(['version' => 'next'], $service->setDocumentMetadata(fileId: 51, metadata: $values, version: 'v2'));
		$service->setDocumentMetadata(fileId: 51, metadata: $values, version: 'v2', outputMode: 'inplace');

		$this->assertSame([[51, 'v2', null, true], [51, 'v2', 'inplace', true]], $modes);
	}//end testSetDocumentMetadataForwardsValues()

	/**
	 * A user folder that cannot be opened is reported with the file id and cause.
	 *
	 * @return void
	 */
	public function testConvertReportsAnUnopenableFolder(): void {
		$this->rootFolder->method('getUserFolder')->willThrowException(new RuntimeException('storage offline'));
		$this->pdfConversion->expects($this->never())->method('convertToPdfReporting');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Could not open file 61: storage offline');

		$this->service()->convertDocumentToPdf(fileId: 61);
	}//end testConvertReportsAnUnopenableFolder()

	/**
	 * A node that is not a file (or absent) is refused.
	 *
	 * @return void
	 */
	public function testConvertRefusesANonFile(): void {
		$folder = $this->createMock(Folder::class);
		$folder->method('getFirstNodeById')->with(62)->willReturn($this->createMock(Folder::class));
		$this->rootFolder->method('getUserFolder')->with('alice')->willReturn($folder);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('File 62 was not found in your files.');

		$this->service()->convertDocumentToPdf(fileId: 62);
	}//end testConvertRefusesANonFile()

	/**
	 * When the relative path cannot be resolved the PDF's name is used.
	 *
	 * @return void
	 */
	public function testConvertFallsBackToTheFileNameForThePath(): void {
		$source = $this->createMock(File::class);
		$pdf = $this->createMock(File::class);
		$pdf->method('getId')->willReturn(64);
		$pdf->method('getName')->willReturn('brief.pdf');
		$pdf->method('getPath')->willReturn('/alice/files/brief.pdf');

		$folder = $this->createMock(Folder::class);
		$folder->method('getFirstNodeById')->willReturn($source);
		$folder->method('getRelativePath')->willThrowException(new RuntimeException('outside'));
		$this->rootFolder->method('getUserFolder')->willReturn($folder);
		$this->pdfConversion->method('convertToPdfReporting')->with($source)->willReturn(['file' => $pdf, 'backend' => 'mpdf']);
		$this->expectRecord(format: 'pdf', note: 'Agent conversion of file 63 via mpdf', fileId: 64, path: 'brief.pdf');

		$result = $this->service()->convertDocumentToPdf(fileId: 63);

		$this->assertSame('brief.pdf', $result['path']);
		$this->assertSame('mpdf', $result['backend']);
	}//end testConvertFallsBackToTheFileNameForThePath()
}//end class
