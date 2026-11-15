<?php

/**
 * Unit tests for PseudonymMapRepository
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Pseudonymisation
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

namespace OCA\Filinq\Tests\Unit\Service\Pseudonymisation;

use Exception;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\Pseudonymisation\PseudonymMapRepository;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Pseudonym maps are matched to their link or source by the repository itself,
 * never hand out their ciphertext in a listing, and every failed read, write
 * or delete is reported.
 *
 * @covers \OCA\Filinq\Service\Pseudonymisation\PseudonymMapRepository
 * @uses   \OCA\Filinq\Service\ReadsStoredObjectRows
 */
class PseudonymMapRepositoryTest extends TestCase {

	/**
	 * The fake OpenRegister object service.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objectService;

	/**
	 * The repository under test.
	 *
	 * @var PseudonymMapRepository
	 */
	private PseudonymMapRepository $repository;

	/**
	 * Set up the repository over a mocked object service.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->objectService = $this->createMock(ObjectService::class);
		$resolver            = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->objectService);

		$this->repository = new PseudonymMapRepository($resolver);

	}//end setUp()

	/**
	 * The link's own map is returned without its mappings; another link's is ignored.
	 *
	 * @return void
	 */
	public function testFindForLinkMatchesTheLinkItself(): void {
		$this->objectService->expects($this->once())
			->method('searchObjectsBySlug')
			->with('filinq', 'pseudonymMap', ['anonymizationLink' => 'link-1'], false)
			->willReturn([
				['uuid' => 'map-0', 'anonymizationLink' => 'link-2', 'mappings' => 'c0'],
				['object' => ['anonymizationLink' => 'link-1', 'keyId' => 'k', 'mappings' => 'c1'], 'uuid' => 'map-1'],
			]);

		$this->assertSame(
			['anonymizationLink' => 'link-1', 'keyId' => 'k', 'uuid' => 'map-1'],
			$this->repository->findForLink('link-1')
		);

	}//end testFindForLinkMatchesTheLinkItself()

	/**
	 * No link, or no matching map, is null.
	 *
	 * @return void
	 */
	public function testFindForLinkIsNullWithoutAMatch(): void {
		$this->objectService->expects($this->once())->method('searchObjectsBySlug')->willReturn([]);

		$this->assertNull($this->repository->findForLink(''));
		$this->assertNull($this->repository->findForLink('link-1'));

	}//end testFindForLinkIsNullWithoutAMatch()

	/**
	 * A failed search is reported.
	 *
	 * @return void
	 */
	public function testFindForLinkReportsAFailedRead(): void {
		$this->objectService->method('searchObjectsBySlug')->willThrowException(new Exception('down'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The pseudonym map could not be read: down');

		$this->repository->findForLink('link-1');

	}//end testFindForLinkReportsAFailedRead()

	/**
	 * Only the source's own maps with a uuid are listed, without mappings.
	 *
	 * @return void
	 */
	public function testFindForSourceListsOnlyItsOwnMaps(): void {
		$this->objectService->method('searchObjectsBySlug')
			->with('filinq', 'pseudonymMap', ['sourceFileId' => 7], false)
			->willReturn([
				['uuid' => 'map-1', 'sourceFileId' => '7', 'mappings' => 'c1'],
				['uuid' => 'map-2', 'sourceFileId' => 8],
				['sourceFileId' => 7],
				['uuid' => 'map-3', 'sourceFileId' => 7],
			]);

		$this->assertSame(
			[['uuid' => 'map-1', 'sourceFileId' => '7'], ['uuid' => 'map-3', 'sourceFileId' => 7]],
			$this->repository->findForSource(7)
		);

	}//end testFindForSourceListsOnlyItsOwnMaps()

	/**
	 * A failed source listing is reported.
	 *
	 * @return void
	 */
	public function testFindForSourceReportsAFailedRead(): void {
		$this->objectService->method('searchObjectsBySlug')->willThrowException(new Exception('down'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The pseudonym maps could not be read: down');

		$this->repository->findForSource(7);

	}//end testFindForSourceReportsAFailedRead()

	/**
	 * The ciphertext is read unrendered from the map.
	 *
	 * @return void
	 */
	public function testReadCiphertextReturnsTheMappings(): void {
		$this->objectService->expects($this->once())
			->method('find')
			->with('map-1', 'filinq', 'pseudonymMap', false, true, false)
			->willReturn(['uuid' => 'map-1', 'mappings' => 'cipher']);

		$this->assertSame('cipher', $this->repository->readCiphertext('map-1'));

	}//end testReadCiphertextReturnsTheMappings()

	/**
	 * A missing map and a failed read are both reported.
	 *
	 * @return void
	 */
	public function testReadCiphertextReportsMissingAndFailedReads(): void {
		$this->objectService->method('find')->willReturnCallback(static function (string $id): ?array {
			if ($id === 'boom') {
				throw new Exception('timeout');
			}

			return null;
		});

		foreach (['map-9' => 'The pseudonym map map-9 does not exist.', 'boom' => 'The pseudonym map could not be read: timeout'] as $uuid => $message) {
			try {
				$this->repository->readCiphertext($uuid);
				$this->fail('No error for ' . $uuid);
			} catch (RuntimeException $e) {
				$this->assertSame($message, $e->getMessage());
			}
		}

	}//end testReadCiphertextReportsMissingAndFailedReads()

	/**
	 * Saving sends the uuid as an argument and returns the stored uuid.
	 *
	 * @return void
	 */
	public function testSaveReturnsTheStoredUuid(): void {
		$this->objectService->expects($this->once())
			->method('saveObject')
			->with(['keyId' => 'k'], 'filinq', 'pseudonymMap', 'map-1', false)
			->willReturn(['@self' => ['id' => 'map-1'], 'keyId' => 'k']);

		$this->assertSame('map-1', $this->repository->save(['uuid' => 'x', 'keyId' => 'k'], 'map-1'));

	}//end testSaveReturnsTheStoredUuid()

	/**
	 * A refused save and a save without a uuid are reported.
	 *
	 * @return void
	 */
	public function testSaveReportsRefusalAndMissingUuid(): void {
		$this->objectService->method('saveObject')->willReturnOnConsecutiveCalls(
			$this->throwException(new Exception('invalid')),
			['keyId' => 'k']
		);

		foreach (['Could not store the pseudonym map: invalid', 'OpenRegister stored the pseudonym map without a uuid.'] as $message) {
			try {
				$this->repository->save(['keyId' => 'k'], null);
				$this->fail('No error: ' . $message);
			} catch (RuntimeException $e) {
				$this->assertSame($message, $e->getMessage());
			}
		}

	}//end testSaveReportsRefusalAndMissingUuid()

	/**
	 * Deleting succeeds quietly, and reports a refusal or an exception.
	 *
	 * @return void
	 */
	public function testDelete(): void {
		$this->objectService->expects($this->exactly(3))
			->method('deleteObject')
			->with($this->anything(), 'filinq', 'pseudonymMap', false)
			->willReturnOnConsecutiveCalls(true, false, $this->throwException(new Exception('locked')));

		$this->repository->delete('map-1');

		foreach (['OpenRegister did not delete the pseudonym map map-2.' => 'map-2', 'Could not delete the pseudonym map: locked' => 'map-3'] as $message => $uuid) {
			try {
				$this->repository->delete($uuid);
				$this->fail('No error for ' . $uuid);
			} catch (RuntimeException $e) {
				$this->assertSame($message, $e->getMessage());
			}
		}

	}//end testDelete()
}//end class
