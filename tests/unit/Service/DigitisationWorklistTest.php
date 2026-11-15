<?php

/**
 * Unit tests for DigitisationWorklist.
 *
 * REQ-PAD-001: the project page counts the originals per box and state.
 * REQ-PAD-004: a checker passes a scan, or rejects it with a note and the
 * original goes back to registered. OpenRegister is doubled at its object
 * contract.
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use InvalidArgumentException;
use OCA\Filinq\Service\DigitisationWorklist;
use OCA\OpenRegister\Contract\ObjectEntityInterface;
use OCA\OpenRegister\Contract\ObjectServiceInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Service\DigitisationWorklist
 */
class DigitisationWorklistTest extends TestCase {

	/**
	 * Stored payloads by "schema/id".
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $objects = [];

	/**
	 * Search answers for paperOriginal.
	 *
	 * @var array<int, mixed>
	 */
	private array $found = [];

	/**
	 * Saves, as [object, schema, uuid].
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $saves = [];

	/**
	 * The filters each search was asked with.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $searches = [];

	/**
	 * An entity double.
	 *
	 * @param string               $uuid    The uuid.
	 * @param array<string, mixed> $payload The payload.
	 *
	 * @return ObjectEntityInterface
	 */
	private function entity(string $uuid, array $payload): ObjectEntityInterface {
		$entity = $this->createMock(ObjectEntityInterface::class);
		$entity->method('getUuid')->willReturn($uuid);
		$entity->method('getObject')->willReturn($payload);
		return $entity;
	}//end entity()

	/**
	 * The worklist over the stored objects.
	 *
	 * @return DigitisationWorklist
	 */
	private function worklist(): DigitisationWorklist {
		$objects = $this->createMock(ObjectServiceInterface::class);
		$objects->method('find')->willReturnCallback(
			function (int|string $id, ?array $_extend = [], bool $files = false, string|int|null $register = null, string|int|null $schema = null, bool $_rbac = true): ?ObjectEntityInterface {
				self::assertTrue($_rbac);
				$payload = ($this->objects[$schema . '/' . $id] ?? null);
				if ($payload === null) {
					throw new RuntimeException('Object not found');
				}

				return $this->entity((string)$id, $payload);
			}
		);
		$objects->method('searchObjectsBySlug')->willReturnCallback(
			function (string $registerSlug, string $schemaSlug, array $filters = [], bool $_rbac = true): array {
				self::assertSame(['filinq', 'paperOriginal', true], [$registerSlug, $schemaSlug, $_rbac]);
				$this->searches[] = $filters;
				return $this->found;
			}
		);
		$objects->method('saveObject')->willReturnCallback(
			function (array $object, ?array $extend = [], string|int|null $register = null, string|int|null $schema = null, ?string $uuid = null, bool $_rbac = true): ObjectEntityInterface {
				self::assertTrue($_rbac);
				$this->saves[] = ['object' => $object, 'register' => $register, 'schema' => $schema, 'uuid' => $uuid];
				return $this->createMock(ObjectEntityInterface::class);
			}
		);

		return new DigitisationWorklist($objects);
	}//end worklist()

	/**
	 * A project is read with its id; one the caller cannot read is null.
	 *
	 * @return void
	 */
	public function testAProjectIsReadWithItsId(): void {
		$this->objects['digitisationProject/prj-1'] = ['name' => 'Bouwvergunningen 1970-2000'];

		self::assertSame(['name' => 'Bouwvergunningen 1970-2000', 'id' => 'prj-1'], $this->worklist()->project(projectId: 'prj-1'));
		self::assertNull($this->worklist()->project(projectId: 'prj-2'));
		self::assertNull($this->worklist()->project(projectId: ' '));
	}//end testAProjectIsReadWithItsId()

	/**
	 * Originals come back of the project and box asked for, sorted by shelf
	 * mark, whatever shape the search answers in.
	 *
	 * @return void
	 */
	public function testOriginalsOfOneBoxSortedByShelfMark(): void {
		$this->found = [
			['@self' => ['id' => 'po-10'], 'project' => 'prj-1', 'box' => '117', 'shelfMark' => '117/10'],
			$this->entity('po-2', ['project' => 'prj-1', 'box' => '117', 'shelfMark' => '117/2']),
			['id' => 'po-3', 'project' => 'prj-1', 'box' => '118', 'shelfMark' => '118/1'],
			['id' => 'po-4', 'project' => 'prj-9', 'box' => '117', 'shelfMark' => '117/1'],
			['project' => 'prj-1', 'box' => '117', 'shelfMark' => 'no id'],
			'garbage',
		];

		$originals = $this->worklist()->originals(projectId: 'prj-1', box: '117');

		self::assertSame(['po-2', 'po-10'], array_column($originals, 'id'));
		self::assertSame([['project' => 'prj-1', '_limit' => 10000]], $this->searches);
	}//end testOriginalsOfOneBoxSortedByShelfMark()

