<?php

/**
 * Unit tests for SigningEvidenceService.
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
use OCA\Filinq\Kernel\SealedArtifact;
use OCA\Filinq\Service\OpenRegisterResolver;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\ReadableFileResolver;
use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\SigningAuditService;
use OCA\Filinq\Service\SigningEvidenceService;
use OCA\Filinq\Service\SigningService;
use OCA\Filinq\Service\Waarmerk\SealedFileWriter;
use OCA\Filinq\Service\WaarmerkService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\Files\File;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;

/**
 * One sealed evidence document for a finished signing transaction.
 *
 * @covers \OCA\Filinq\Service\SigningEvidenceService
 * @uses   \OCA\Filinq\Service\ReadsStoredObjectRows
 * @uses   \OCA\Filinq\Kernel\SealedArtifact
 * @uses   \OCA\Filinq\Service\Waarmerk\SealedFileWriter
 * @uses   \OCA\Filinq\Exception\WaarmerkRefusedException
 */
class SigningEvidenceServiceTest extends TestCase {

	/**
	 * Signer records by id.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $signers = [
		's1' => ['displayName' => 'Ann', 'status' => 'SIGNED', 'signedAt' => '2026-11-10T10:00:00+00:00', 'signatureAssurance' => 'AES', 'identityEvidence' => ['method' => 'digid'], 'ipAddress' => '10.0.0.1', 'signatureData' => 'SIGDATA'],
		's2' => ['displayName' => 'Bob', 'status' => 'SIGNED', 'signedAt' => '2026-11-11T10:00:00+00:00', 'signatureAssurance' => 'SES'],
		's3' => ['displayName' => 'Cor', 'status' => 'SIGNED', 'signedAt' => '2026-11-12T10:00:00+00:00'],
	];

	/**
	 * The service over doubles.
	 *
	 * @param array<string, mixed>|null $request The request getRequest() returns.
	 * @param array<string, mixed>|null $rendered Receives the template data.
	 * @param WaarmerkService|null $waarmerk The waarmerk double.
	 * @param SealedFileWriter|null $writer The writer double.
	 * @param File|null $document The readable document.
	 * @param string $expectedScope The caller scope getRequest() must receive.
	 *
	 * @return SigningEvidenceService
	 */
	private function service(?array $request, ?array &$rendered = null, ?WaarmerkService $waarmerk = null, ?SealedFileWriter $writer = null, ?File $document = null, string $expectedScope = 'ann'): SigningEvidenceService {
		$signing = $this->createMock(SigningService::class);
		$signing->expects($this->once())->method('getRequest')->with('req-1', $expectedScope)->willReturn($request);
		$audit = $this->createMock(SigningAuditService::class);
		$audit->method('getAuditTrail')->willReturn(
			[
				['action' => 'docudesk.signing.CREATED', 'created' => '2026-11-09T10:00:00+00:00', 'changed' => ['actorDisplayName' => 'Ann', 'extra' => ['documentHash' => str_repeat('b', 64)], 'ipAddress' => '10.0.0.1']],
				['action' => 'docudesk.signing.VIEWED', 'created' => '2026-11-09T11:00:00+00:00', 'changed' => ['actorUserId' => 'bob']],
				['action' => 'docudesk.signing.COMPLETED', 'created' => '2026-11-12T10:00:00+00:00', 'changed' => []],
			]
		);
		$objects = $this->createMock(ObjectService::class);
		$objects->method('find')->willReturnCallback(fn (string $id): ?array => $this->signers[$id] ?? null);
		$settings = $this->createMock(SettingsService::class);
		$settings->method('getObjectService')->willReturn($objects);
		$bindings = $this->createMock(OpenRegisterResolver::class);
		$bindings->method('getSignerRecordRegisterAndSchema')->willReturn(['register' => 'r', 'schema' => 's']);
		$files = $this->createMock(ReadableFileResolver::class);
		$files->method('node')->willReturn($document);
		$pdf = $this->createMock(PdfService::class);
		$pdf->method('renderPdf')->willReturnCallback(function (string $templateContent, array $data) use (&$rendered): string {
			$rendered = $data;
			return '%PDF-1.4 evidence';
		});

		return new SigningEvidenceService(
			signing: $signing,
			audit: $audit,
			settings: $settings,
			bindings: $bindings,
			files: $files,
			pdfService: $pdf,
			waarmerk: $waarmerk ?? $this->createMock(WaarmerkService::class),
			writer: $writer ?? $this->createMock(SealedFileWriter::class)
		);
	}//end service()

	/**
	 * A completed request with three signers.
	 *
	 * @return array<string, mixed>
	 */
	private function completed(): array {
		return ['id' => 'req-1', 'documentName' => 'contract.pdf', 'documentFileId' => 5, 'status' => 'COMPLETED', 'signatureLevel' => 'AdES', 'provider' => 'native', 'signingMode' => 'sequential', 'signerIds' => ['s1', 's2', 's3'], 'signedDocumentRef' => '5:signed:0123456789abcdef'];
	}//end completed()

