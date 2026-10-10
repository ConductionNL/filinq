<?php

/**
 * The eraser on its own: what it refuses to write, and what it says when a
 * write half-fails.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\SubjectErasure
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-3.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\SubjectErasure;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\FinalDocumentRepository;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureEraser;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;
use ZipArchive;

class SubjectErasureEraserTest extends TestCase {
	use SubjectErasureDoubles;

	private const PERSON = 'Jan Jansen';

	private const VERSION_MANAGER = 'OCA\Files_Versions\Versions\IVersionManager';

	private const PROCESSOR = 'OCA\OpenRegister\Service\File\DocumentProcessingHandler';

	/**
	 * The real eraser over the in-memory files and register.
	 *
	 * @return SubjectErasureEraser The eraser.
	 */
	private function eraser(): SubjectErasureEraser {
		$container = $this->erasureContainer();
		$finals = new FinalDocumentRepository(new DocumentObjectServiceResolver($container, $this->apps()), new NullLogger());

		return new SubjectErasureEraser($container, $finals, $this->clock());

	}//end eraser()

	/**
	 * A Word document whose body XML is the given runs.
	 *
	 * @param string $body The w:body inner XML.
	 *
	 * @return string The docx bytes.
	 */
	private function docx(string $body): string {
		$path = tempnam(sys_get_temp_dir(), 'filinq-test-docx-');
		$zip = new ZipArchive();
		$zip->open($path, ZipArchive::OVERWRITE);
		$zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types/>');
		$zip->addFromString('word/document.xml', '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>' . $body . '</w:body></w:document>');
		$zip->close();
		$bytes = (string) file_get_contents($path);
		unlink($path);

		return $bytes;

	}//end docx()

	/**
	 * The names of the files that were not deleted.
	 *
	 * @return array<int, string> The live file names.
	 */
	private function liveNames(): array {
		return array_values(array_column(array_filter($this->files, static fn (array $f): bool => $f['deleted'] === false), 'name'));

	}//end liveNames()

	/**
	 * A Word file whose runs still spell the name after the rewrite is not
	 * written: the check reads the text a reader sees, not the zipped bytes.
	 *
	 * @return void
	 */
	public function testAWordFileWhoseRunsStillSpellTheNameIsNotWritten(): void {
		$original = $this->docx(body: '<w:p><w:r><w:t>Aanvrager: Jan </w:t></w:r><w:r><w:t>Jansen</w:t></w:r></w:p>');
		$this->addFile(fileId: 11, name: 'aanvraag.docx', content: $original);
		$this->assertStringNotContainsString(self::PERSON, $original, 'Precondition: the zipped bytes hide the name.');
		// The processor replaces per run, so a name split over two runs survives.
		$this->rewriteMisses = true;

		try {
			$this->eraser()->erase(file: $this->fileNode(fileId: 11), values: [self::PERSON]);
			$this->fail('A rewrite that left the name in the text was written.');
		} catch (RuntimeException $e) {
			$this->assertStringContainsString('still contains one of the identifiers', $e->getMessage());
		}

		$this->assertSame($original, $this->files[11]['content']);
		$this->assertSame(['aanvraag.docx'], $this->liveNames(), 'The rewritten copy is discarded.');

	}//end testAWordFileWhoseRunsStillSpellTheNameIsNotWritten()

	/**
	 * A Word file whose rewrite removed the name is written, and its earlier
	 * versions, which still hold the name, are removed.
	 *
	 * @return void
	 */
	public function testAWordFileRewrittenCleanIsWrittenAndItsVersionsGo(): void {
		$this->addFile(fileId: 11, name: 'aanvraag.docx', content: $this->docx(body: '<w:p><w:r><w:t>Aanvrager: Jan Jansen</w:t></w:r></w:p>'));
		$this->rewriter = fn (string $bytes, array $replacements): string => $this->docx(body: '<w:p><w:r><w:t>Aanvrager: ' . SubjectErasureEraser::MARK . '</w:t></w:r></w:p>');

		$outcome = $this->eraser()->erase(file: $this->fileNode(fileId: 11), values: [self::PERSON]);

		// The version kept before this run and the one the overwrite made.
		$this->assertSame(2, $outcome['versionsDeleted']);
		$this->assertSame([], array_values(array_filter($this->files[11]['versions'], fn (string $v): bool => str_contains($v, 'Jan Jansen'))));
		$this->assertSame(['aanvraag.docx'], $this->liveNames());

	}//end testAWordFileRewrittenCleanIsWrittenAndItsVersionsGo()

	/**
	 * A PDF is not re-read: OpenRegister's strict rewrite already refuses a
	 * PDF with residual text, so the eraser trusts it.
	 *
	 * @return void
	 */
	public function testAPdfIsTrustedToTheStrictRewrite(): void {
		$this->addFile(fileId: 11, name: 'besluit.pdf', content: 'Aanvrager: Jan Jansen');

		$this->eraser()->erase(file: $this->fileNode(fileId: 11), values: [self::PERSON]);

		$this->assertSame('Aanvrager: ' . SubjectErasureEraser::MARK, $this->files[11]['content']);

		$this->rewriteMisses = true;
		$this->addFile(fileId: 12, name: 'bijlage.pdf', content: 'Bijlage van Jan Jansen');
		$this->expectExceptionMessage('could not be rewritten without the person: Residual entity text');
		$this->eraser()->erase(file: $this->fileNode(fileId: 12), values: [self::PERSON]);

	}//end testAPdfIsTrustedToTheStrictRewrite()

	/**
	 * Nothing to erase is refused before a copy is made.
	 *
	 * @return void
	 */
	public function testNothingToEraseIsRefusedBeforeACopyIsMade(): void {
		$this->addFile(fileId: 11, name: 'aanvraag.txt', content: 'Aanvrager: Jan Jansen');

		try {
			$this->eraser()->erase(file: $this->fileNode(fileId: 11), values: []);
			$this->fail('An empty erase went ahead.');
		} catch (RuntimeException $e) {
			$this->assertStringContainsString('every occurrence in this document was excluded', $e->getMessage());
		}

		$this->assertCount(1, $this->files);

	}//end testNothingToEraseIsRefusedBeforeACopyIsMade()

	/**
	 * Without OpenRegister's processor the document is left as it was.
	 *
	 * @return void
	 */
	public function testWithoutTheProcessorTheDocumentIsLeftAlone(): void {
		$this->addFile(fileId: 11, name: 'aanvraag.txt', content: 'Aanvrager: Jan Jansen');
		$this->absent = [self::PROCESSOR];

		$this->expectExceptionMessage('could not be rewritten without the person');
		try {
			$this->eraser()->erase(file: $this->fileNode(fileId: 11), values: [self::PERSON]);
		} finally {
			$this->assertSame('Aanvrager: Jan Jansen', $this->files[11]['content']);
		}

	}//end testWithoutTheProcessorTheDocumentIsLeftAlone()

	/**
	 * A locked document is not changed, says why, and the erased copy is
	 * not left behind.
	 *
	 * @return void
	 */
	public function testALockedDocumentSaysSoAndLeavesNoCopy(): void {
		$this->addFile(fileId: 11, name: 'aanvraag.txt', content: 'Aanvrager: Jan Jansen');
		$this->unwritable = [11];

		try {
			$this->eraser()->erase(file: $this->fileNode(fileId: 11), values: [self::PERSON]);
			$this->fail('A locked document was reported erased.');
		} catch (RuntimeException $e) {
			$this->assertSame('The erased content could not be written into the document: The file is locked.', $e->getMessage());
		}

		$this->assertSame('Aanvrager: Jan Jansen', $this->files[11]['content']);
		$this->assertSame(['aanvraag.txt'], $this->liveNames());

	}//end testALockedDocumentSaysSoAndLeavesNoCopy()

	/**
	 * Without the versions app there are no earlier bytes to remove.
	 *
	 * @return void
	 */
	public function testWithoutTheVersionsAppThereIsNothingToPurge(): void {
		$this->addFile(fileId: 11, name: 'aanvraag.txt', content: 'Aanvrager: Jan Jansen');
		$this->absent = [self::VERSION_MANAGER];

		$outcome = $this->eraser()->erase(file: $this->fileNode(fileId: 11), values: [self::PERSON]);

		$this->assertSame(['versionsDeleted' => 0], $outcome);
		$this->assertSame('Aanvrager: ' . SubjectErasureEraser::MARK, $this->files[11]['content']);

	}//end testWithoutTheVersionsAppThereIsNothingToPurge()

	/**
	 * A file without an owner is erased, but the step fails loudly because
	 * its earlier versions, which still hold the person, cannot be found.
	 *
	 * @return void
	 */
	public function testAnOwnerlessFileSaysItsVersionsStayed(): void {
		$this->addFile(fileId: 11, name: 'aanvraag.txt', content: 'Aanvrager: Jan Jansen');
		$this->ownerless = [11];

		$this->expectExceptionMessage('the file has no owner, so its earlier versions could not be removed');
		$this->eraser()->erase(file: $this->fileNode(fileId: 11), values: [self::PERSON]);

	}//end testAnOwnerlessFileSaysItsVersionsStayed()

	/**
	 * A final document whose successor cannot be written is left final and
	 * untouched, and no draft record is made.
	 *
	 * @return void
	 */
	public function testAFinalWhoseSuccessorCannotBeWrittenIsLeftAlone(): void {
		$this->addFile(fileId: 11, name: 'besluit', content: 'Besluit op de aanvraag van Jan Jansen');
		$this->rows['documentVersion']['final-11'] = ['fileId' => 11, 'versionLabel' => '', 'status' => 'final', 'documentName' => 'besluit'];
		$eraser = $this->eraser();
		$node = $this->fileNode(fileId: 11);
		$this->refusedNewFiles = '/ erased/';

		try {
			$eraser->eraseFinal(file: $node, values: [self::PERSON], record: ['uuid' => 'final-11'] + $this->rows['documentVersion']['final-11'], requestUuid: 'req-1');
			$this->fail('A final document was changed without a successor.');
		} catch (RuntimeException $e) {
			$this->assertStringContainsString('could not be written beside the final document', $e->getMessage());
		}

		$this->assertSame('Besluit op de aanvraag van Jan Jansen', $this->files[11]['content']);
		$this->assertCount(1, $this->rows['documentVersion']);

	}//end testAFinalWhoseSuccessorCannotBeWrittenIsLeftAlone()

	/**
	 * A final document without an extension gets a successor named after it,
	 * and the superseded record is still a valid documentVersion.
	 *
	 * @return void
	 */
	public function testAFinalWithoutAnExtensionGetsANamedSuccessor(): void {
		$this->addFile(fileId: 11, name: 'besluit', content: 'Besluit op de aanvraag van Jan Jansen');
		$this->rows['documentVersion']['final-11'] = ['fileId' => 11, 'versionLabel' => '', 'status' => 'final', 'documentName' => 'besluit'];

		$outcome = $this->eraser()->eraseFinal(file: $this->fileNode(fileId: 11), values: [self::PERSON], record: ['uuid' => 'final-11'] + $this->rows['documentVersion']['final-11'], requestUuid: 'req-1');

		$this->assertSame('besluit erased', $this->files[$outcome['newVersionFileId']]['name']);
		$superseded = $this->rows['documentVersion']['final-11'];
		$this->assertSame('2026-09-29T10:00:00+00:00', $superseded['erasedAt']);
		$this->assertSame('req-1', $superseded['erasureRequest']);
		$this->assertValidAgainstSchema(schema: 'documentVersion', payload: $superseded);

	}//end testAFinalWithoutAnExtensionGetsANamedSuccessor()

	/**
	 * A final whose own content cannot be erased after the successor exists
	 * says so; the successor already holds the erased content.
	 *
	 * @return void
	 */
	public function testAFinalThatCannotBeOverwrittenSaysSo(): void {
		$this->addFile(fileId: 11, name: 'besluit.txt', content: 'Besluit op de aanvraag van Jan Jansen');
		$this->rows['documentVersion']['final-11'] = ['fileId' => 11, 'versionLabel' => '', 'status' => 'final', 'documentName' => 'besluit.txt'];
		$this->unwritable = [11];

		try {
			$this->eraser()->eraseFinal(file: $this->fileNode(fileId: 11), values: [self::PERSON], record: ['uuid' => 'final-11'] + $this->rows['documentVersion']['final-11'], requestUuid: 'req-1');
			$this->fail('A final that kept the person was reported erased.');
		} catch (RuntimeException $e) {
			$this->assertStringContainsString('superseded version\'s content could not be erased', $e->getMessage());
		}

		$this->assertContains('besluit erased.txt', $this->liveNames());
		$this->assertNotContains('.filinq-erasure-', array_map(static fn (string $n): string => substr($n, 0, 16), $this->liveNames()));

	}//end testAFinalThatCannotBeOverwrittenSaysSo()
}//end class
