<?php

/**
 * Unit tests for PublicationStore
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Publication
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Publication;

use Exception;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\Publication\PublicationStore;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Publication records, their log, redacted copies and platform publications,
 * read from and written to OpenRegister with rows normalised to flat fields.
 *
 * @covers \OCA\Filinq\Service\Publication\PublicationStore
 */
class PublicationStoreTest extends TestCase {

	/**
	 * The fake OpenRegister object service.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objectService;

	/**
	 * The store under test.
	 *
	 * @var PublicationStore
	 */
	private PublicationStore $store;

	/**
	 * Set up the store over a mocked object service.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->objectService = $this->createMock(ObjectService::class);
		$resolver            = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->objectService);

		$this->store = new PublicationStore($resolver);

	}//end setUp()

	/**
	 * A record is read and normalised from its object envelope.
	 *
	 * @return void
	 */
	public function testFindRecordNormalisesTheEnvelope(): void {
		$entity = $this->createMock(ObjectEntity::class);
		$entity->method('jsonSerialize')->willReturn(['object' => ['title' => 'Besluit', '@self' => ['x' => 1]], '@self' => ['id' => 'rec-1']]);
		$this->objectService->expects($this->once())
			->method('find')
			->with('rec-1', 'filinq', 'publicationRecord')
			->willReturn($entity);

		$this->assertSame(['title' => 'Besluit', 'uuid' => 'rec-1'], $this->store->findRecord('rec-1'));

	}//end testFindRecordNormalisesTheEnvelope()

	/**
	 * No uuid, an unknown record or a failed read is null.
	 *
	 * @return void
	 */
	public function testFindRecordIsNullWhenAbsentOrUnreadable(): void {
		$this->objectService->method('find')->willReturnCallback(static function (string $id): ?array {
			if ($id === 'boom') {
				throw new Exception('timeout');
			}

			return null;
		});

		$this->assertNull($this->store->findRecord(''));
		$this->assertNull($this->store->findRecord('rec-9'));
		$this->assertNull($this->store->findRecord('boom'));

	}//end testFindRecordIsNullWhenAbsentOrUnreadable()

	/**
	 * The log is read for one record and sorted by timestamp; non-rows are dropped.
	 *
	 * @return void
	 */
	public function testLogForIsSortedByTimestamp(): void {
		$this->objectService->expects($this->once())
			->method('searchObjectsBySlug')
			->with('filinq', 'publicationLogEntry', ['publicationRecordRef' => 'rec-1'])
			->willReturn([
				['uuid' => 'l2', 'timestamp' => '2026-11-02'],
				'scalar',
				['uuid' => 'l1', 'timestamp' => '2026-11-01'],
			]);

		$this->assertSame(['l1', 'l2'], array_column($this->store->logFor('rec-1'), 'uuid'));

	}//end testLogForIsSortedByTimestamp()

	/**
	 * The redacted copy is the first link that has an anonymised file.
	 *
	 * @return void
	 */
	public function testRedactedCopyIsTheFirstLinkWithAFile(): void {
		$this->objectService->method('searchObjectsBySlug')
			->with('filinq', 'anonymizationLink', ['sourceFileId' => '12'])
			->willReturn([
				['uuid' => 'a', 'anonymizedFileId' => ''],
				['uuid' => 'b', 'anonymizedFileId' => 99],
			]);

		$this->assertSame('99', $this->store->findRedactedCopy('12'));
		$this->assertSame('b', $this->store->findRedactionLink('12')['uuid']);

	}//end testRedactedCopyIsTheFirstLinkWithAFile()

	/**
	 * Without a link that has a file there is no redacted copy.
	 *
	 * @return void
	 */
	public function testNoRedactedCopyWithoutAFile(): void {
		$this->objectService->method('searchObjectsBySlug')->willReturn([['uuid' => 'a']]);

		$this->assertNull($this->store->findRedactedCopy('12'));
		$this->assertNull($this->store->findRedactionLink('12'));

	}//end testNoRedactedCopyWithoutAFile()

	/**
	 * Saving strips framework fields, treats an empty uuid as new and fills the uuid back.
	 *
	 * @return void
	 */
	public function testSaveRecordStripsFrameworkFields(): void {
		$this->objectService->expects($this->exactly(2))
			->method('saveObject')
			->willReturnCallback(static function (array $object, string $register, string $schema, ?string $uuid, bool $_rbac, bool $_multitenancy, bool $_unowned): array {
				TestCase::assertSame(['title' => 'Besluit'], $object);
				TestCase::assertSame('filinq', $register);
				TestCase::assertSame('publicationRecord', $schema);
				TestCase::assertFalse($_rbac);
				TestCase::assertTrue($_unowned);

				return $uuid === null ? ['uuid' => 'new-1', 'title' => 'Besluit'] : ['title' => 'Besluit'];
			});

		$record = ['uuid' => 'x', '@self' => [], 'log' => [], 'title' => 'Besluit'];

		$this->assertSame('new-1', $this->store->saveRecord($record, '')['uuid']);
		$this->assertSame('rec-1', $this->store->saveRecord($record, 'rec-1')['uuid']);

	}//end testSaveRecordStripsFrameworkFields()

	/**
	 * A refused save is reported with the schema and the code.
	 *
	 * @return void
	 */
	public function testARefusedSaveIsReported(): void {
		$this->objectService->method('saveObject')->willThrowException(new Exception('invalid', 422));

		try {
			$this->store->appendLog(['event' => 'published']);
			$this->fail('A refused save was not reported.');
		} catch (RuntimeException $e) {
			$this->assertSame('OpenRegister refused the publicationLogEntry: invalid', $e->getMessage());
			$this->assertSame(422, $e->getCode());
		}

	}//end testARefusedSaveIsReported()

	/**
	 * Updating a platform publication merges over the stored one in the publication register.
	 *
	 * @return void
	 */
	public function testPlatformPublicationUpdateMergesOverTheStoredOne(): void {
		$this->objectService->expects($this->once())
			->method('find')
			->with('pub-1', 'publication', 'publication')
			->willReturn(['uuid' => 'pub-1', 'title' => 'Oud', 'status' => 'concept']);
		$this->objectService->expects($this->once())
			->method('saveObject')
			->with(['title' => 'Nieuw', 'status' => 'concept'], 'publication', 'publication', 'pub-1')
			->willReturnArgument(0);

		$this->assertSame(
			['title' => 'Nieuw', 'status' => 'concept', 'uuid' => 'pub-1'],
			$this->store->savePlatformPublication(['title' => 'Nieuw'], 'pub-1')
		);

	}//end testPlatformPublicationUpdateMergesOverTheStoredOne()

	/**
	 * A new platform publication is saved without a lookup.
	 *
	 * @return void
	 */
	public function testNewPlatformPublicationIsSavedWithoutALookup(): void {
		$this->objectService->expects($this->never())->method('find');
		$this->objectService->method('saveObject')->willReturn(['id' => 'pub-2', 'title' => 'Nieuw']);

		$this->assertSame(['id' => 'pub-2', 'title' => 'Nieuw', 'uuid' => 'pub-2'], $this->store->savePlatformPublication(['title' => 'Nieuw']));

	}//end testNewPlatformPublicationIsSavedWithoutALookup()
}//end class
