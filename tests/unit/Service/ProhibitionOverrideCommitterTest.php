<?php

/**
 * Unit tests for ProhibitionOverrideCommitter
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
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use ArrayIterator;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCA\Filinq\Service\ProhibitionOverrideCommitter;
use OCA\OpenRegister\Db\EntityRelationMapper;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * An override is only applied when its audit entry was written, and a
 * failed skip-flag patch stops the run.
 */
#[CoversClass(ProhibitionOverrideCommitter::class)]
class ProhibitionOverrideCommitterTest extends TestCase {

	/**
	 * The service locator.
	 *
	 * @var OpenRegisterServiceLocator&MockObject
	 */
	private OpenRegisterServiceLocator $locator;

	/**
	 * The logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface $logger;

	/**
	 * The object service.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService $objects;

	/**
	 * The relation mapper.
	 *
	 * @var EntityRelationMapper&MockObject
	 */
	private EntityRelationMapper $relations;

	/**
	 * The subject.
	 *
	 * @var ProhibitionOverrideCommitter
	 */
	private ProhibitionOverrideCommitter $committer;

	/**
	 * Build the subject over a locator handing out mocks.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->locator = $this->createMock(OpenRegisterServiceLocator::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->objects = $this->createMock(ObjectService::class);
		$this->relations = $this->createMock(EntityRelationMapper::class);
		$this->committer = new ProhibitionOverrideCommitter(logger: $this->logger, locator: $this->locator);
	}//end setUp()

	/**
	 * Wire the locator to hand out both services.
	 *
	 * @return void
	 */
	private function locatorServes(): void {
		$this->locator->method('get')->willReturnMap([
			['OCA\OpenRegister\Service\ObjectService', $this->objects],
			['OCA\OpenRegister\Db\EntityRelationMapper', $this->relations],
		]);
	}//end locatorServes()

