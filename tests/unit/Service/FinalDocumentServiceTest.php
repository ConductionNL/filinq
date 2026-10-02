<?php

/**
 * Unit tests for FinalDocumentService
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
use OCA\Filinq\Service\FinalDocumentRepository;
use OCA\Filinq\Service\FinalDocumentService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Tests that a version becomes final with its facts recorded, and that a final
 * version refuses every write with a sentence naming who froze it and when.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class FinalDocumentServiceTest extends TestCase {

	/**
	 * Rows the fake OpenRegister answers with.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $rows = [];

	/**
	 * Objects the fake OpenRegister was asked to store.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $written = [];

	/**
	 * Build the service over a fake register holding `$rows`.
	 *
	 * @param array<int, array<string, mixed>> $rows The stored finalisation records.
	 * @param bool $unreachable Whether the register throws instead of answering.
	 * @param string $content The document's content, which decides its checksum.
	 *
	 * @return FinalDocumentService The service.
	 */
	private function service(array $rows = [], bool $unreachable = false, string $content = 'besluit'): FinalDocumentService {
		$this->rows = $rows;
		$this->written = [];

		$objectService = $this->createMock(ObjectService::class);
		if ($unreachable === true) {
			$objectService->method('searchObjectsBySlug')->willThrowException(new RuntimeException('no register'));
		} else {
			$objectService->method('searchObjectsBySlug')->willReturnCallback(fn (): array => $this->rows);
		}

		$objectService->method('saveObject')->willReturnCallback(
			function (...$arguments): array {
				$object = ($arguments['object'] ?? ($arguments[0] ?? []));
				$this->written[] = $object;

				return ($object + ['uuid' => 'stored-uuid']);
			}
		);

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);

		$repository = new FinalDocumentRepository($resolver, $this->createMock(LoggerInterface::class));

		return new FinalDocumentService(
			$repository,
			$this->rootFolder(content: $content),
			$this->userSession(),
			$this->createMock(LoggerInterface::class)
		);

	}//end service()

	/**
	 * A root folder answering with one readable file.
	 *
	 * @param string $content The file's content.
	 *
	 * @return IRootFolder The root folder.
	 */
	private function rootFolder(string $content): IRootFolder {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(4711);
		$file->method('getName')->willReturn('Besluit.docx');
		$file->method('getContent')->willReturn($content);

		$folder = $this->createMock(Folder::class);
		$folder->method('getById')->willReturn([$file]);

		$rootFolder = $this->createMock(IRootFolder::class);
		$rootFolder->method('getUserFolder')->willReturn($folder);

		return $rootFolder;

	}//end rootFolder()

	/**
	 * A session holding one named person.
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
	 * One stored final record.
	 *
	 * @param array<string, mixed> $overrides Fields to override.
	 *
	 * @return array<string, mixed> The record.
	 */
	private function finalRecord(array $overrides = []): array {
		return array_merge([
			'uuid' => 'version-1',
			'fileId' => 4711,
			'versionLabel' => '',
			'documentName' => 'Besluit.docx',
			'status' => 'final',
			'finalisedBy' => 'anna',
			'finalisedByName' => 'Anna de Boer',
			'finalisedAt' => '2026-09-03T14:05:00+00:00',
			'finalReason' => 'Het besluit is genomen',
			'fileChecksum' => hash('sha256', 'besluit'),
			'unfrozen' => false,
		], $overrides);

	}//end finalRecord()

	/**
	 * Making a draft final records the person, the moment, the reason and the checksum.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testFinalisingRecordsWhoWhenWhyAndTheChecksum(): void {
		$stored = $this->service()->finalise(fileId: 4711, reason: 'Het besluit is genomen');

		$this->assertSame('final', $stored['status']);
		$this->assertSame('anna', $stored['finalisedBy']);
		$this->assertSame('Anna de Boer', $stored['finalisedByName']);
		$this->assertSame('Het besluit is genomen', $stored['finalReason']);
		$this->assertSame(hash('sha256', 'besluit'), $stored['fileChecksum']);
		$this->assertNotSame('', (string)$stored['finalisedAt']);

	}//end testFinalisingRecordsWhoWhenWhyAndTheChecksum()

	/**
	 * A final version refuses a write, and the refusal names who froze it and when.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testAFinalVersionRefusesAWriteAndSaysWhoFrozeIt(): void {
		$refusal = $this->service(rows: [$this->finalRecord()])
			->refusalFor(fileId: 4711, action: 'edit this document');

		$this->assertIsString($refusal);
		$this->assertStringContainsString('Besluit.docx', $refusal);
		$this->assertStringContainsString('is final', $refusal);
		$this->assertStringContainsString('Anna de Boer', $refusal);
		$this->assertStringContainsString('3 September 2026', $refusal);
		$this->assertStringContainsString('Het besluit is genomen', $refusal);

	}//end testAFinalVersionRefusesAWriteAndSaysWhoFrozeIt()

	/**
	 * The refusal is thrown, with the version's facts attached.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testAssertWritableThrowsWithTheVersionAttached(): void {
		$service = $this->service(rows: [$this->finalRecord()]);

		try {
			$service->assertWritable(fileId: 4711, action: 'replace the file behind this document');
			$this->fail('A final version must refuse the write.');
		} catch (DocumentFinalException $e) {
			$this->assertStringContainsString('replace the file behind this document', $e->getMessage());
			$this->assertSame('final', $e->getVersion()['status']);
			$this->assertSame('anna', $e->getVersion()['finalisedBy']);
		}

	}//end testAssertWritableThrowsWithTheVersionAttached()

	/**
	 * A document nobody made final behaves exactly as before.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testANonFinalDocumentIsNotRefused(): void {
		$this->assertNull($this->service()->refusalFor(fileId: 4711));
		$this->assertNull(
			$this->service(rows: [$this->finalRecord(['status' => 'draft'])])->refusalFor(fileId: 4711)
		);

	}//end testANonFinalDocumentIsNotRefused()

	/**
	 * A record about an older set of bytes does not freeze the current content.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testARecordAboutAnEarlierVersionDoesNotFreezeTheCurrentOne(): void {
		$this->assertNull(
			$this->service(rows: [$this->finalRecord(['versionLabel' => '1756900000'])])->refusalFor(fileId: 4711)
		);

	}//end testARecordAboutAnEarlierVersionDoesNotFreezeTheCurrentOne()

	/**
	 * An unreachable register refuses the write rather than allowing it.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testAnUnreachableRegisterFailsClosed(): void {
		$refusal = $this->service(rows: [], unreachable: true)->refusalFor(fileId: 4711);

		$this->assertIsString($refusal);
		$this->assertStringContainsString('unreachable', $refusal);

	}//end testAnUnreachableRegisterFailsClosed()

	/**
	 * A version that is already final is not finalised a second time.
	 *
	 * The first moment is the one the archive needs, so overwriting it is
	 * refused rather than silently accepted.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testAFinalVersionIsNotFinalisedTwice(): void {
		$this->expectException(DocumentFinalException::class);

		$this->service(rows: [$this->finalRecord()])->finalise(fileId: 4711, reason: 'again');

	}//end testAFinalVersionIsNotFinalisedTwice()

	/**
	 * A file changed outside the product is reported against its final version.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testAFileChangedOnTheStorageIsReported(): void {
		$report = $this->service(rows: [$this->finalRecord()], content: 'tampered')
			->verifyChecksum(fileId: 4711);

		$this->assertTrue($report['final']);
		$this->assertFalse($report['matches']);
		$this->assertStringContainsString('changed on the storage', $report['message']);

	}//end testAFileChangedOnTheStorageIsReported()

	/**
	 * An untouched final file reports no mismatch.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testAnUntouchedFinalFileReportsNoMismatch(): void {
		$report = $this->service(rows: [$this->finalRecord()])->verifyChecksum(fileId: 4711);

		$this->assertTrue($report['final']);
		$this->assertTrue($report['matches']);
		$this->assertSame('', $report['message']);

	}//end testAnUntouchedFinalFileReportsNoMismatch()
}//end class