	/**
	 * Every box is counted per state, a rejected scan awaiting its rescan
	 * counts as rejected, and a declared box without originals shows zeros.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-an-archivist-runs-a-digitisation-project-req-pad-001
	 */
	public function testBoxesAreCountedPerState(): void {
		$this->found = [
			['id' => 'a', 'project' => 'prj-1', 'box' => '117', 'status' => 'registered'],
			['id' => 'b', 'project' => 'prj-1', 'box' => '117', 'status' => 'scanned'],
			['id' => 'c', 'project' => 'prj-1', 'box' => '117', 'status' => 'checked'],
			['id' => 'd', 'project' => 'prj-1', 'box' => '117', 'status' => 'registered', 'check' => ['result' => 'rejected']],
			['id' => 'e', 'project' => 'prj-1', 'box' => '120', 'status' => 'rejected'],
			['id' => 'f', 'project' => 'prj-1', 'box' => '120', 'status' => 'odd'],
		];

		$rows = $this->worklist()->boxCounts(project: ['id' => 'prj-1', 'boxes' => ['116', '117', '', ['x']]]);

		self::assertSame(
			[
				['id' => '116', 'project' => 'prj-1', 'box' => '116', 'registered' => 0, 'scanned' => 0, 'checked' => 0, 'rejected' => 0, 'total' => 0],
				['id' => '117', 'project' => 'prj-1', 'box' => '117', 'registered' => 1, 'scanned' => 1, 'checked' => 1, 'rejected' => 1, 'total' => 4],
				['id' => '120', 'project' => 'prj-1', 'box' => '120', 'registered' => 1, 'scanned' => 0, 'checked' => 0, 'rejected' => 1, 'total' => 2],
			],
			$rows
		);
	}//end testBoxesAreCountedPerState()

	/**
	 * A passed scan makes the original checked with who and the note.
	 *
	 * @return void
	 */
	public function testAPassedScanIsChecked(): void {
		$this->objects['paperOriginal/po-1'] = ['@self' => ['id' => 'po-1'], 'project' => 'prj-1', 'box' => '117', 'status' => 'scanned', 'document' => 41];

		$saved = $this->worklist()->check(originalId: 'po-1', result: 'passed', note: '', checkedBy: 'checker');

		self::assertSame('checked', $saved['status']);
		self::assertCount(1, $this->saves);
		self::assertSame('po-1', $this->saves[0]['uuid']);
		self::assertSame(['filinq', 'paperOriginal'], [$this->saves[0]['register'], $this->saves[0]['schema']]);
		$object = $this->saves[0]['object'];
		self::assertArrayNotHasKey('@self', $object);
		self::assertArrayNotHasKey('id', $object);
		self::assertSame(['passed', 'checker', ''], [$object['check']['result'], $object['check']['checkedBy'], $object['check']['note']]);
		self::assertNotFalse(\DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $object['check']['checkedAt']));
	}//end testAPassedScanIsChecked()

	/**
	 * A rejected scan goes through rejected back to registered, keeping the note.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-a-sampled-share-is-checked-by-a-person-req-pad-004
	 */
	public function testARejectedScanGoesBackToRegistered(): void {
		$this->objects['paperOriginal/po-1'] = ['project' => 'prj-1', 'box' => '117', 'status' => 'scanned'];

		$saved = $this->worklist()->check(originalId: 'po-1', result: 'rejected', note: ' page 2 cut off ', checkedBy: 'checker');

		self::assertSame(['rejected', 'registered'], array_map(static fn (array $save): string => $save['object']['status'], $this->saves));
		self::assertSame('registered', $saved['status']);
		self::assertSame(['rejected', 'page 2 cut off'], [$saved['check']['result'], $saved['check']['note']]);
		self::assertSame($this->saves[0]['object']['check'], $this->saves[1]['object']['check']);
	}//end testARejectedScanGoesBackToRegistered()

	/**
	 * An unknown verdict, a rejection without a note and an original that is
	 * not scanned are refused without a save; an unreadable one is null.
	 *
	 * @return void
	 */
	public function testAnInvalidCheckIsRefused(): void {
		$this->objects['paperOriginal/po-1'] = ['project' => 'prj-1', 'status' => 'checked'];

		$cases = [
			['po-1', 'maybe', 'x', 'A check is passed or rejected.'],
			['po-1', 'rejected', ' ', 'A rejected scan needs a note saying what is wrong.'],
			['po-1', 'passed', '', 'Only a scanned original waits for a check.'],
		];
		foreach ($cases as [$id, $result, $note, $message]) {
			try {
				$this->worklist()->check(originalId: $id, result: $result, note: $note, checkedBy: 'checker');
				self::fail('Expected a refusal for: ' . $message);
			} catch (InvalidArgumentException $e) {
				self::assertSame($message, $e->getMessage());
			}
		}

		self::assertNull($this->worklist()->check(originalId: 'po-404', result: 'passed', note: '', checkedBy: 'checker'));
		self::assertSame([], $this->saves);
	}//end testAnInvalidCheckIsRefused()
}//end class
