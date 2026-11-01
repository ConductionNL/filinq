<?php

/**
 * Whose files a merge reads, traced per acting user.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-merge/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Exception\MergeRefusedException;
use OCA\Filinq\Service\DocumentMergeService;
use OCA\Filinq\Service\MergeCoverRenderer;
use OCA\Filinq\Service\MergeJobRepository;
use OCA\Filinq\Service\Pdfa3ConversionService;
use OCA\Filinq\Service\PdfConversionService;
use OCA\Filinq\Service\PdfDocumentFactory;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Each request's folder lookups and outcome, compared with JSON recorded from
 * the implementation before resolveInputs() was split.
 *
 * anna is the session user and reads files 1 and 2; bob reads file 3;
 * mallory, who only ever appears as a forged value, reads every file.
 *
 * @covers \OCA\Filinq\Service\DocumentMergeService
 * @uses   \OCA\Filinq\Exception\MergeRefusedException
 * @uses   \OCA\Filinq\Service\MergeSelection
 * @uses   \OCA\Filinq\Service\MergePlan
 * @uses   \OCA\Filinq\Service\MergeRun
 */
class DocumentMergeInputsTraceTest extends TestCase {

	/** @var array<int, string> The users whose folder was opened, in order. */
	private array $opened = [];

	/** @var array<int, string> The names of the files handed to conversion, in order. */
	private array $converted = [];

	/**
	 * Requests: the entry point, the session user, the inputs and the options or job.
	 *
	 * @return array<string, array{0: string, 1: string|null, 2: array<int, mixed>, 3: array<string, mixed>}>
	 */
	public static function requests(): array {
		$forged = ['owner' => 'mallory', 'requestedBy' => 'mallory', 'userId' => 'mallory'];
		return [
			'anna queues her own files' => ['queue', 'anna', [['fileId' => 1, 'label' => 'Brief'], ['fileId' => 2]], []],
			'anna forges an owner in the options' => ['queue', 'anna', [['fileId' => 1], ['fileId' => 3]], $forged],
			'anna forges an owner on an input' => ['queue', 'anna', [['fileId' => 3] + $forged], []],
			'anna merges her own files' => ['merge', 'anna', [['fileId' => 2], ['fileId' => 1, 'label' => '  ']], $forged],
			'anna merges a file of bob' => ['merge', 'anna', [['fileId' => 1], ['fileId' => 3]], []],
			'nobody is logged in' => ['queue', null, [['fileId' => 1]], $forged],
			'nobody, and no inputs' => ['queue', null, [], []],
			'no inputs' => ['queue', 'anna', [], $forged],
			'an input without a file' => ['queue', 'anna', [['fileId' => 1], ['label' => 'x']], []],
			'a zero file id' => ['queue', 'anna', [['fileId' => 0]], []],
			'a negative file id' => ['queue', 'anna', [['fileId' => -1]], []],
			'a file reachable twice keeps the first node' => ['queue', 'anna', [['fileId' => 4]], []],
			'a merge of a file reachable twice converts the first node' => ['merge', 'anna', [['fileId' => 4, 'label' => 'Eigen']], []],
			'a string file id' => ['queue', 'anna', [['fileId' => '2', 'label' => 7]], []],
			'a missing file before a bad one' => ['queue', 'anna', [['fileId' => 9], ['fileId' => 0]], []],
			'a bad one before a missing file' => ['queue', 'anna', [['fileId' => 0], ['fileId' => 9]], []],
			'bob resumes his job while anna is logged in' => ['resume', 'anna', [['fileId' => 3]], ['requestedBy' => 'bob', 'owner' => 'mallory']],
			'a job of bob naming a file of anna' => ['resume', 'anna', [['fileId' => 1]], ['requestedBy' => 'bob']],
			'a job without a requester falls back to the session' => ['resume', 'anna', [['fileId' => 1]], ['owner' => 'mallory']],
			'a job without a requester and no session' => ['resume', null, [['fileId' => 1]], ['owner' => 'mallory']],
			'a job with an empty requester' => ['resume', 'anna', [['fileId' => 1]], ['requestedBy' => '']],
		];
	}//end requests()

