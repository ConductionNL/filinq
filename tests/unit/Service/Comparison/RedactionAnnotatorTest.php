<?php

/**
 * Redaction annotation of diff hunks.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Comparison
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Comparison;

use OCA\Filinq\Service\Comparison\RedactionAnnotator;
use OCP\App\IAppManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The status, the hunk annotations and the completeness list, recorded on the
 * implementation before annotate() was split.
 *
 * @covers \OCA\Filinq\Service\Comparison\RedactionAnnotator
 * @uses \OCA\Filinq\Service\Comparison\RedactionMatchSet
 */
class RedactionAnnotatorTest extends TestCase {

	/**
	 * An annotator over the given mapper; null means OpenRegister is not installed.
	 *
	 * @param object|null           $mapper    The entity relation mapper.
	 * @param RuntimeException|null $container An error the container throws.
	 *
	 * @return RedactionAnnotator
	 */
	private function annotator(?object $mapper, ?RuntimeException $container = null): RedactionAnnotator {
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn($mapper === null ? ['files'] : ['files', 'openregister']);
		$di = $this->createMock(ContainerInterface::class);
		if ($container !== null) {
			$di->method('get')->willThrowException($container);
		} else {
			$di->method('get')->with('OCA\OpenRegister\Db\EntityRelationMapper')->willReturn($mapper);
		}

		return new RedactionAnnotator($this->createMock(LoggerInterface::class), $apps, $di);
	}

	/**
	 * A mapper answering fixed rows for file 7, or throwing.
	 *
	 * @param array<int, object>               $relations The relations.
	 * @param array<int, array<string, mixed>> $joined    The joined entity rows.
	 * @param bool                             $fails     Whether the lookup throws.
	 *
	 * @return object
	 */
	private function mapper(array $relations, array $joined, bool $fails = false): object {
		return new class ($relations, $joined, $fails) {
			public function __construct(private array $relations, private array $joined, private bool $fails) {
			}

			public function findByFileId(int $fileId): array {
				if ($this->fails === true || $fileId !== 7) {
					throw new RuntimeException('down');
				}

				return $this->relations;
			}

			public function findEntitiesForFile(int $fileId): array {
				return $this->joined;
			}
		};
	}

	/**
	 * One entity relation.
	 *
	 * @param int         $entityId    The entity id.
	 * @param string|null $replacement The anonymized value.
	 * @param bool|null   $skip        The skip flag; null means the relation has no such method.
	 *
	 * @return object
	 */
	private function relation(int $entityId, ?string $replacement, ?bool $skip = false): object {
		if ($skip === null) {
			return new class ($entityId, $replacement) {
				public function __construct(private int $id, private ?string $value) {
				}

				public function getEntityId(): int {
					return $this->id;
				}

				public function getAnonymizedValue(): ?string {
					return $this->value;
				}
			};
		}

		return new class ($entityId, $replacement, $skip) {
			public function __construct(private int $id, private ?string $value, private bool $skip) {
			}

			public function getEntityId(): int {
				return $this->id;
			}

			public function getAnonymizedValue(): ?string {
				return $this->value;
			}

			public function getSkipAnonymization(): mixed {
				return $this->skip;
			}
		};
	}

	public function testUnavailableAndUnrelatedFilesGiveAPlainDiff(): void {
		$hunks = [['type' => 'removed', 'leftText' => 'Jan', 'rightText' => '']];

		$this->assertSame(['hunks' => $hunks, 'status' => 'unavailable', 'unredactedEntities' => []], $this->annotator(null)->annotate($hunks, 7));
		$this->assertSame(
			['hunks' => $hunks, 'status' => 'unavailable', 'unredactedEntities' => []],
			$this->annotator($this->mapper([], []), new RuntimeException('no OR'))->annotate($hunks, 7)
		);
		$this->assertSame(
			['hunks' => $hunks, 'status' => 'unavailable', 'unredactedEntities' => []],
			$this->annotator($this->mapper([], [], true))->annotate($hunks, 7)
		);
		$this->assertSame(['hunks' => $hunks, 'status' => 'none', 'unredactedEntities' => []], $this->annotator($this->mapper([], []))->annotate($hunks, 7));

	}

	public function testHunksAreMatchedByKeyThenByValue(): void {
		$relations = [
			$this->relation(1, '[PERSOON-1]'),
			$this->relation(2, null),
			$this->relation(3, '[ADRES-1]', true),
			$this->relation(4, '[MAIL-1]', null),
			$this->relation(5, '[IBAN-1]'),
		];
		$joined = [
			['entity_id' => 1, 'entity_type' => 'PERSON', 'entity_name' => 'Jan Jansen', 'entity_value' => 'Jan Jansen'],
			['entity_id' => 2, 'entity_type' => 'PHONE', 'entity_value' => '0612345678'],
			['entity_id' => 3, 'entity_type' => 'ADDRESS', 'entity_value' => 'Straat 1'],
			['entity_id' => 0, 'entity_type' => 'IGNORED', 'entity_value' => 'x'],
			['entity_type' => 'NO-ID', 'entity_value' => 'y'],
			['entity_id' => 5],
		];
		$hunks = [
			['type' => 'unchanged', 'leftText' => 'Jan Jansen', 'rightText' => '[PERSOON-1]'],
			['type' => 'changed', 'leftText' => 'Jan Jansen', 'rightText' => 'Beste [PERSOON-1],'],
			['type' => 'removed', 'leftText' => 'bel 0612345678 nu'],
			['type' => 'changed', 'leftText' => 'Straat 1', 'rightText' => '[ADRES-1]'],
			['type' => 'added', 'rightText' => '[MAIL-1] en [PERSOON-1]'],
			['type' => 'added', 'leftText' => '', 'rightText' => ''],
		];

		$result = $this->annotator($this->mapper($relations, $joined))->annotate($hunks, 7);

		$this->assertSame('annotated', $result['status']);
		$this->assertSame([
			$hunks[0],
			$hunks[1] + ['redaction' => ['entityId' => 1, 'entityType' => 'PERSON', 'matchedBy' => 'key']],
			$hunks[2] + ['redaction' => ['entityId' => 2, 'entityType' => 'PHONE', 'matchedBy' => 'value']],
			$hunks[3],
			$hunks[4] + ['redaction' => ['entityId' => 1, 'entityType' => 'PERSON', 'matchedBy' => 'key']],
			$hunks[5],
		], $result['hunks']);
		$this->assertSame(
			[['entityId' => 4, 'entityName' => ''], ['entityId' => 5, 'entityName' => '']],
			$result['unredactedEntities']
		);

	}

	public function testUnmatchedEntitiesCarryTheirName(): void {
		$relations = [$this->relation(1, '[PERSOON-1]'), $this->relation(2, '[PERSOON-2]')];
		$joined = [
			['entity_id' => 1, 'entity_type' => 'PERSON', 'entity_name' => 'Jan', 'entity_value' => 'Jan'],
			['entity_id' => 2, 'entity_type' => 'PERSON', 'entity_value' => 'Piet'],
		];
		$hunks = [['type' => 'removed', 'leftText' => 'mevrouw']];

		$result = $this->annotator($this->mapper($relations, $joined))->annotate($hunks, 7);

		$this->assertSame($hunks, $result['hunks']);
		$this->assertSame([['entityId' => 1, 'entityName' => 'Jan'], ['entityId' => 2, 'entityName' => 'Piet']], $result['unredactedEntities']);

	}
}
