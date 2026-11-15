<?php

/**
 * LegalHoldRecordFreeze against OpenRegister's per-matter legal holds.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\LegalHold
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\LegalHold;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\LegalHold\LegalHoldRecordFreeze;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\Archival\LegalHoldServiceInterface;
use OCA\OpenRegister\Service\HoldOrder;
use PHPUnit\Framework\TestCase;

/**
 * Every hold call passes a HoldOrder under filinq's own matter.
 */
class LegalHoldRecordFreezeTest extends TestCase {

	/** @var FakeLegalHoldService OpenRegister's hold service. */
	private FakeLegalHoldService $holds;

	/**
	 * The freeze, wired to the fake hold service.
	 *
	 * @return LegalHoldRecordFreeze The freeze.
	 */
	private function freeze(): LegalHoldRecordFreeze {
		$this->holds = new FakeLegalHoldService();
		$locator     = $this->createMock(OpenRegisterServiceLocator::class);
		$locator->method('get')->willReturnCallback(
			fn (string $class) => ($class === LegalHoldServiceInterface::class ? $this->holds : throw new \RuntimeException('Unknown ' . $class))
		);

		return new LegalHoldRecordFreeze(objectResolver: $this->createMock(DocumentObjectServiceResolver::class), locator: $locator);

	}//end freeze()

	/**
	 * A record.
	 *
	 * @param array<string, mixed>|null $retention The retention.
	 *
	 * @return ObjectEntity The record.
	 */
	private function record(?array $retention = null): ObjectEntity {
		$entity = new ObjectEntity();
		$entity->setUuid('doc-1');
		$entity->setRetention($retention);
		return $entity;

	}//end record()

	/**
	 * Placing passes a HoldOrder naming the case and filinq's matter.
	 *
	 * @return void
	 */
	public function testPlacePassesAHoldOrderUnderFilinqsMatter(): void {
		$freeze = $this->freeze();
		$entity = $this->record();

		$this->assertSame('held', $freeze->place(entity: $entity, caseUuid: 'case-a'));
		$this->assertCount(1, $this->holds->orders);
		$this->assertInstanceOf(HoldOrder::class, $this->holds->orders[0]);
		$this->assertSame(['filinq-hold-case:case-a', LegalHoldRecordFreeze::OWNER_KEY], [$this->holds->orders[0]->reason, $this->holds->orders[0]->ownerKey]);
		$this->assertTrue($entity->hasActiveLegalHold());

	}//end testPlacePassesAHoldOrderUnderFilinqsMatter()

	/**
	 * Release names filinq's matter, so a hold another party placed later stays.
	 *
	 * @return void
	 */
	public function testReleaseLiftsOnlyFilinqsHold(): void {
		$freeze = $this->freeze();
		$entity = $this->record();
		$freeze->place(entity: $entity, caseUuid: 'case-a');
		$this->holds->placeHold($entity, new HoldOrder(reason: 'Rijksarchief inspectie', ownerKey: 'dossiq:bezwaar'));

		$this->assertSame('released', $freeze->release(entity: $entity, survivorUuid: null, releaseReason: 'Settled.'));

		$release = $this->holds->orders[count($this->holds->orders) - 1];
		$this->assertSame([LegalHoldRecordFreeze::OWNER_KEY, 'Settled.'], [$release->ownerKey, $release->reason]);
		$this->assertTrue($entity->hasActiveLegalHold());
		$this->assertSame(['dossiq:bezwaar'], array_column($entity->getRetention()['legalHold']['holds'], 'ownerKey'));

	}//end testReleaseLiftsOnlyFilinqsHold()

	/**
	 * Handing over to another case restates the same hold: no release, no gap.
	 *
	 * @return void
	 */
	public function testHandoverRestatesTheSameHold(): void {
		$freeze = $this->freeze();
		$entity = $this->record();
		$freeze->place(entity: $entity, caseUuid: 'case-a');
		$id = $entity->getRetention()['legalHold']['holds'][0]['id'];

		$this->assertSame('kept_for_other_case', $freeze->release(entity: $entity, survivorUuid: 'case-b', releaseReason: 'Settled.'));

		$this->assertSame(['place:doc-1:filinq-hold-case:case-a', 'place:doc-1:filinq-hold-case:case-b'], $this->holds->calls);
		$holds = $entity->getRetention()['legalHold']['holds'];
		$this->assertSame([[$id, 'filinq-hold-case:case-b']], array_map(fn (array $hold) => [$hold['id'], $hold['reason']], $holds));

	}//end testHandoverRestatesTheSameHold()

	/**
	 * A hold placed before holds were kept per matter is still ours, and its
	 * release names the legacy matter, never no matter at all.
	 *
	 * @return void
	 */
	public function testALegacyHoldIsReleasedUnderTheLegacyMatter(): void {
		$freeze = $this->freeze();
		$entity = $this->record(retention: ['legalHold' => ['active' => true, 'reason' => 'filinq-hold-case:case-a', 'history' => []]]);

		$this->assertSame('held', $freeze->place(entity: $entity, caseUuid: 'case-b'));
		$this->assertSame([], $this->holds->calls);

		$this->assertSame('released', $freeze->release(entity: $entity, survivorUuid: null, releaseReason: 'Settled.'));
		$this->assertSame(FakeLegalHoldService::LEGACY_OWNER, $this->holds->orders[0]->ownerKey);
		$this->assertFalse($entity->hasActiveLegalHold());

	}//end testALegacyHoldIsReleasedUnderTheLegacyMatter()

	/**
	 * Another party's hold is never overwritten nor lifted.
	 *
	 * @return void
	 */
	public function testAnotherPartysHoldIsLeftAlone(): void {
		$freeze = $this->freeze();
		$entity = $this->record(retention: ['legalHold' => ['active' => true, 'reason' => 'Rijksarchief inspectie', 'history' => []]]);

		$this->assertSame('held_by_other', $freeze->place(entity: $entity, caseUuid: 'case-a'));
		$this->assertSame('left_to_other_party', $freeze->release(entity: $entity, survivorUuid: null, releaseReason: 'Settled.'));
		$this->assertSame([], $this->holds->calls);

	}//end testAnotherPartysHoldIsLeftAlone()
}//end class
