<?php

/**
 * Format refusals, decided from the BYTES rather than the file name.
 *
 * @category Test
 * @package  OCA\DocuDesk\Tests\Unit\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://docudesk.app
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#3-refusals
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service\Editing;

use OCA\DocuDesk\Service\DocumentObjectServiceResolver;
use OCA\DocuDesk\Service\Editing\DocumentGuard;
use OCP\Files\File;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for DocumentGuard::formatRefusal().
 */
class DocumentGuardFormatTest extends TestCase {

	private DocumentGuard $guard;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		$this->guard = new DocumentGuard(
			$this->createMock(DocumentObjectServiceResolver::class),
			$this->createMock(LoggerInterface::class)
		);
	}//end setUp()

	/**
	 * A file stub with the given mime and package contents.
	 *
	 * @param string             $mime    The mime type.
	 * @param array<int, string> $entries Zip entry names, empty for a non-zip.
	 *
	 * @return File The stub.
	 */
	private function file(string $mime, array $entries = []): File {
		$bytes = '';
		if ($entries !== []) {
			$path = tempnam(sys_get_temp_dir(), 'gz');
			$zip = new \ZipArchive();
			$zip->open($path, \ZipArchive::OVERWRITE);
			foreach ($entries as $entry) {
				$zip->addFromString($entry, 'x');
			}

			$zip->close();
			$bytes = (string)file_get_contents($path);
			unlink($path);
		}

		$file = $this->createMock(File::class);
		$file->method('getMimeType')->willReturn($mime);
		$file->method('getContent')->willReturn($bytes);

		return $file;
	}//end file()

	/**
	 * CONTROL: an ordinary docx is NOT refused. Without this, every assertion
	 * below passes on a guard that refuses everything.
	 *
	 * @return void
	 */
	public function testAnOrdinaryDocxIsNotRefused(): void {
		$file = $this->file(
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			['word/document.xml']
		);

		$this->assertNull($this->guard->formatRefusal(file: $file));
	}//end testAnOrdinaryDocxIsNotRefused()

	/**
	 * 🔴 The case a name-based check waves through: a package carrying VBA
	 * while claiming to be a plain .docx. The refusal reads the bytes.
	 *
	 * @return void
	 */
	public function testAMacroPackageRenamedToDocxIsStillRefused(): void {
		$file = $this->file(
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			['word/document.xml', 'word/vbaProject.bin']
		);

		$refusal = $this->guard->formatRefusal(file: $file);

		$this->assertNotNull($refusal);
		$this->assertStringContainsString('macro', strtolower((string)$refusal));
		$this->assertStringContainsString('bytes, not the file name', (string)$refusal);
	}//end testAMacroPackageRenamedToDocxIsStillRefused()

	/**
	 * A PDF is refused for CONTENT editing; annotation and form-fill are a
	 * different capability.
	 *
	 * @return void
	 */
	public function testPdfContentEditingIsRefused(): void {
		$refusal = $this->guard->formatRefusal(file: $this->file('application/pdf'));

		$this->assertNotNull($refusal);
		$this->assertStringContainsString('final-form', (string)$refusal);
	}//end testPdfContentEditingIsRefused()

	/**
	 * A database is not a document.
	 *
	 * @return void
	 */
	public function testOdbIsRefused(): void {
		$refusal = $this->guard->formatRefusal(
			file: $this->file('application/vnd.oasis.opendocument.database')
		);

		$this->assertNotNull($refusal);
		$this->assertStringContainsString('database', (string)$refusal);
	}//end testOdbIsRefused()

	/**
	 * ⚠️ A package that cannot be inspected is treated as macro-bearing. For a
	 * code-execution check, "I could not tell" must not resolve to "safe".
	 *
	 * @return void
	 */
	public function testAnUnreadablePackageIsTreatedAsMacroBearing(): void {
		$file = $this->createMock(File::class);
		$file->method('getMimeType')->willReturn(
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
		);
		$file->method('getContent')->willThrowException(new \RuntimeException('storage gone'));

		$this->assertNotNull($this->guard->formatRefusal(file: $file));
	}//end testAnUnreadablePackageIsTreatedAsMacroBearing()
}//end class
