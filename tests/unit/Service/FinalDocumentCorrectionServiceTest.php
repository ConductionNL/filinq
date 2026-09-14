<?php

/**
 * Unit tests for FinalDocumentCorrectionService
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

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\FinalDocumentCorrectionService;
use OCA\Filinq\Service\FinalDocumentRepository;
use OCA\Filinq\Service\FinalDocumentService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\Files\File;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Asserts that a correction supersedes rather than overwrites: a new version
 * references the old one, the old one stays readable and stays final, and a
 * reader of either end can walk the chain.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class FinalDocumentCorrectionServiceTest extends TestCase {

	/**
	 * Objects the fake OpenRegister was asked to store, in order.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $written = [];

	/**
	 * The file the correction was written into, if any.
	 *
	 * @var File|null
	 */
	private ?File $copy = null;

	/**
	 * Whether the frozen file was opened for writing.
	 *
	 * @var bool
	 */
	private bool $frozenFileWasWritten = false;

	/**
	 * One stored final record.
	 *
	 * @return array<string, mixed> The record.
	 */
	private function finalRecord(): array {
		return [
			'uuid' => 'version-1',
			'fileId' => 4711,
			'versionLabel' => '',
			'documentName' => 'Besluit.docx',
			'status' => 'final',
			'finalisedBy' => 'anna',
			'finalisedByName' => 'Anna de Boer',
			'finalisedAt' => '2026-09-03T14:05:00+00:00',
			'finalReason' => 'Het besluit is genomen',
			'unfrozen' => false,
		];

	}//end finalRecord()

	/**
	 * Build the service over a fake register holding `$rows`.
	 *
	 * @param array<int, array<string, mixed>> $rows The stored records.
	 *
	 * @return FinalDocumentCorrectionService The service.
	 */
	private function service(array $rows): FinalDocumentCorrectionService {
		$this->written = [];
		$this->frozenFileWasWritten = false;

		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('searchObjects')->willReturn($rows);
		$objectService->method('find')->willReturnCallback(
			static function (...$arguments) use ($rows): ?array {
				$id = (string)($arguments[0] ?? '');
				foreach ($rows as $row) {
					if ((string)($row['uuid'] ?? '') === $id) {
						return $row;
					}
				}

				return null;
			}
		);
		$objectService->method('saveObject')->willReturnCallback(
			function (...$arguments): array {
				$object = ($arguments[0] ?? []);
				$this->written[] = $object;

				return ($object + ['uuid' => 'version-2']);
			}
		);

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);

		$repository = new FinalDocumentRepository($resolver, $this->createMock(LoggerInterface::class));

		$this->copy = $this->createMock(File::class);
		$this->copy->method('getId')->willReturn(9002);
		$this->copy->method('getName')->willReturn('Besluit correction.docx');
		$this->copy->method('getPath')->willReturn('/anna/files/Besluiten/Besluit correction.docx');

		$parent = $this->createMock(Folder::class);
		$parent->method('getNonExistingName')->willReturnArgument(0);
		$parent->method('newFile')->willReturn($this->copy);

		$frozen = $this->createMock(File::class);
		$frozen->method('getId')->willReturn(4711);
		$frozen->method('getName')->willReturn('Besluit.docx');
		$frozen->method('getContent')->willReturn('besluit');
		$frozen->method('getParent')->willReturn($parent);
		$frozen->method('putContent')->willReturnCallback(
			function (): bool {
				$this->frozenFileWasWritten = true;

				return true;
			}
		);

		$finalDocuments = $this->createMock(FinalDocumentService::class);
		$finalDocuments->method('isFinal')->willReturnCallback(
			static fn (array $record): bool => (($record['status'] ?? '') === 'final')
		);
		$finalDocuments->method('resolveFile')->willReturn($frozen);
		$finalDocuments->method('resolveActor')->willReturn(['id' => 'anna', 'name' => 'Anna de Boer']);

		return new FinalDocumentCorrectionService(
			$repository,
			$finalDocuments,
			$this->createMock(LoggerInterface::class)
		);

	}//end service()

	/**
	 * A correction is a new version referencing the one it supersedes.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testACorrectionSupersedesTheFinalVersion(): void {
		$result = $this->service(rows: [$this->finalRecord()])
			->correct(fileId: 4711, reason: 'The besluit named the wrong street');

		$this->assertSame(9002, $result['fileId']);
		$this->assertSame('version-1', $result['version']['supersedes']);
		$this->assertSame('draft', $result['version']['status']);

	}//end testACorrectionSupersedesTheFinalVersion()

	/**
	 * The superseded version stays readable and stays final.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testTheSupersededVersionStaysFinalAndIsNeverOverwritten(): void {
		$service = $this->service(rows: [$this->finalRecord()]);
		$service->correct(fileId: 4711, reason: 'The besluit named the wrong street');

		$this->assertFalse(
			$this->frozenFileWasWritten,
			'The frozen file must never be opened for writing by a correction.'
		);

		$backReference = $this->written[1];
		$this->assertSame('final', $backReference['status']);
		$this->assertSame('version-2', $backReference['supersededBy']);
		$this->assertSame('anna', $backReference['finalisedBy']);

	}//end testTheSupersededVersionStaysFinalAndIsNeverOverwritten()

	/**
	 * A reader of either end of the chain can see the other.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testAReaderOfEitherEndSeesTheChain(): void {
		$correction = [
			'uuid' => 'version-2',
			'fileId' => 9002,
			'versionLabel' => '',
			'status' => 'draft',
			'supersedes' => 'version-1',
		];

		$chain = $this->service(rows: [$correction, ($this->finalRecord() + ['supersededBy' => 'version-2'])])
			->chainFor(fileId: 9002);

		$this->assertSame('version-2', $chain['version']['uuid']);
		$this->assertSame('version-1', $chain['supersedes']['uuid']);

	}//end testAReaderOfEitherEndSeesTheChain()

	/**
	 * A document that is not final is edited directly, not superseded.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testANonFinalDocumentIsNotCorrectedBySuperseding(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/not final/');

		$this->service(rows: [])->correct(fileId: 4711, reason: 'nothing to supersede');

	}//end testANonFinalDocumentIsNotCorrectedBySuperseding()
}//end class
