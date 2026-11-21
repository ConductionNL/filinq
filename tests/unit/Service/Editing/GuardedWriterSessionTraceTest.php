<?php

/**
 * Trace tests for GuardedWriter's guarded session: which lock, marker and
 * file calls it makes, in which order, what it answers and what it logs.
 *
 * Recorded on the implementation before runSession() took one GuardedEdit;
 * each scenario's trace must stay identical. Allowed outcomes (a held lock,
 * no lock provider, a lock error that is only logged) and refused ones (a
 * lock another owner holds, a stale version, a failed save) are both pinned.
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
 * @spec openspec/specs/document-editing/spec.md#requirement-an-in-place-write-is-guarded-by-the-lock-and-a-version-precondition
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Editing;

use OCA\Filinq\Service\Editing\AgentArtefactMarker;
use OCA\Filinq\Service\Editing\GuardedWriter;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Lock\ILock;
use OCP\Files\Lock\ILockManager;
use OCP\Files\Lock\LockContext;
use OCP\Files\Lock\NoLockProviderException;
use OCP\Files\Lock\OwnerLockedException;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use RuntimeException;
use Stringable;
use Throwable;

/**
 * The guarded session's trace per scenario, compared with a recorded one.
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-an-in-place-write-is-guarded-by-the-lock-and-a-version-precondition
 */
class GuardedWriterSessionTraceTest extends TestCase {
	/**
	 * What the doubles were asked, in order.
	 *
	 * @var array<int, array<int, mixed>>
	 */
	private array $calls = [];

	/**
	 * The scenarios: mode, version the caller read, and how the world answers.
	 *
	 * @return array<string, array{0: string, 1: string, 2: array<string, mixed>}> Name => [mode, version, world].
	 */
	public static function scenarios(): array {
		$current = 'sha256:' . hash('sha256', 'original bytes');
		return [
			'in place, lock held' => ['inplace', $current, []],
			'beside the source' => ['sibling', $current, []],
			'beside a source without extension' => ['sibling', $current, ['extension' => '']],
			'the sibling cannot be created' => ['sibling', $current, ['parent' => 'fail']],
			'a stale version' => ['inplace', 'sha256:stale', []],
			'an etag is never a version' => ['inplace', 'etag-1', []],
			'another owner holds the lock' => ['inplace', $current, ['lock' => 'owner']],
			'another anonymous owner holds the lock' => ['inplace', $current, ['lock' => 'anonymous']],
			'no lock provider' => ['inplace', $current, ['lock' => 'none']],
			'the lock errors' => ['inplace', $current, ['lock' => 'error']],
			'the save fails after tagging' => ['inplace', $current, ['save' => 'fail']],
			'the save fails on an already tagged file' => ['inplace', $current, ['save' => 'fail', 'marked' => false]],
			'the save fails without a lock provider' => ['inplace', $current, ['save' => 'fail', 'lock' => 'none']],
			'the transform fails' => ['inplace', $current, ['transform' => 'fail']],
			'the unlock fails' => ['inplace', $current, ['unlock' => 'fail']],
			'the file is unreadable' => ['inplace', $current, ['content' => '']],
			'the user folder cannot be reached' => ['inplace', $current, ['userFolder' => 'fail']],
		];
	}//end scenarios()

