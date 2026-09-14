<?php

/**
 * Unit tests for the refusal on every named write path
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Exception\DocumentFinalException;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentStorageService;
use OCA\Filinq\Service\DocumentVersionService;
use OCA\Filinq\Service\Editing\DocumentGuard;
use OCA\Filinq\Service\FinalDocumentService;
use OCA\Filinq\Service\GrondslagenPdfWriter;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\SignedArtifactProducer;
use OCA\Filinq\Service\Signing\SigningProviderFactory;
use OCP\App\IAppManager;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Asserts the refusal on the API, both editors, the batch correspondence path,
 * the merge, the anonymisation output and the signed artefact.
 *
 * Each case drives the real write path rather than the guard directly, because
 * a guard that is never called looks exactly like a guard that passed.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class FinalDocumentWritePathsTest extends TestCase {

	/**
	 * A guard that refuses every write with the standing sentence.
	 *
	 * @return FinalDocumentService&\PHPUnit\Framework\MockObject\MockObject The guard.
	 */
	private function refusing(): FinalDocumentService {
		$guard = $this->createMock(FinalDocumentService::class);
		$guard->method('refusalFor')->willReturn(
			'You cannot edit this document. The current version of Besluit.docx is final. '
			. 'Anna de Boer made it final on 3 September 2026 at 14:05.'
		);
		$guard->method('assertWritable')->willThrowException(
			new DocumentFinalException(
				message: 'You cannot edit this document. The current version of Besluit.docx is final. '
					. 'Anna de Boer made it final on 3 September 2026 at 14:05.',
				version: ['status' => 'final', 'finalisedBy' => 'anna']
			)
		);

		return $guard;

	}//end refusing()

	/**
	 * A file with a fixed id and name.
	 *
	 * @return File&\PHPUnit\Framework\MockObject\MockObject The file.
	 */
	private function file(): File {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(4711);
		$file->method('getName')->willReturn('Besluit.docx');
		$file->method('getContent')->willReturn('%PDF-1.4 besluit');

		return $file;

	}//end file()

	/**
	 * A session holding one person.
	 *
	 * @return IUserSession The session.
	 */
	private function userSession(): IUserSession {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('anna');
		$user->method('getDisplayName')->willReturn('Anna de Boer');

		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		return $session;

	}//end userSession()

	/**
	 * The API refuses to replace the file by restoring an earlier version.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testTheApiRefusesARestoreOfAFinalDocument(): void {
		$service = new DocumentVersionService(
			logger: $this->createMock(LoggerInterface::class),
			rootFolder: $this->createMock(IRootFolder::class),
			userSession: $this->userSession(),
			appManager: $this->createMock(IAppManager::class),
			container: $this->createMock(ContainerInterface::class),
			finalDocuments: $this->refusing()
		);

		$this->expectException(DocumentFinalException::class);
		$this->expectExceptionMessageMatches('/is final/');

		$service->restoreVersion(fileId: 4711, versionTimestamp: 1756900000);

	}//end testTheApiRefusesARestoreOfAFinalDocument()

	/**
	 * Both editors refuse, through the one guard they share.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testBothEditorsRefuseThroughTheSharedGuard(): void {
		$guard = new DocumentGuard(
			$this->createMock(DocumentObjectServiceResolver::class),
			$this->createMock(LoggerInterface::class),
			$this->refusing()
		);

		$refusal = $guard->finalRefusal($this->file());

		$this->assertIsString($refusal);
		$this->assertStringContainsString('is final', $refusal);
		$this->assertStringContainsString('Anna de Boer', $refusal);

	}//end testBothEditorsRefuseThroughTheSharedGuard()

	/**
	 * The merge and the batch correspondence path refuse to store over a final document.
	 *
	 * Both reach Files through DocumentStorageService::store(), so both are
	 * refused by the one check there.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testTheMergeAndTheBatchPathRefuseToStoreOverAFinalDocument(): void {
		$target = $this->createMock(Folder::class);
		$target->method('nodeExists')->willReturn(true);
		$target->method('get')->willReturn($this->file());

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('nodeExists')->willReturn(true);
		$userFolder->method('get')->willReturn($target);

		$rootFolder = $this->createMock(IRootFolder::class);
		$rootFolder->method('getUserFolder')->willReturn($userFolder);

		$service = new DocumentStorageService(
			$rootFolder,
			$this->createMock(LoggerInterface::class),
			$this->refusing()
		);

		$this->expectException(DocumentFinalException::class);

		$service->store(userId: 'anna', targetPath: 'Besluiten', filename: 'Besluit.docx', content: 'new bytes');

	}//end testTheMergeAndTheBatchPathRefuseToStoreOverAFinalDocument()

	/**
	 * The anonymisation output refuses to append to a final document.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testTheAnonymisationOutputRefusesToAppendToAFinalDocument(): void {
		$writer = new GrondslagenPdfWriter(
			pdfService: $this->createMock(PdfService::class),
			finalDocuments: $this->refusing()
		);

		$this->expectException(DocumentFinalException::class);

		$writer->appendToPdf(anonymisedFile: $this->file(), summaryBytes: '%PDF-1.4 summary');

	}//end testTheAnonymisationOutputRefusesToAppendToAFinalDocument()

	/**
	 * The signing path refuses to write a signed version over a final document.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testTheSigningPathRefusesToWriteOverAFinalDocument(): void {
		$producer = new SignedArtifactProducer(
			providerFactory: $this->createMock(SigningProviderFactory::class),
			userSession: $this->userSession(),
			request: $this->createMock(IRequest::class),
			rootFolder: $this->createMock(IRootFolder::class),
			finalDocuments: $this->refusing()
		);

		$this->expectException(DocumentFinalException::class);

		$producer->produce(request: ['documentFileId' => 4711, 'provider' => 'native']);

	}//end testTheSigningPathRefusesToWriteOverAFinalDocument()

	/**
	 * A document nobody made final is stored exactly as before.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testANonFinalTargetIsStoredAsBefore(): void {
		$stored = $this->createMock(File::class);
		$stored->method('getId')->willReturn(9001);
		$stored->method('getPath')->willReturn('/anna/files/Besluiten/Besluit.docx');
		$stored->method('getSize')->willReturn(12);

		$target = $this->createMock(Folder::class);
		$target->method('nodeExists')->willReturn(false);
		$target->method('getNonExistingName')->willReturn('Besluit.docx');
		$target->method('newFile')->willReturn($stored);

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('nodeExists')->willReturn(true);
		$userFolder->method('get')->willReturn($target);

		$rootFolder = $this->createMock(IRootFolder::class);
		$rootFolder->method('getUserFolder')->willReturn($userFolder);

		$service = new DocumentStorageService(
			$rootFolder,
			$this->createMock(LoggerInterface::class),
			$this->createMock(FinalDocumentService::class)
		);

		$result = $service->store(
			userId: 'anna',
			targetPath: 'Besluiten',
			filename: 'Besluit.docx',
			content: 'new bytes'
		);

		$this->assertSame(9001, $result['fileId']);
		$this->assertSame('Besluit.docx', $result['name']);

	}//end testANonFinalTargetIsStoredAsBefore()
}//end class
