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
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
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

namespace OCA\Filinq\Tests\Unit\Service\Editing;

use OCA\Filinq\Service\Editing\AgentArtefactMarker;
use OCA\Filinq\Service\Editing\GuardedWriter;
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
	/**
	 * 🔴 THE CASE THE OLD GUARD COULD NOT SEE. The etag is unchanged and the
	 * CONTENT is not, and the write must still be refused.
	 *
	 * This is what happened in CI on 2026-09-07. A docx was read at etag
	 * `e6748d59…`, edited, and re-read in a fresh request with visibly
	 * different text under the SAME etag. The next edit presented the original
	 * etag, `$file->getEtag() !== $version` compared equal, and an edit built
	 * on text that no longer existed overwrote the intervening one.
	 *
	 * Every existing test above passed throughout, because each hands the
	 * comparison two literally different strings. That is a test of `!==`, not
	 * of the property, and it is why the guard could be inert for as long as it
	 * was. This one pins the property: the token names the BYTES.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-an-in-place-write-is-guarded-by-the-lock-and-a-version-precondition
	 */
	public function testAnUnchangedEtagOverChangedContentStillRefuses(): void {
		$writer = $this->writer();

		// What the caller read, and what it will present.
		$read = $writer->versionOf(packageBytes: 'original bytes');

		// The file now holds different bytes under an etag that never moved.
		// The etag is set to the token the caller presents, which is what an
		// etag-based guard compares and finds equal: without that, this double
		// would refuse for the wrong reason and the test would pass against
		// the defect it is here to catch.
		$file = $this->createMock(File::class);
		$file->method('getEtag')->willReturn($read);
		$file->method('getExtension')->willReturn('odt');
		$file->method('getContent')->willReturn('somebody else wrote this');
		$file->method('getId')->willReturn(42);
		$file->method('getName')->willReturn('report.odt');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/changed since you read it/');

		$writer->runSession(
			uid: 'alice',
			file: $file,
			transform: static fn (string $bytes, string $ext): array => ['bytes' => 'edited', 'applied' => []],
			version: $read,
			mode: 'inplace'
		);

	}//end testAnUnchangedEtagOverChangedContentStillRefuses()

	/**
	 * The matching case, so the test above is not green merely because the
	 * guard refuses everything: a token that names the current bytes is
	 * accepted, and the answer carries a token for what was written.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-an-in-place-write-is-guarded-by-the-lock-and-a-version-precondition
	 */
	public function testACurrentVersionIsAcceptedAndAnswersWithTheNewOne(): void {
		$writer = $this->writer();

		$result = $writer->runSession(
			uid: 'alice',
			file: $this->file('etag-now'),
			transform: static fn (string $bytes, string $ext): array => ['bytes' => 'edited bytes', 'applied' => []],
			version: $writer->versionOf(packageBytes: 'original bytes'),
			mode: 'inplace'
		);

		// The token answers for the bytes that were WRITTEN. It used to be
		// re-read from the node, which `putContent()` does not refresh, so the
		// caller was handed the version of the document it had just replaced.
		$this->assertSame($writer->versionOf(packageBytes: 'edited bytes'), $result['version']);

	}//end testACurrentVersionIsAcceptedAndAnswersWithTheNewOne()
}//end class
