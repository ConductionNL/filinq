<?php

/**
 * Document Comparison Service Test
 *
 * @category  Test
 * @package   OCA\DocuDesk\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/document-comparison/specs/document-comparison/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Exception\ComparisonException;
use OCA\DocuDesk\Service\DocumentComparisonService;
use OCP\App\IAppManager;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for DocumentComparisonService.
 *
 * @category Test
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class DocumentComparisonServiceTest extends TestCase {
	/**
	 * Logger mock.
	 *
	 * @var LoggerInterface|MockObject
	 */
	private LoggerInterface|MockObject $logger;

	/**
	 * Root folder mock.
	 *
	 * @var IRootFolder|MockObject
	 */
	private IRootFolder|MockObject $rootFolder;

	/**
	 * User session mock.
	 *
	 * @var IUserSession|MockObject
	 */
	private IUserSession|MockObject $userSession;

	/**
	 * App config mock.
	 *
	 * @var IAppConfig|MockObject
	 */
	private IAppConfig|MockObject $appConfig;

	/**
	 * App manager mock.
	 *
	 * @var IAppManager|MockObject
	 */
	private IAppManager|MockObject $appManager;

	/**
	 * Container mock.
	 *
	 * @var ContainerInterface|MockObject
	 */
	private ContainerInterface|MockObject $container;

	/**
	 * User folder mock.
	 *
	 * @var Folder|MockObject
	 */
	private Folder|MockObject $userFolder;

	/**
	 * Set up common mocks.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->container = $this->createMock(ContainerInterface::class);
		$this->userFolder = $this->createMock(Folder::class);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$this->userSession->method('getUser')->willReturn($user);

		$this->rootFolder->method('getUserFolder')->willReturn($this->userFolder);
		$this->appConfig->method('getValueInt')->willReturnCallback(
			static function (string $app, string $key, int $default) {
				return $default;
			}
		);

	}//end setUp()

	/**
	 * Build the service with the configured mocks.
	 *
	 * @return DocumentComparisonService The service.
	 */
	private function service(): DocumentComparisonService {
		return new DocumentComparisonService(
			$this->logger,
			$this->rootFolder,
			$this->userSession,
			$this->appConfig,
			$this->appManager,
			$this->container
		);

	}//end service()

	/**
	 * Build a mocked text file returning given content + mime.
	 *
	 * @param int $id File id.
	 * @param string $content Content.
	 * @param string $mime Mime type.
	 *
	 * @return File|MockObject The file mock.
	 */
	private function file(int $id, string $content, string $mime = 'text/plain'): File|MockObject {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn($id);
		$file->method('getContent')->willReturn($content);
		$file->method('getMimeType')->willReturn($mime);
		return $file;
	}//end file()

	/**
	 * Wire getById() to return per-id file mocks.
	 *
	 * @param array<int, File|null> $map File id => File or null.
	 *
	 * @return void
	 */
	private function wireFiles(array $map): void {
		$this->userFolder->method('getById')->willReturnCallback(
			static function (int $id) use ($map) {
				if (isset($map[$id]) === true && $map[$id] !== null) {
					return [$map[$id]];
				}

				return [];
			}
		);

	}//end wireFiles()

	/**
	 * Two distinct readable files produce a structured diff.
	 *
	 * @return void
	 */
	public function testCompareTwoDistinctFiles(): void {
		$this->appManager->method('getInstalledApps')->willReturn([]);
		$this->wireFiles([
			42 => $this->file(42, 'the quick brown fox'),
			77 => $this->file(77, 'the slow brown fox'),
		]);

		$result = $this->service()->compare(['fileId' => 42], ['fileId' => 77]);

		$this->assertArrayHasKey('hunks', $result);
		$this->assertGreaterThan(0, $result['summary']['changedHunks']);
		$this->assertFalse($result['crossFormat']);
		foreach ($result['hunks'] as $hunk) {
			$this->assertArrayHasKey('type', $hunk);
			$this->assertArrayHasKey('left', $hunk);
			$this->assertArrayHasKey('right', $hunk);
		}

	}//end testCompareTwoDistinctFiles()

	/**
	 * Identical documents yield only unchanged hunks and zero changed.
	 *
	 * @return void
	 */
	public function testIdenticalDocumentsProduceNoChangeHunks(): void {
		$this->appManager->method('getInstalledApps')->willReturn([]);
		$this->wireFiles([
			1 => $this->file(1, 'same content here'),
			2 => $this->file(2, 'same content here'),
		]);

		$result = $this->service()->compare(['fileId' => 1], ['fileId' => 2]);

		$this->assertSame(0, $result['summary']['changedHunks']);
		foreach ($result['hunks'] as $hunk) {
			$this->assertSame('unchanged', $hunk['type']);
		}

	}//end testIdenticalDocumentsProduceNoChangeHunks()

	/**
	 * Inaccessible subject yields 404 without disclosure.
	 *
	 * @return void
	 */
	public function testInaccessibleSubjectYields404(): void {
		$this->appManager->method('getInstalledApps')->willReturn([]);
		$this->wireFiles([42 => $this->file(42, 'content')]);

		$this->expectException(ComparisonException::class);
		try {
			$this->service()->compare(['fileId' => 42], ['fileId' => 99]);
		} catch (ComparisonException $e) {
			$this->assertSame(404, $e->getStatusCode());
			throw $e;
		}

	}//end testInaccessibleSubjectYields404()

	/**
	 * Unsupported format yields 415 naming the offending subject.
	 *
	 * @return void
	 */
	public function testUnsupportedFormatYields415(): void {
		$this->appManager->method('getInstalledApps')->willReturn([]);
		$this->wireFiles([
			42 => $this->file(42, 'text', 'text/plain'),
			43 => $this->file(43, "\x00\x01binary", 'application/octet-stream'),
		]);

		try {
			$this->service()->compare(['fileId' => 42], ['fileId' => 43]);
			$this->fail('Expected ComparisonException');
		} catch (ComparisonException $e) {
			$this->assertSame(415, $e->getStatusCode());
			$this->assertSame('unsupported-format', $e->getReason());
			$this->assertStringContainsString('right', $e->getMessage());
		}

	}//end testUnsupportedFormatYields415()

	/**
	 * Oversize subject yields 413.
	 *
	 * @return void
	 */
	public function testOversizeSubjectYields413(): void {
		$this->appManager->method('getInstalledApps')->willReturn([]);
		// Override config to a tiny cap.
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->appConfig->method('getValueInt')->willReturn(8);
		$this->wireFiles([
			42 => $this->file(42, 'small'),
			43 => $this->file(43, 'this content is definitely larger than eight bytes'),
		]);

		try {
			$this->service()->compare(['fileId' => 42], ['fileId' => 43]);
			$this->fail('Expected ComparisonException');
		} catch (ComparisonException $e) {
			$this->assertSame(413, $e->getStatusCode());
		}

	}//end testOversizeSubjectYields413()

	/**
	 * Cross-format pairs set crossFormat true.
	 *
	 * @return void
	 */
	public function testCrossFormatPairIsFlagged(): void {
		$this->appManager->method('getInstalledApps')->willReturn([]);
		$this->wireFiles([
			42 => $this->file(42, 'doc content', 'text/html'),
			43 => $this->file(43, 'doc content', 'text/markdown'),
		]);

		$result = $this->service()->compare(['fileId' => 42], ['fileId' => 43]);
		$this->assertTrue($result['crossFormat']);

	}//end testCrossFormatPairIsFlagged()

	/**
	 * Whitespace differences across formats are normalised away.
	 *
	 * @return void
	 */
	public function testWhitespaceNormalisation(): void {
		$this->appManager->method('getInstalledApps')->willReturn([]);
		$this->wireFiles([
			42 => $this->file(42, "alpha   beta\n\ngamma", 'text/plain'),
			43 => $this->file(43, 'alpha beta gamma', 'text/plain'),
		]);

		$result = $this->service()->compare(['fileId' => 42], ['fileId' => 43]);
		$this->assertSame(0, $result['summary']['changedHunks']);

	}//end testWhitespaceNormalisation()

	/**
	 * OpenRegister unavailable degrades to a plain diff.
	 *
	 * @return void
	 */
	public function testOpenRegisterUnavailableDegradesToPlainDiff(): void {
		$this->appManager->method('getInstalledApps')->willReturn([]);
		$this->wireFiles([
			42 => $this->file(42, 'one two three'),
			88 => $this->file(88, 'one TWO three'),
		]);

		$result = $this->service()->compare(['fileId' => 42], ['fileId' => 88]);
		$this->assertSame('unavailable', $result['redactionAnnotation']);
		$this->assertArrayNotHasKey('unredactedEntities', $result);

	}//end testOpenRegisterUnavailableDegradesToPlainDiff()

	/**
	 * An unrelated OR-backed pair produces no redaction annotations.
	 *
	 * @return void
	 */
	public function testUnrelatedPairCarriesNoRedactionAnnotations(): void {
		$this->appManager->method('getInstalledApps')->willReturn(['openregister']);
		$mapper = $this->buildMapper([], []);
		$this->container->method('get')->willReturn($mapper);
		$this->wireFiles([
			42 => $this->file(42, 'alpha beta'),
			88 => $this->file(88, 'alpha gamma'),
		]);

		$result = $this->service()->compare(['fileId' => 42], ['fileId' => 88]);
		$this->assertSame('none', $result['redactionAnnotation']);
		foreach ($result['hunks'] as $hunk) {
			$this->assertArrayNotHasKey('redaction', $hunk);
		}

	}//end testUnrelatedPairCarriesNoRedactionAnnotations()

	/**
	 * Original-vs-anonymised pair is key-annotated and surfaces a missed redaction.
	 *
	 * @return void
	 */
	public function testAnnotatedPairWithCompletenessSignal(): void {
		$this->appManager->method('getInstalledApps')->willReturn(['openregister']);

		// Two entities in the anonymise set; only entity 1 is reflected in output.
		$relations = [
			$this->relation(entityId: 1, anonymizedValue: '[PERSON-1]', skip: false),
			$this->relation(entityId: 2, anonymizedValue: '[PERSON-2]', skip: false),
		];
		$joined = [
			['entity_id' => 1, 'entity_type' => 'PERSON', 'entity_name' => 'Pieter Jansen', 'entity_value' => 'P. Jansen'],
			['entity_id' => 2, 'entity_type' => 'PERSON', 'entity_name' => 'Anna de Vries', 'entity_value' => 'A. de Vries'],
		];
		$mapper = $this->buildMapper($relations, $joined);
		$this->container->method('get')->willReturn($mapper);

		// Left = original (contains both names), right = anonymised (entity 1 replaced).
		$this->wireFiles([
			42 => $this->file(42, 'name is P. Jansen here'),
			88 => $this->file(88, 'name is [PERSON-1] here'),
		]);

		$result = $this->service()->compare(['fileId' => 42], ['fileId' => 88]);
		$this->assertSame('annotated', $result['redactionAnnotation']);

		$annotated = array_values(array_filter($result['hunks'], static fn ($h) => isset($h['redaction'])));
		$this->assertNotEmpty($annotated);
		$this->assertSame('key', $annotated[0]['redaction']['matchedBy']);
		$this->assertSame(1, $annotated[0]['redaction']['entityId']);

		// Entity 2 matched zero hunks => surfaced as unredacted by canonical name.
		$names = array_column($result['unredactedEntities'], 'entityName');
		$this->assertContains('Anna de Vries', $names);
		$this->assertNotContains('Pieter Jansen', $names);
		// Canonical names only, never literal document text.
		$this->assertNotContains('A. de Vries', $names);

	}//end testAnnotatedPairWithCompletenessSignal()

	/**
	 * Skip-flagged relations are excluded from the completeness signal.
	 *
	 * @return void
	 */
	public function testSkipFlaggedRelationsExcludedFromSignal(): void {
		$this->appManager->method('getInstalledApps')->willReturn(['openregister']);
		$relations = [
			$this->relation(entityId: 5, anonymizedValue: '[X]', skip: true),
		];
		$joined = [
			['entity_id' => 5, 'entity_type' => 'PERSON', 'entity_name' => 'Released Person', 'entity_value' => 'Released'],
		];
		$mapper = $this->buildMapper($relations, $joined);
		$this->container->method('get')->willReturn($mapper);
		$this->wireFiles([
			42 => $this->file(42, 'plain text content'),
			88 => $this->file(88, 'plain text content'),
		]);

		$result = $this->service()->compare(['fileId' => 42], ['fileId' => 88]);
		$this->assertSame('annotated', $result['redactionAnnotation']);
		$names = array_column($result['unredactedEntities'], 'entityName');
		$this->assertNotContains('Released Person', $names);

	}//end testSkipFlaggedRelationsExcludedFromSignal()

	/**
	 * Value-based fallback annotates a removed span matching a canonical value.
	 *
	 * @return void
	 */
	public function testValueBasedFallbackAnnotation(): void {
		$this->appManager->method('getInstalledApps')->willReturn(['openregister']);
		// Empty replacement key forces value-based matching on the removed span.
		$relations = [
			$this->relation(entityId: 9, anonymizedValue: '', skip: false),
		];
		$joined = [
			['entity_id' => 9, 'entity_type' => 'EMAIL', 'entity_name' => 'secret@example.com', 'entity_value' => 'secret@example.com'],
		];
		$mapper = $this->buildMapper($relations, $joined);
		$this->container->method('get')->willReturn($mapper);

		$this->wireFiles([
			42 => $this->file(42, 'contact secret@example.com today'),
			88 => $this->file(88, 'contact today'),
		]);

		$result = $this->service()->compare(['fileId' => 42], ['fileId' => 88]);
		$annotated = array_values(array_filter($result['hunks'], static fn ($h) => isset($h['redaction'])));
		$this->assertNotEmpty($annotated);
		$this->assertSame('value', $annotated[0]['redaction']['matchedBy']);

	}//end testValueBasedFallbackAnnotation()

	/**
	 * Build a mapper double with findByFileId / findEntitiesForFile.
	 *
	 * @param array<int, object> $relations Relation doubles.
	 * @param array<int, array<string,mixed>> $joined Joined rows.
	 *
	 * @return object The mapper double.
	 */
	private function buildMapper(array $relations, array $joined): object {
		return new class($relations, $joined) {
			/**
			 * @param array<int, object> $relations Relations.
			 * @param array<int, array<string,mixed>> $joined Joined rows.
			 */
			public function __construct(
				private array $relations,
				private array $joined,
			) {
			}

			/**
			 * @param int $fileId File id.
			 * @return array<int, object>
			 */
			public function findByFileId(int $fileId): array {
				return $this->relations;
			}

			/**
			 * @param int $fileId File id.
			 * @return array<int, array<string,mixed>>
			 */
			public function findEntitiesForFile(int $fileId): array {
				return $this->joined;
			}
		};

	}//end buildMapper()

	/**
	 * Build an EntityRelation double.
	 *
	 * @param int $entityId Entity id.
	 * @param string $anonymizedValue Replacement key.
	 * @param bool $skip Skip-anonymisation flag.
	 *
	 * @return object The relation double.
	 */
	private function relation(int $entityId, string $anonymizedValue, bool $skip): object {
		return new class($entityId, $anonymizedValue, $skip) {
			/**
			 * @param int $entityId Entity id.
			 * @param string $anonymizedValue Replacement key.
			 * @param bool $skip Skip flag.
			 */
			public function __construct(
				private int $entityId,
				private string $anonymizedValue,
				private bool $skip,
			) {
			}

			/**
			 * @return int
			 */
			public function getEntityId(): int {
				return $this->entityId;
			}

			/**
			 * @return string
			 */
			public function getAnonymizedValue(): string {
				return $this->anonymizedValue;
			}

			/**
			 * @return bool
			 */
			public function getSkipAnonymization(): bool {
				return $this->skip;
			}
		};

	}//end relation()
}//end class
