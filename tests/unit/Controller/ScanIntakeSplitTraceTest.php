<?php
/**
 * Trace tests for cutting a scanned batch: the answer and every collaborator call.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\ScanIntakeController;
use OCA\Filinq\Kernel\Contracts\ScanBatchRepositoryInterface;
use OCA\Filinq\Kernel\Contracts\ScanBatchServiceInterface;
use OCA\Filinq\Kernel\Contracts\ScanProfileServiceInterface;
use OCA\Filinq\Kernel\Contracts\SeparatorSheetServiceInterface;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Every path through split(), compared with JSON recorded from the
 * implementation before it was split into helpers.
 *
 * @covers \OCA\Filinq\Controller\ScanIntakeController
 * @uses   \OCA\Filinq\Service\ScanBatchCut
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 */
class ScanIntakeSplitTraceTest extends TestCase {

	/**
	 * The calls the collaborators saw.
	 *
	 * @var array<int, array<int, mixed>>
	 */
	private array $calls = [];

	/**
	 * The scenarios: the profile id asked for, and how the world answers.
	 *
	 * @return array<string, array{0: string, 1: array<string, mixed>}>
	 */
	public static function scenarios(): array {
		return [
			'nobody logged in' => ['', ['user' => null]],
			'no such batch' => ['', ['node' => 'none']],
			'a folder id' => ['', ['node' => 'folder']],
			'no profile watches the folder' => ['', ['forPath' => null]],
			'the folder decides the profile' => ['', []],
			'a named profile' => ['  scan-a ', []],
			'a blank profile name' => ['   ', []],
			'a named profile that does not exist' => ['scan-x', ['find' => null]],
			'a batch already received' => ['', ['batch' => ['id' => 'batch-1', 'status' => 'received']]],
			'the user folder fails' => ['', ['userFolder' => 'throw']],
			'the profile store fails' => ['', ['forPath' => 'throw']],
			'the batch has no parent' => ['', ['parent' => 'throw']],
			'the cut fails' => ['', ['cut' => 'throw']],
		];
	}//end scenarios()

	/**
	 * The answer and the calls match the recorded ones.
	 *
	 * @param string               $profileId The profile id asked for.
	 * @param array<string, mixed> $world     How the collaborators answer.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testTrace(string $profileId, array $world): void {
		$response = $this->controller($world)->split(fileId: 42, profileId: $profileId);
		$trace = ['status' => $response->getStatus(), 'data' => $response->getData(), 'calls' => $this->calls];

		$path = __DIR__ . '/golden/scan-intake-split-trace.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $trace;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, $trace);
	}//end testTrace()

	/**
	 * The controller over recording doubles.
	 *
	 * @param array<string, mixed> $world How the collaborators answer.
	 *
	 * @return ScanIntakeController
	 */
	private function controller(array $world): ScanIntakeController {
		$folder = $this->createMock(Folder::class);
		$folder->method('getPath')->willReturn('/beheerder/files/Scans');

		$file = $this->createMock(File::class);
		$file->method('getPath')->willReturn('/beheerder/files/Scans/batch.pdf');
		if (($world['parent'] ?? '') === 'throw') {
			$file->method('getParent')->willThrowException(new RuntimeException('no parent'));
		} else {
			$file->method('getParent')->willReturn($folder);
		}

		$nodes = ['file' => [$file], 'folder' => [$folder], 'none' => []][$world['node'] ?? 'file'];
		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getById')->willReturnCallback(
			function (int $id) use ($nodes): array {
				$this->calls[] = ['getById', $id];
				return $nodes;
			}
		);

		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->willReturnCallback(
			function (string $uid) use ($userFolder, $world): Folder {
				$this->calls[] = ['getUserFolder', $uid];
				if (($world['userFolder'] ?? '') === 'throw') {
					throw new RuntimeException('storage offline');
				}

				return $userFolder;
			}
		);

		$session = $this->createMock(IUserSession::class);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('beheerder');
		$session->method('getUser')->willReturn(array_key_exists('user', $world) === true ? null : $user);

		return new ScanIntakeController(
			'filinq',
			$this->createMock(IRequest::class),
			$this->createMock(SeparatorSheetServiceInterface::class),
			$this->profiles($world),
			$this->batches($world),
			$this->store($world),
			$root,
			$session
		);
	}//end controller()

	/**
	 * A profile store that records what it was asked.
	 *
	 * @param array<string, mixed> $world How it answers.
	 *
	 * @return ScanProfileServiceInterface
	 */
	private function profiles(array $world): ScanProfileServiceInterface {
		$profile = ['id' => 'scan-a', 'folder' => '/Scans'];
		$profiles = $this->createMock(ScanProfileServiceInterface::class);
		$profiles->method('find')->willReturnCallback(
			function (string $id) use ($world, $profile): ?array {
				$this->calls[] = ['find', $id];
				return array_key_exists('find', $world) === true ? $world['find'] : $profile;
			}
		);
		$profiles->method('forPath')->willReturnCallback(
			function (string $path) use ($world, $profile): ?array {
				$this->calls[] = ['forPath', $path];
				if (($world['forPath'] ?? '') === 'throw') {
					throw new RuntimeException('profile store down');
				}

				return array_key_exists('forPath', $world) === true ? $world['forPath'] : $profile;
			}
		);
		return $profiles;
	}//end profiles()

	/**
	 * A batch store that records what it was asked.
	 *
	 * @param array<string, mixed> $world How it answers.
	 *
	 * @return ScanBatchRepositoryInterface
	 */
	private function store(array $world): ScanBatchRepositoryInterface {
		$store = $this->createMock(ScanBatchRepositoryInterface::class);
		$store->method('findByFile')->willReturnCallback(
			function (int $fileId) use ($world): ?array {
				$this->calls[] = ['findByFile', $fileId];
				return $world['batch'] ?? null;
			}
		);
		return $store;
	}//end store()

	/**
	 * A splitter that records what it was asked.
	 *
	 * @param array<string, mixed> $world How it answers.
	 *
	 * @return ScanBatchServiceInterface
	 */
	private function batches(array $world): ScanBatchServiceInterface {
		$batches = $this->createMock(ScanBatchServiceInterface::class);
		$batches->method('receive')->willReturnCallback(
			function (File $file, array $profile): array {
				$this->calls[] = ['receive', $file->getPath(), $profile];
				return ['id' => 'batch-new', 'status' => 'received'];
			}
		);
		$batches->method('split')->willReturnCallback(
			function (array $batch, array $profile, File $file, Folder $target) use ($world): array {
				$this->calls[] = ['split', $batch, $profile, $file->getPath(), $target->getPath()];
				if (($world['cut'] ?? '') === 'throw') {
					throw new RuntimeException('unreadable page 3');
				}

				return ['batch' => $batch['id'], 'segments' => 2];
			}
		);
		return $batches;
	}//end batches()
}//end class