	/**
	 * Download: three signers, every step, both checksums; sealed as signing-evidence and recorded without a file.
	 *
	 * @return void
	 */
	public function testDownloadOfACompletedRequest(): void {
		$document = $this->createMock(File::class);
		$document->method('getContent')->willReturn('signed bytes');
		$artifact = new SealedArtifact(pdf: '%PDF sealed', record: ['sealType' => 'signing-evidence']);
		$waarmerk = $this->createMock(WaarmerkService::class);
		$waarmerk->expects($this->once())->method('prepare')->with('%PDF-1.4 evidence', 'contract-bewijsdocument.pdf', 'ann', 'signing-evidence')->willReturn($artifact);
		$waarmerk->expects($this->once())->method('record')->with($artifact, 0)->willReturn(['uuid' => 'w-1']);

		$result = $this->service(request: $this->completed(), rendered: $rendered, waarmerk: $waarmerk, document: $document)->download(requestId: 'req-1', callerScope: 'ann', userId: 'ann');

		$this->assertSame('%PDF sealed', $result['pdf']);
		$this->assertSame('contract-bewijsdocument.pdf', $result['fileName']);
		$evidence = $rendered['evidence'];
		$this->assertSame(['Ann', 'Bob', 'Cor'], array_column($evidence['signers'], 'name'));
		$this->assertSame('digid', $evidence['signers'][0]['method']);
		$this->assertSame('AES', $evidence['signers'][0]['assurance']);
		$this->assertSame(['CREATED', 'VIEWED', 'COMPLETED'], array_column($evidence['steps'], 'action'));
		$this->assertSame('bob', $evidence['steps'][1]['actor']);
		$this->assertSame(str_repeat('b', 64), $evidence['hashBefore']);
		$this->assertSame(hash('sha256', 'signed bytes'), $evidence['hashAfter']);
		$this->assertSame('0123456789abcdef', $evidence['signedVersionTag']);
		$text = (string) json_encode($evidence);
		$this->assertStringNotContainsString('10.0.0.1', $text);
		$this->assertStringNotContainsString('SIGDATA', $text);
		$this->assertStringNotContainsString('signed bytes', $text);
	}//end testDownloadOfACompletedRequest()

	/**
	 * An open request is a 409 naming the open signers, and nothing is sealed.
	 *
	 * @return void
	 */
	public function testAnOpenRequestIsRefusedNamingTheOpenSigners(): void {
		$this->signers['s3']['status'] = 'PENDING';
		$waarmerk = $this->createMock(WaarmerkService::class);
		$waarmerk->expects($this->never())->method('prepare');
		$request = array_merge($this->completed(), ['status' => 'IN_PROGRESS']);

		try {
			$this->service(request: $request, waarmerk: $waarmerk)->download(requestId: 'req-1', callerScope: 'ann', userId: 'ann');
			$this->fail('expected a refusal');
		} catch (WaarmerkRefusedException $e) {
			$this->assertSame(409, $e->getStatus());
			$this->assertSame(['openSigners' => ['Cor']], $e->getDetails());
		}
	}//end testAnOpenRequestIsRefusedNamingTheOpenSigners()

	/**
	 * A request that is not the caller's is a 404; an admin reads unscoped.
	 *
	 * @return void
	 */
	public function testNotFoundAndAdminScope(): void {
		try {
			$this->service(request: null)->download(requestId: 'req-1', callerScope: 'ann', userId: 'ann');
			$this->fail('expected a refusal');
		} catch (WaarmerkRefusedException $e) {
			$this->assertSame(404, $e->getStatus());
		}

		$waarmerk = $this->createMock(WaarmerkService::class);
		$waarmerk->method('prepare')->willReturn(new SealedArtifact(pdf: '%PDF', record: []));
		$waarmerk->method('record')->willReturn([]);
		$this->assertSame('%PDF', $this->service(request: $this->completed(), waarmerk: $waarmerk, expectedScope: '')->download(requestId: 'req-1', callerScope: '', userId: 'root')['pdf']);
	}//end testNotFoundAndAdminScope()

	/**
	 * Copy to dossier writes next to the signed document; without a readable document it is a 404.
	 *
	 * @return void
	 */
	public function testCopyToDossier(): void {
		$folder = $this->createMock(Folder::class);
		$document = $this->createMock(File::class);
		$document->method('getParent')->willReturn($folder);
		$document->method('getContent')->willReturn('signed bytes');
		$artifact = new SealedArtifact(pdf: '%PDF sealed', record: []);
		$waarmerk = $this->createMock(WaarmerkService::class);
		$waarmerk->method('prepare')->willReturn($artifact);
		$writer = $this->createMock(SealedFileWriter::class);
		$writer->expects($this->once())->method('write')->with($folder, 'contract-bewijsdocument.pdf', $artifact)->willReturn(['fileId' => 12, 'fileName' => 'contract-bewijsdocument.pdf', 'waarmerk' => []]);

		$this->assertSame(12, $this->service(request: $this->completed(), waarmerk: $waarmerk, writer: $writer, document: $document)->copyToDossier(requestId: 'req-1', callerScope: 'ann', userId: 'ann')['fileId']);

		$this->expectException(WaarmerkRefusedException::class);
		$this->service(request: $this->completed(), waarmerk: $waarmerk)->copyToDossier(requestId: 'req-1', callerScope: 'ann', userId: 'ann');
	}//end testCopyToDossier()
}//end class