	/**
	 * Each released override writes an audit entry, then patches its relation.
	 *
	 * @return void
	 */
	public function testCommitAuditsAndPatchesEachOverride(): void {
		$this->locatorServes();
		$audits = [];
		$this->objects->expects($this->exactly(2))
			->method('saveObject')
			->willReturnCallback(function (...$args) use (&$audits) {
				// Positional: object, register, schema, uuid, _rbac, _multitenancy, _unowned.
				$audits[] = $args;
				return [];
			});
		$this->relations->expects($this->once())
			->method('updateDecisionMetadata')
			->with(11, ['skipAnonymization' => true]);

		$this->committer->commit(
			released: [
				['match' => ['ruleId' => 'rule-a', 'entityRelationId' => 11], 'reason' => 'Public figure'],
				['match' => ['ruleId' => 'rule-b'], 'reason' => 'No relation'],
			],
			fileId: 99,
			userId: 'alice'
		);

		$this->assertCount(2, $audits);
		$entry = $audits[0][0];
		$this->assertSame('rule-a', $entry['ruleId']);
		$this->assertSame(11, $entry['entityRelationId']);
		$this->assertSame(99, $entry['fileId']);
		$this->assertSame('Public figure', $entry['reason']);
		$this->assertSame('alice', $entry['acknowledgedBy']);
		$this->assertNotFalse(\DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $entry['acknowledgedAt']));
		$this->assertSame('filinq', $audits[0][1]);
		$this->assertSame('prohibitionOverrideAudit', $audits[0][2]);
		$this->assertFalse($audits[0][4]);
		$this->assertTrue($audits[0][6]);
		$this->assertSame(0, $audits[1][0]['entityRelationId']);
	}//end testCommitAuditsAndPatchesEachOverride()

	/**
	 * When OpenRegister is unavailable the commit is skipped with a warning.
	 *
	 * @return void
	 */
	public function testCommitIsSkippedWhenServicesAreUnavailable(): void {
		$this->locator->method('get')->willThrowException(new \RuntimeException('OR disabled'));
		$this->logger->expects($this->once())
			->method('warning')
			->with('ProhibitionGate: OR services unavailable — skipping override commit', ['error' => 'OR disabled']);

		$this->committer->commit(
			released: [['match' => ['ruleId' => 'r', 'entityRelationId' => 1], 'reason' => 'x']],
			fileId: 1,
			userId: 'u'
		);
	}//end testCommitIsSkippedWhenServicesAreUnavailable()

	/**
	 * A failed audit write aborts before the relation is patched.
	 *
	 * @return void
	 */
	public function testAFailedAuditAbortsBeforeThePatch(): void {
		$this->locatorServes();
		$this->objects->method('saveObject')->willThrowException(new \RuntimeException('write denied'));
		$this->relations->expects($this->never())->method('updateDecisionMetadata');
		$this->logger->expects($this->once())
			->method('error')
			->with(
				'ProhibitionGate: failed to write audit entry — aborting override commit',
				['ruleId' => 'rule-a', 'relationId' => 5, 'fileId' => 3, 'error' => 'write denied']
			);

		try {
			$this->committer->commit(
				released: [['match' => ['ruleId' => 'rule-a', 'entityRelationId' => 5], 'reason' => 'r']],
				fileId: 3,
				userId: 'u'
			);
			$this->fail('Expected a RuntimeException');
		} catch (RuntimeException $e) {
			$this->assertSame('ProhibitionGate: refusing to commit override without audit entry: write denied', $e->getMessage());
			$this->assertSame(500, $e->getCode());
		}
	}//end testAFailedAuditAbortsBeforeThePatch()

	/**
	 * A failed patch stops processing the remaining overrides.
	 *
	 * @return void
	 */
	public function testAFailedPatchStopsTheRun(): void {
		$this->locatorServes();
		$this->objects->expects($this->once())->method('saveObject')->willReturn([]);
		$this->relations->method('updateDecisionMetadata')->willThrowException(new \RuntimeException('patch failed'));
		$this->logger->expects($this->once())
			->method('error')
			->with(
				'ProhibitionGate: OR PATCH failed — stopping override processing',
				['ruleId' => 'rule-a', 'relationId' => 8, 'fileId' => 4, 'error' => 'patch failed']
			);

		try {
			$this->committer->commit(
				released: [
					['match' => ['ruleId' => 'rule-a', 'entityRelationId' => 8], 'reason' => 'r'],
					['match' => ['ruleId' => 'rule-b', 'entityRelationId' => 9], 'reason' => 'r'],
				],
				fileId: 4,
				userId: 'u'
			);
			$this->fail('Expected a RuntimeException');
		} catch (RuntimeException $e) {
			$this->assertSame('ProhibitionGate: failed to update EntityRelation skip flag: patch failed', $e->getMessage());
			$this->assertSame(500, $e->getCode());
		}
	}//end testAFailedPatchStopsTheRun()

	/**
	 * recordRelease writes one audit entry with the given fields.
	 *
	 * @return void
	 */
	public function testRecordReleaseWritesTheAudit(): void {
		$this->locator->method('get')->with('OCA\OpenRegister\Service\ObjectService')->willReturn($this->objects);
		$this->objects->expects($this->once())
			->method('saveObject')
			->with(
				$this->callback(static fn (array $entry): bool => $entry['ruleId'] === 'rule-z'
					&& $entry['entityRelationId'] === 12
					&& $entry['fileId'] === 34
					&& $entry['reason'] === 'Consent on file'
					&& $entry['acknowledgedBy'] === 'bob'),
				'filinq',
				'prohibitionOverrideAudit'
			)
			->willReturn([]);

		$this->committer->recordRelease(ruleId: 'rule-z', relationId: 12, fileId: 34, reason: 'Consent on file', userId: 'bob');
	}//end testRecordReleaseWritesTheAudit()

	/**
	 * recordRelease refuses when the object service cannot be located.
	 *
	 * @return void
	 */
	public function testRecordReleaseRefusesWithoutTheObjectService(): void {
		$cause = new \RuntimeException('missing');
		$this->locator->method('get')->willThrowException($cause);

		try {
			$this->committer->recordRelease(ruleId: 'r', relationId: 1, fileId: 1, reason: 'x', userId: 'u');
			$this->fail('Expected a RuntimeException');
		} catch (RuntimeException $e) {
			$this->assertSame('The override could not be recorded, so it was not applied.', $e->getMessage());
			$this->assertSame(500, $e->getCode());
			$this->assertSame($cause, $e->getPrevious());
		}
	}//end testRecordReleaseRefusesWithoutTheObjectService()

	/**
	 * recordRelease refuses when the locator hands back a non-object.
	 *
	 * @return void
	 */
	public function testRecordReleaseRefusesANonObjectService(): void {
		$this->locator->method('get')->willReturn(null);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The override could not be recorded, so it was not applied.');
		$this->expectExceptionCode(500);
		$this->committer->recordRelease(ruleId: 'r', relationId: 1, fileId: 1, reason: 'x', userId: 'u');
	}//end testRecordReleaseRefusesANonObjectService()
}//end class