	/**
	 * The trace matches the recorded one.
	 *
	 * @param string               $mode    The requested output mode.
	 * @param string               $version The version the caller read.
	 * @param array<string, mixed> $world   How the doubles answer.
	 *
	 * @return void
	 *
	 * @dataProvider scenarios
	 */
	public function testTrace(string $mode, string $version, array $world): void {
		$logger = new class extends AbstractLogger {
			/**
			 * The lines logged.
			 *
			 * @var array<int, array<int, mixed>>
			 */
			public array $lines = [];

			/**
			 * Record one line.
			 *
			 * @param mixed             $level   The level.
			 * @param string|Stringable $message The message.
			 * @param array             $context The context.
			 *
			 * @return void
			 */
			public function log($level, string|Stringable $message, array $context = []): void {
				$this->lines[] = [$level, (string)$message];
			}
		};

		$writer = new GuardedWriter($this->lockManager($world), $this->marker($world), $logger, $this->rootFolder($world));
		$transform = function (string $bytes, string $extension) use ($world): array {
			$this->calls[] = ['transform', $bytes, $extension];
			if (($world['transform'] ?? '') === 'fail') {
				throw new RuntimeException('codec refused');
			}

			return ['bytes' => 'edited bytes', 'applied' => ['a1'], 'written' => ['title']];
		};

		try {
			$outcome = ['result' => $this->runSession(writer: $writer, session: [$mode, $version, $this->file($world), $transform])];
		} catch (Throwable $e) {
			$outcome = ['exception' => [$e::class, $e->getMessage(), ($e->getPrevious() !== null ? $e->getPrevious()::class : null)]];
		}

		$trace = ['outcome' => $outcome, 'calls' => $this->calls, 'logs' => $logger->lines];

		$path = __DIR__ . '/golden/guarded-writer-session-trace.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $trace;
			if (is_dir(dirname($path)) === false) {
				mkdir(dirname($path), 0777, true);
			}

			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, json_decode((string)json_encode($trace), true));
	}//end testTrace()

	/**
	 * Run one guarded session the way the editing service does.
	 *
	 * @param GuardedWriter     $writer  The writer.
	 * @param array<int, mixed> $session Mode, version, file and transform.
	 *
	 * @return array<string, mixed> The write result.
	 */
	private function runSession(GuardedWriter $writer, array $session): array {
		return $writer->runSession(
			uid: 'anna',
			file: $session[2],
			transform: $session[3],
			version: $session[1],
			mode: $session[0]
		);
	}//end runSession()

	/**
	 * A lock manager that records and answers as the world says.
	 *
	 * @param array<string, mixed> $world How the doubles answer.
	 *
	 * @return ILockManager The double.
	 */
	private function lockManager(array $world): ILockManager {
		$locks = $this->createMock(ILockManager::class);
		$locks->method('lock')->willReturnCallback(
			function (LockContext $context) use ($world): ILock {
				$this->calls[] = ['lock', $context->getType(), $context->getOwner()];
				$this->throwLockFailure(kind: (string)($world['lock'] ?? ''));
				return $this->createMock(ILock::class);
			}
		);
		$locks->method('runInScope')->willReturnCallback(
			function (LockContext $context, callable $callback): void {
				$this->calls[] = ['runInScope', $context->getType()];
				$callback();
			}
		);
		$locks->method('unlock')->willReturnCallback(
			function (LockContext $context) use ($world): void {
				$this->calls[] = ['unlock', $context->getType()];
				if (($world['unlock'] ?? '') === 'fail') {
					throw new RuntimeException('unlock refused');
				}
			}
		);
		return $locks;
	}//end lockManager()

	/**
	 * Throw what the lock manager throws in this world, if anything.
	 *
	 * @param string $kind owner, anonymous, none, error or ''.
	 *
	 * @return void
	 */
	private function throwLockFailure(string $kind): void {
		if ($kind === 'owner' || $kind === 'anonymous') {
			$held = $this->createMock(ILock::class);
			$held->method('getOwner')->willReturn($kind === 'owner' ? 'bob' : '');
			throw new OwnerLockedException($held);
		}

		if ($kind === 'none') {
			throw new NoLockProviderException();
		}

		if ($kind === 'error') {
			throw new RuntimeException('lock backend down');
		}
	}//end throwLockFailure()

	/**
	 * A marker that records and answers whether it added the tag.
	 *
	 * @param array<string, mixed> $world How the doubles answer.
	 *
	 * @return AgentArtefactMarker The double.
	 */
	private function marker(array $world): AgentArtefactMarker {
		$marker = $this->createMock(AgentArtefactMarker::class);
		$marker->method('mark')->willReturnCallback(
			function (int $fileId) use ($world): bool {
				$this->calls[] = ['mark', $fileId];
				return ($world['marked'] ?? true);
			}
		);
		$marker->method('unmark')->willReturnCallback(
			function (int $fileId): void {
				$this->calls[] = ['unmark', $fileId];
			}
		);
		return $marker;
	}//end marker()

	/**
	 * A root folder whose user folder answers a relative path, or fails.
	 *
	 * @param array<string, mixed> $world How the doubles answer.
	 *
	 * @return IRootFolder The double.
	 */
	private function rootFolder(array $world): IRootFolder {
		$root = $this->createMock(IRootFolder::class);
		if (($world['userFolder'] ?? '') === 'fail') {
			$root->method('getUserFolder')->willThrowException(new RuntimeException('no user folder'));
			return $root;
		}

		$folder = $this->createMock(Folder::class);
		$folder->method('getRelativePath')->willReturnCallback(static fn (string $path): string => '/rel' . $path);
		$root->method('getUserFolder')->willReturn($folder);
		return $root;
	}//end rootFolder()

	/**
	 * The source file, with a parent that can create the sibling.
	 *
	 * @param array<string, mixed> $world How the doubles answer.
	 *
	 * @return File The double.
	 */
	private function file(array $world): File {
		$extension = (string)($world['extension'] ?? 'odt');
		$name = 'report' . ($extension !== '' ? '.' . $extension : '');
		$file = $this->namedFile(id: 42, name: $name, world: $world);
		$file->method('getExtension')->willReturn($extension);
		$file->method('getSize')->willReturn(14);
		$file->method('getContent')->willReturn((string)($world['content'] ?? 'original bytes'));
		$file->method('getParent')->willReturnCallback(fn (): Folder => $this->parent(world: $world));
		return $file;
	}//end file()

	/**
	 * A writable file double with an id and a name.
	 *
	 * @param int                  $id    Its id.
	 * @param string               $name  Its name.
	 * @param array<string, mixed> $world How the doubles answer.
	 *
	 * @return File&\PHPUnit\Framework\MockObject\MockObject The double.
	 */
	private function namedFile(int $id, string $name, array $world): File {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn($id);
		$file->method('getName')->willReturn($name);
		$file->method('getPath')->willReturn('/anna/files/' . $name);
		$file->method('putContent')->willReturnCallback(
			function (string $bytes) use ($id, $world): void {
				$this->calls[] = ['putContent', $id, $bytes];
				if (($world['save'] ?? '') === 'fail') {
					throw new RuntimeException('disk full');
				}
			}
		);
		return $file;
	}//end namedFile()

	/**
	 * The source's parent folder.
	 *
	 * @param array<string, mixed> $world How the doubles answer.
	 *
	 * @return Folder The double.
	 */
	private function parent(array $world): Folder {
		$this->calls[] = ['getParent'];
		$parent = $this->createMock(Folder::class);
		$parent->method('getNonExistingName')->willReturnCallback(
			function (string $name): string {
				$this->calls[] = ['getNonExistingName', $name];
				return $name;
			}
		);
		$parent->method('newFile')->willReturnCallback(
			function (string $name) use ($world): File {
				$this->calls[] = ['newFile', $name];
				if (($world['parent'] ?? '') === 'fail') {
					throw new RuntimeException('folder is read-only');
				}

				return $this->namedFile(id: 43, name: $name, world: $world);
			}
		);
		return $parent;
	}//end parent()
}//end class