	/**
	 * The folder lookups and the outcome match the recorded ones.
	 *
	 * @param string $entry queue, merge or resume.
	 * @param string|null $session The session user, null when nobody is logged in.
	 * @param array<int, mixed> $inputs The requested inputs.
	 * @param array<string, mixed> $extra The options (queue, merge) or the job fields (resume).
	 *
	 * @dataProvider requests
	 *
	 * @return void
	 */
	public function testTraceMatchesTheRecordedOne(string $entry, ?string $session, array $inputs, array $extra): void {
		$service = $this->service(session: $session);

		try {
			$job = match ($entry) {
				'queue' => $service->queue(inputs: $inputs, options: $extra),
				'merge' => $service->merge(inputs: $inputs, options: $extra),
				'resume' => $service->resume(job: ['uuid' => 'merge-1', 'inputs' => $inputs, 'options' => []] + $extra),
			};
			$outcome = ['status' => $job['status'], 'inputs' => $job['inputs'], 'requestedBy' => ($job['requestedBy'] ?? null)];
		} catch (MergeRefusedException $e) {
			$outcome = ['refused' => [$e->getStatus(), $e->getMessage()]];
		}

		$result = ['opened' => $this->opened, 'converted' => $this->converted, 'outcome' => $outcome];

		$path = __DIR__ . '/golden/document-merge-inputs-trace.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $result;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, $result);
		$this->assertNotContains('mallory', $this->opened, 'A forged owner must never decide whose files are read.');
	}//end testTraceMatchesTheRecordedOne()

	/**
	 * The service; each user's folder answers only that user's files.
	 *
	 * @param string|null $session The session user.
	 *
	 * @return DocumentMergeService The service under test.
	 */
	private function service(?string $session): DocumentMergeService {
		$parent = $this->createMock(Folder::class);
		$parent->method('isCreatable')->willReturn(true);

		$readable = ['anna' => [1 => 'brief.pdf', 2 => 'bijlage.pdf', 4 => 'eigen.pdf'], 'bob' => [3 => 'bob.pdf'], 'mallory' => [1 => 'brief.pdf', 2 => 'bijlage.pdf', 3 => 'bob.pdf']];
		$rootFolder = $this->createMock(IRootFolder::class);
		$rootFolder->method('getUserFolder')->willReturnCallback(
			function (string $uid) use ($readable, $parent): Folder {
				$this->opened[] = $uid;
				$files = ($readable[$uid] ?? []);
				$folder = $this->createMock(Folder::class);
				$folder->method('getById')->willReturnCallback(
					function (int $fileId) use ($files, $parent): array {
						if (isset($files[$fileId]) === false) {
							return [];
						}

						if ($fileId === 4) {
							return [$this->node(fileId: 4, name: 'eigen.pdf', parent: $parent), $this->node(fileId: 4, name: 'gedeeld.pdf', parent: $parent)];
						}

						$file = $this->createMock(File::class);
						$file->method('getId')->willReturn($fileId);
						$file->method('getName')->willReturn($files[$fileId]);
						$file->method('getParent')->willReturn($parent);
						return [$file];
					}
				);
				return $folder;
			}
		);

		$conversion = $this->createMock(PdfConversionService::class);
		$conversion->method('convertToPdfReporting')->willReturnCallback(
			function (File $source): array {
				$this->converted[] = $source->getName();
				throw new RuntimeException('no conversion in this trace');
			}
		);

		$jobs = $this->createMock(MergeJobRepository::class);
		$jobs->method('create')->willReturnCallback(static fn (array $job): array => $job + ['uuid' => 'merge-1']);
		$jobs->method('save')->willReturnCallback(static fn (array $job, string $uuid): array => ['uuid' => $uuid] + $job);

		$userSession = $this->createMock(IUserSession::class);
		$user = null;
		if ($session !== null) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($session);
		}

		$userSession->method('getUser')->willReturn($user);

		return new DocumentMergeService(
			$jobs,
			$conversion,
			$this->createMock(Pdfa3ConversionService::class),
			new PdfDocumentFactory(),
			$this->createMock(MergeCoverRenderer::class),
			$rootFolder,
			$userSession,
			$this->createMock(LoggerInterface::class)
		);
	}//end service()

	/**
	 * A file node.
	 *
	 * @param int $fileId The file id.
	 * @param string $name The name.
	 * @param Folder $parent The parent folder.
	 *
	 * @return File The node.
	 */
	private function node(int $fileId, string $name, Folder $parent): File {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn($fileId);
		$file->method('getName')->willReturn($name);
		$file->method('getParent')->willReturn($parent);
		return $file;
	}//end node()
}//end class
