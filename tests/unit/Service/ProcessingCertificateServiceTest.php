<?php

/**
 * Unit tests for ProcessingCertificateService.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Exception\WaarmerkRefusedException;
use OCA\Filinq\Service\AnonymiserBackendStateClient;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\ProcessingCertificateService;
use OCA\Filinq\Service\ReadableFileResolver;
use OCA\Filinq\Service\Waarmerk\SealedArtifact;
use OCA\Filinq\Service\Waarmerk\SealedFileWriter;
use OCA\Filinq\Service\WaarmerkService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\Files\File;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;

/**
 * The processing certificate attests the recorded run: types and counts, never values.
 *
 * @covers \OCA\Filinq\Service\ProcessingCertificateService
 * @uses   \OCA\Filinq\Service\ReadsStoredObjectRows
 * @uses   \OCA\Filinq\Service\Waarmerk\SealedArtifact
 * @uses   \OCA\Filinq\Service\Waarmerk\SealedFileWriter
 * @uses   \OCA\Filinq\Exception\WaarmerkRefusedException
 */
class ProcessingCertificateServiceTest extends TestCase {

	/**
	 * A stored link, as OpenRegister answers it.
	 *
	 * @return array<string, mixed>
	 */
	private function link(): array {
		return [
			'uuid' => 'link-1', 'sourceFileName' => 'besluit.docx', 'anonymizedFileId' => 9, 'anonymizedFileName' => 'besluit-geanonimiseerd.pdf',
			'outputFormat' => 'pdf', 'replacementCount' => 4, 'runCount' => 1, 'anonymizedAt' => '2026-11-14T09:00:00+00:00',
			'anonymizedBy' => 'alice', 'verificationVerdict' => 'clean', 'mappingRef' => 'secret-map-ref',
			'entityTypeCounts' => ['PERSON' => 3, 'BSN' => 1, 'Jan Jansen' => 1, 'IBAN' => 'many'],
		];
	}//end link()

	/**
	 * The service over doubles.
	 *
	 * @param array<string, mixed>|null $link The stored link, or null.
	 * @param File|null $node The anonymised file the caller can read, or null.
	 * @param array<string, mixed>|null $rendered Receives the template data.
	 * @param WaarmerkService|null $waarmerk The waarmerk double.
	 * @param SealedFileWriter|null $writer The writer double.
	 *
	 * @return ProcessingCertificateService
	 */
	private function service(?array $link, ?File $node, ?array &$rendered = null, ?WaarmerkService $waarmerk = null, ?SealedFileWriter $writer = null): ProcessingCertificateService {
		$objects = $this->createMock(ObjectService::class);
		$objects->method('find')->willReturn($link);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);
		$files = $this->createMock(ReadableFileResolver::class);
		$files->method('node')->willReturn($node);
		$backends = $this->createMock(AnonymiserBackendStateClient::class);
		$backends->method('getState')->willReturn(['known' => true, 'effectiveMethod' => 'presidio']);
		$pdf = $this->createMock(PdfService::class);
		$pdf->method('renderPdf')->willReturnCallback(function (string $templateContent, array $data) use (&$rendered): string {
			$rendered = $data;
			return '%PDF-1.4 certificate';
		});

		return new ProcessingCertificateService(
			objectResolver: $resolver,
			files: $files,
			backends: $backends,
			pdfService: $pdf,
			waarmerk: $waarmerk ?? $this->createMock(WaarmerkService::class),
			writer: $writer ?? $this->createMock(SealedFileWriter::class)
		);
	}//end service()

	/**
	 * Only recorded fields and well-formed type keys reach the certificate; no mapping ref, no value.
	 *
	 * @return void
	 */
	public function testCertificateDataIsMinimised(): void {
		$data = $this->service(link: null, node: null)->certificateData(link: $this->link());

		$this->assertSame(['BSN' => 1, 'PERSON' => 3], $data['entityTypeCounts']);
		$this->assertTrue($data['entityTypeCountsRecorded']);
		$this->assertSame('presidio', $data['detectionBackend']);
		$this->assertSame('link-1', $data['anonymizationLinkId']);
		$this->assertSame(4, $data['replacementCount']);
		$this->assertSame('alice', $data['anonymizedBy']);
		$this->assertArrayNotHasKey('mappingRef', $data);
		$this->assertStringNotContainsString('Jan Jansen', (string) json_encode($data));

		$older = $this->link();
		unset($older['entityTypeCounts']);
		$this->assertFalse($this->service(link: null, node: null)->certificateData(link: $older)['entityTypeCountsRecorded']);
	}//end testCertificateDataIsMinimised()

	/**
	 * Certify: renders the data, seals as processing-certificate with the link id, writes next to the anonymised file.
	 *
	 * @return void
	 */
	public function testCertifySealsAndWrites(): void {
		$folder = $this->createMock(Folder::class);
		$node = $this->createMock(File::class);
		$node->method('getParent')->willReturn($folder);
		$node->method('getName')->willReturn('besluit-geanonimiseerd.pdf');
		$artifact = new SealedArtifact(pdf: '%PDF sealed', record: []);
		$waarmerk = $this->createMock(WaarmerkService::class);
		$waarmerk->expects($this->once())->method('prepare')
			->with('%PDF-1.4 certificate', 'besluit-geanonimiseerd-anonimisatiecertificaat.pdf', 'alice', 'processing-certificate', 'link-1')
			->willReturn($artifact);
		$writer = $this->createMock(SealedFileWriter::class);
		$writer->expects($this->once())->method('write')
			->with($folder, 'besluit-geanonimiseerd-anonimisatiecertificaat.pdf', $artifact)
			->willReturn(['waarmerk' => ['uuid' => 'w-1'], 'fileId' => 11, 'fileName' => 'besluit-geanonimiseerd-anonimisatiecertificaat.pdf']);

		$result = $this->service(link: $this->link(), node: $node, rendered: $rendered, waarmerk: $waarmerk, writer: $writer)->certify(linkId: 'link-1', userId: 'alice');

		$this->assertSame(11, $result['fileId']);
		$this->assertSame(['BSN' => 1, 'PERSON' => 3], $result['certificate']['entityTypeCounts']);
		$this->assertSame($result['certificate'], $rendered['certificate']);
	}//end testCertifySealsAndWrites()

	/**
	 * An unknown link and an unreadable anonymised file are the same 404, and nothing is sealed.
	 *
	 * @return void
	 */
	public function testUnknownOrUnreadableIs404(): void {
		$waarmerk = $this->createMock(WaarmerkService::class);
		$waarmerk->expects($this->never())->method('prepare');
		foreach ([[null, $this->createMock(File::class)], [$this->link(), null]] as [$link, $node]) {
			try {
				$this->service(link: $link, node: $node, waarmerk: $waarmerk)->certify(linkId: 'link-1', userId: 'alice');
				$this->fail('expected a refusal');
			} catch (WaarmerkRefusedException $e) {
				$this->assertSame(404, $e->getStatus());
				$this->assertSame('Anonymisation run not found', $e->getMessage());
			}
		}
	}//end testUnknownOrUnreadableIs404()
}//end class
