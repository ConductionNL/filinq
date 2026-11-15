<?php

/**
 * SigningEnvelopeRepository tests
 *
 * Pins what the envelope repository hands OpenRegister and what it reads
 * back: the fields it strips before a save, the uuid it falls back to, the
 * errors it wraps, the empty-uuid shortcut, and the owner filter and newest-
 * first order of a listing.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\SigningEnvelope
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\SigningEnvelope;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\SigningEnvelope\SigningEnvelopeRepository;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for SigningEnvelopeRepository.
 */
#[CoversClass(SigningEnvelopeRepository::class)]
final class SigningEnvelopeRepositoryTest extends TestCase {

	/**
	 * The OpenRegister object service double.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objects;

	/**
	 * The repository under test.
	 *
	 * @var SigningEnvelopeRepository
	 */
	private SigningEnvelopeRepository $repository;

	/**
	 * Wire the repository to the object service double.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->objects = $this->createMock(ObjectService::class);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->objects);
		$this->repository = new SigningEnvelopeRepository(objectResolver: $resolver);
	}//end setUp()

	/**
	 * A save drops the derived uuid and members, writes unscoped, and reads the stored payload back.
	 *
	 * @return void
	 */
	public function testSaveStripsDerivedFieldsAndReadsThePayload(): void {
		$this->objects->expects($this->once())
			->method('saveObject')
			->with(['title' => 'Contract', 'status' => 'open'], 'filinq', 'signingEnvelope', 'env-1', false)
			->willReturn(['object' => ['title' => 'Contract', 'status' => 'open'], '@self' => ['id' => 'env-1']]);

		$saved = $this->repository->save(envelope: ['uuid' => 'ignored', 'members' => [['id' => 'm']], 'title' => 'Contract', 'status' => 'open'], uuid: 'env-1');

		self::assertSame(['title' => 'Contract', 'status' => 'open', 'uuid' => 'env-1'], $saved);
	}//end testSaveStripsDerivedFieldsAndReadsThePayload()

	/**
	 * When the stored row names no uuid, the requested one is used; an entity is read through jsonSerialize.
	 *
	 * @return void
	 */
	public function testSaveFallsBackToTheRequestedUuid(): void {
		$entity = new class implements \JsonSerializable {
			/**
			 * The serialised row.
			 *
			 * @return array<string, mixed>
			 */
			public function jsonSerialize(): array {
				return ['title' => 'Zonder id'];
			}
		};
		$this->objects->method('saveObject')->willReturn($entity);

		self::assertSame(['title' => 'Zonder id', 'uuid' => 'env-2'], $this->repository->save(envelope: ['title' => 'Zonder id'], uuid: 'env-2'));
	}//end testSaveFallsBackToTheRequestedUuid()

	/**
	 * A failing save is wrapped with its cause.
	 *
	 * @return void
	 */
	public function testAFailingSaveIsWrapped(): void {
		$cause = new \LogicException('disk full');
		$this->objects->method('saveObject')->willThrowException($cause);

		try {
			$this->repository->save(envelope: [], uuid: 'env-3');
			self::fail('Expected the save to fail.');
		} catch (RuntimeException $e) {
			self::assertSame('Could not store the envelope: disk full', $e->getMessage());
			self::assertSame($cause, $e->getPrevious());
		}
	}//end testAFailingSaveIsWrapped()

	/**
	 * An empty uuid finds nothing without asking the store; a missing row is null.
	 *
	 * @return void
	 */
	public function testFindShortcutsAndMissingRows(): void {
		$this->objects->expects($this->once())
			->method('find')
			->with('env-4', 'filinq', 'signingEnvelope', false)
			->willReturn(null);

		self::assertNull($this->repository->find(uuid: ''));
		self::assertNull($this->repository->find(uuid: 'env-4'));
	}//end testFindShortcutsAndMissingRows()

	/**
	 * A found row is read without its `@self`, keeping the stored uuid.
	 *
	 * @return void
	 */
	public function testFindReadsTheRow(): void {
		$this->objects->method('find')->willReturn(['uuid' => 'env-5', 'title' => 'NDA', '@self' => ['id' => 'env-5']]);

		self::assertSame(['uuid' => 'env-5', 'title' => 'NDA'], $this->repository->find(uuid: 'env-5'));
	}//end testFindReadsTheRow()

	/**
	 * A failing read is wrapped with its cause.
	 *
	 * @return void
	 */
	public function testAFailingFindIsWrapped(): void {
		$this->objects->method('find')->willThrowException(new \LogicException('gone'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The envelope could not be read: gone');
		$this->repository->find(uuid: 'env-6');
	}//end testAFailingFindIsWrapped()

	/**
	 * A listing for one initiator filters by them, drops rows of anyone else, and sorts newest first.
	 *
	 * @return void
	 */
	public function testListFiltersByInitiatorAndSortsNewestFirst(): void {
		$this->objects->expects($this->once())
			->method('searchObjectsBySlug')
			->with('filinq', 'signingEnvelope', ['initiatorUserId' => 'alice'], false)
			->willReturn(
				[
					['uuid' => 'a', 'initiatorUserId' => 'alice', 'createdAt' => '2026-01-01T10:00:00Z'],
					['uuid' => 'b', 'initiatorUserId' => 'bob', 'createdAt' => '2026-03-01T10:00:00Z'],
					['uuid' => 'c', 'initiatorUserId' => 'alice', 'createdAt' => '2026-02-01T10:00:00Z'],
					['uuid' => 'd', 'initiatorUserId' => 'alice'],
				]
			);

		self::assertSame(['c', 'a', 'd'], array_column($this->repository->list(initiator: 'alice'), 'uuid'));
	}//end testListFiltersByInitiatorAndSortsNewestFirst()

	/**
	 * A listing for everyone has no filter and keeps every row.
	 *
	 * @return void
	 */
	public function testListForEveryoneKeepsEveryRow(): void {
		$this->objects->expects($this->once())
			->method('searchObjectsBySlug')
			->with('filinq', 'signingEnvelope', [], false)
			->willReturn([['uuid' => 'a', 'initiatorUserId' => 'alice', 'createdAt' => '1'], ['uuid' => 'b', 'createdAt' => '2']]);

		self::assertSame(['b', 'a'], array_column($this->repository->list(initiator: null), 'uuid'));
	}//end testListForEveryoneKeepsEveryRow()

	/**
	 * A failing listing is wrapped with its cause.
	 *
	 * @return void
	 */
	public function testAFailingListIsWrapped(): void {
		$this->objects->method('searchObjectsBySlug')->willThrowException(new \LogicException('timeout'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The envelopes could not be read: timeout');
		$this->repository->list(initiator: 'alice');
	}//end testAFailingListIsWrapped()
}//end class
