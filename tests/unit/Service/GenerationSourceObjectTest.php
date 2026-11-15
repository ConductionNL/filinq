<?php

/**
 * Unit tests for GenerationSourceObject
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
 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\GenerationSourceObject;
use OCA\Filinq\Service\StoredRecordFields;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindOptions;
use OCA\OpenRegister\Service\ObjectService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The source object of a generation: one of the data references, recorded, and its folder as the default target.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
#[CoversClass(GenerationSourceObject::class)]
#[UsesClass(StoredRecordFields::class)]
final class GenerationSourceObjectTest extends TestCase {

	private const HOST = ['register' => 'dossiq', 'schema' => 'case', 'id' => 'c-1'];

	private ObjectService&MockObject $objects;

	private Folder&MockObject $userFolder;

	/**
	 * The ids find() was asked for.
	 *
	 * @var list<array{0: string, 1: string, 2: string}>
	 */
	private array $finds = [];

	/**
	 * The service, with the host stored under folder 77 at "Open Registers/Zaken/c-1" in the clerk's Files.
	 *
	 * @param mixed $stored What OpenRegister answers for the host.
	 *
	 * @return GenerationSourceObject
	 */
	private function service(mixed $stored = ['title' => 'Zaak', '@self' => ['id' => 'c-1', 'folder' => 77]]): GenerationSourceObject {
		$this->objects = $this->createMock(ObjectService::class);
		$this->objects->method('find')->willReturnCallback(function (string $id = '', ?ObjectFindOptions $options = null) use ($stored): mixed {
			$this->finds[] = [$id, (string)($options?->register ?? ''), (string)($options?->schema ?? '')];
			return $stored;
		});
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->objects);

		$hostFolder = $this->createMock(Folder::class);
		$hostFolder->method('getPath')->willReturn('/clerk/files/Open Registers/Zaken/c-1');
		$this->userFolder = $this->createMock(Folder::class);
		$this->userFolder->method('getFirstNodeById')->willReturnCallback(fn (int $id): mixed => $id === 77 ? $hostFolder : null);
		$this->userFolder->method('getRelativePath')->willReturnCallback(
			static fn (string $path): string => substr($path, strlen('/clerk/files'))
		);
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->with('clerk')->willReturn($this->userFolder);

		return new GenerationSourceObject(objects: $resolver, rootFolder: $root);
	}//end service()

	/**
	 * Options without a source object pass through untouched, and nothing is looked up.
	 *
	 * @return void
	 */
	public function testWithoutASourceObjectNothingChanges(): void {
		$options = ['output' => ['mode' => 'files'], 'format' => 'pdf'];

		$this->assertSame(
			['options' => $options, 'recordFields' => []],
			$this->service()->apply(dataRefs: [self::HOST], options: $options, userId: 'clerk')
		);
		$this->assertSame([], $this->finds);
	}//end testWithoutASourceObjectNothingChanges()

	/**
	 * A stored document without a target goes into the host's folder, and the host is recorded.
	 *
	 * @return void
	 */
	public function testAStoredDocumentGoesIntoTheHostFolderAndRecordsItsSource(): void {
		$applied = $this->service()->apply(
			dataRefs: [['register' => 'dossiq', 'schema' => 'case', 'id' => 'c-1']],
			options: ['output' => ['mode' => 'files'], 'sourceObject' => self::HOST],
			userId: 'clerk'
		);

		$this->assertSame(['output' => ['mode' => 'files', 'targetPath' => 'Open Registers/Zaken/c-1']], $applied['options']);
		$this->assertSame(['sourceObject' => self::HOST], $applied['recordFields']);
		$this->assertSame([['c-1', 'dossiq', 'case']], $this->finds);
	}//end testAStoredDocumentGoesIntoTheHostFolderAndRecordsItsSource()

	/**
	 * A target the caller names wins over the host folder; the host is still recorded.
	 *
	 * @return void
	 */
	public function testAGivenTargetPathIsKept(): void {
		$applied = $this->service()->apply(
			dataRefs: [self::HOST],
			options: ['output' => ['mode' => 'both', 'targetPath' => 'Brieven'], 'sourceObject' => self::HOST],
			userId: 'clerk'
		);

		$this->assertSame(['output' => ['mode' => 'both', 'targetPath' => 'Brieven']], $applied['options']);
		$this->assertSame(['sourceObject' => self::HOST], $applied['recordFields']);
		$this->assertSame([], $this->finds);
	}//end testAGivenTargetPathIsKept()

	/**
	 * A document only returned, not stored, is recorded but placed nowhere.
	 *
	 * @return void
	 */
	public function testAReturnedDocumentIsRecordedButNotPlaced(): void {
		$applied = $this->service()->apply(dataRefs: [self::HOST], options: ['sourceObject' => self::HOST], userId: 'clerk');

		$this->assertSame([], $applied['options']);
		$this->assertSame(['sourceObject' => self::HOST], $applied['recordFields']);
		$this->assertSame([], $this->finds);
	}//end testAReturnedDocumentIsRecordedButNotPlaced()

	/**
	 * A source object the request does not render is refused with 400, before anything is looked up.
	 *
	 * @return void
	 */
	public function testASourceObjectOutsideTheDataReferencesIsRefused(): void {
		$service = $this->service();
		foreach ([
			['register' => 'dossiq', 'schema' => 'case', 'id' => 'c-2'],
			['register' => 'dossiq', 'schema' => 'case'],
			['register' => 'dossiq', 'schema' => 'case', 'id' => ''],
			'dossiq/case/c-1',
		] as $candidate) {
			try {
				$service->apply(dataRefs: [self::HOST], options: ['output' => ['mode' => 'files'], 'sourceObject' => $candidate], userId: 'clerk');
				$this->fail('A source object outside the data references was accepted.');
			} catch (Exception $e) {
				$this->assertSame('options.sourceObject must be one of the dataRefs', $e->getMessage());
				$this->assertSame(400, $e->getCode());
			}
		}

		$this->assertSame([], $this->finds);
	}//end testASourceObjectOutsideTheDataReferencesIsRefused()

	/**
	 * A numeric id matches the same id given as a string.
	 *
	 * @return void
	 */
	public function testANumericIdMatchesItsString(): void {
		$applied = $this->service()->apply(
			dataRefs: [['register' => 'dossiq', 'schema' => 'case', 'id' => 12]],
			options: ['sourceObject' => ['register' => 'dossiq', 'schema' => 'case', 'id' => '12']],
			userId: 'clerk'
		);

		$this->assertSame(['sourceObject' => ['register' => 'dossiq', 'schema' => 'case', 'id' => '12']], $applied['recordFields']);
	}//end testANumericIdMatchesItsString()

	/**
	 * Without a reachable host folder the document keeps the default folder.
	 *
	 * @return void
	 */
	public function testWithoutAReachableHostFolderTheDefaultFolderStays(): void {
		$cases = [
			'no folder'         => ['title' => 'Zaak', '@self' => ['id' => 'c-1']],
			'folder not seen'   => ['title' => 'Zaak', '@self' => ['id' => 'c-1', 'folder' => 78]],
			'object not found'  => null,
		];
		foreach ($cases as $label => $stored) {
			$applied = $this->service(stored: $stored)->apply(
				dataRefs: [self::HOST],
				options: ['output' => ['mode' => 'files'], 'sourceObject' => self::HOST],
				userId: 'clerk'
			);
			$this->assertSame(['output' => ['mode' => 'files']], $applied['options'], $label);
			$this->assertSame(['sourceObject' => self::HOST], $applied['recordFields'], $label);
		}
	}//end testWithoutAReachableHostFolderTheDefaultFolderStays()

	/**
	 * A file instead of a folder, a lookup that throws, or a path the storage would refuse keeps the default folder.
	 *
	 * @return void
	 */
	public function testAnUnusableHostFolderKeepsTheDefaultFolder(): void {
		$this->service();

		$file = $this->createMock(File::class);
		$odd  = $this->createMock(Folder::class);
		$odd->method('getPath')->willReturn('/clerk/files/Zaken/c:1?');
		$top  = $this->createMock(Folder::class);
		$top->method('getPath')->willReturn('/clerk/files');
		foreach ([$file, $odd, $top, new RuntimeException('storage down')] as $answer) {
			$userFolder = $this->createMock(Folder::class);
			if ($answer instanceof RuntimeException) {
				$userFolder->method('getFirstNodeById')->willThrowException($answer);
			} else {
				$userFolder->method('getFirstNodeById')->willReturn($answer);
			}

			$userFolder->method('getRelativePath')->willReturnCallback(static fn (string $path): string => substr($path, strlen('/clerk/files')));
			$root = $this->createMock(IRootFolder::class);
			$root->method('getUserFolder')->willReturn($userFolder);
			$resolver = $this->createMock(DocumentObjectServiceResolver::class);
			$resolver->method('resolve')->willReturn($this->objects);
			$service = new GenerationSourceObject(objects: $resolver, rootFolder: $root);

			$applied = $service->apply(
				dataRefs: [self::HOST],
				options: ['output' => ['mode' => 'files'], 'sourceObject' => self::HOST],
				userId: 'clerk'
			);
			$this->assertSame(['output' => ['mode' => 'files']], $applied['options']);
		}
	}//end testAnUnusableHostFolderKeepsTheDefaultFolder()
}//end class
