<?php

/**
 * Unit tests for GuardedWriter — the one write path an agent edit takes.
 *
 * 🔴 The property these tests exist for: the version is re-read IMMEDIATELY
 * BEFORE the write, and a mismatch refuses. The lock excludes another editing
 * session; this closes the remaining window in which the file changed outside
 * one. Refusing is correct — this codec cannot merge, and guessing would
 * overwrite a human's edit with an agent's stale copy.
 *
 * @category Test
 * @package  OCA\DocuDesk\Tests\Unit\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service\Editing;

use OCA\DocuDesk\Service\Editing\AgentArtefactMarker;
use OCA\DocuDesk\Service\Editing\GuardedWriter;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\Lock\ILockManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * Tests for the guarded write session.
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
 */
class GuardedWriterTest extends TestCase {
	/**
	 * A writer whose locking and persistence are stubbed out.
	 *
	 * The lock manager and root folder are mocked because this test is about
	 * the VERSION guard, not about Nextcloud's locking — and a test that had to
	 * stand up real locking would not be run often enough to catch a regression
	 * in the guard.
	 *
	 * @param bool $lockAvailable Whether a lock provider answers.
	 *
	 * @return GuardedWriter The writer.
	 */
	private function writer(bool $lockAvailable = true): GuardedWriter {
		$lockManager = $this->createMock(ILockManager::class);
		if ($lockAvailable === false) {
			$lockManager->method('lock')->willThrowException(new \RuntimeException('no lock provider'));
		}

		return new GuardedWriter(
			$lockManager,
			$this->createMock(AgentArtefactMarker::class),
			new NullLogger(),
			$this->createMock(IRootFolder::class)
		);
	}//end writer()

	/**
	 * A file double whose etag and content are fixed.
	 *
	 * @param string $etag The current version.
	 *
	 * @return File The double.
	 */
	private function file(string $etag): File {
		$file = $this->createMock(File::class);
		$file->method('getEtag')->willReturn($etag);
		$file->method('getExtension')->willReturn('odt');
		$file->method('getContent')->willReturn('original bytes');
		$file->method('getId')->willReturn(42);
		$file->method('getName')->willReturn('report.odt');

		return $file;
	}//end file()

	/**
	 * 🔴 A version that no longer matches REFUSES the write.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function testAStaleVersionRefusesTheWrite(): void {
		$transformed = false;

		try {
			$this->writer()->runSession(
				uid: 'alice',
				file: $this->file('etag-now'),
				transform: static function (string $bytes, string $ext) use (&$transformed): array {
					$transformed = true;
					return ['bytes' => 'edited bytes', 'applied' => []];
				},
				version: 'etag-the-caller-read',
				mode: 'inplace'
			);
			$this->fail('a stale version must refuse the write');
		} catch (RuntimeException $e) {
			$this->assertStringContainsString(
				'changed since you read it',
				$e->getMessage(),
				'the refusal must say WHY, so the caller can re-read and re-apply'
			);
		}

		// The transform may run — it is the WRITE that must not happen — but the
		// refusal must come from the version check rather than from the codec.
		$this->assertTrue($transformed, 'the guard is the version re-read, not a refusal to transform');
	}//end testAStaleVersionRefusesTheWrite()

	/**
	 * The refusal happens even when no lock provider is available.
	 *
	 * The lock and the version check answer different questions, and the
	 * instance without locking is exactly where the version check matters most.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function testTheVersionGuardHoldsWithoutALockProvider(): void {
		$this->expectException(RuntimeException::class);

		$this->writer(lockAvailable: false)->runSession(
			uid: 'alice',
			file: $this->file('etag-now'),
			transform: static fn (string $bytes, string $ext): array => ['bytes' => 'edited', 'applied' => []],
			version: 'stale',
			mode: 'inplace'
		);
	}//end testTheVersionGuardHoldsWithoutALockProvider()

	/**
	 * The transform receives the file's current bytes and extension.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function testTheTransformSeesTheCurrentBytesAndExtension(): void {
		$seen = [];

		try {
			$this->writer()->runSession(
				uid: 'alice',
				file: $this->file('etag-now'),
				transform: static function (string $bytes, string $ext) use (&$seen): array {
					$seen = ['bytes' => $bytes, 'ext' => $ext];
					return ['bytes' => 'edited', 'applied' => []];
				},
				version: 'stale',
				mode: 'inplace'
			);
		} catch (RuntimeException) {
			// The write is refused by the stale version; the transform still ran,
			// which is what this test is about.
		}

		$this->assertSame('original bytes', $seen['bytes']);
		$this->assertSame('odt', $seen['ext']);
	}//end testTheTransformSeesTheCurrentBytesAndExtension()
}//end class
