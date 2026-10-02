<?php

/**
 * Unit tests for ContractService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Contract
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#4-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Contract;

require_once __DIR__ . '/ContractSchemaValidation.php';
require_once __DIR__ . '/InMemoryContracts.php';

use InvalidArgumentException;
use OCA\Filinq\Service\Contract\ContractNotFoundException;
use OCA\Filinq\Service\Contract\ContractService;
use PHPUnit\Framework\TestCase;

/**
 * Renew, terminate and suggestion decisions, against an in-memory store that
 * keeps whole objects, with every written payload validated against the real
 * contract schema.
 */
class ContractServiceTest extends TestCase {
	use ContractSchemaValidation;

	/**
	 * The store.
	 *
	 * @var InMemoryContracts
	 */
	private InMemoryContracts $store;

	/**
	 * The service under test.
	 *
	 * @var ContractService
	 */
	private ContractService $service;

	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->store = new InMemoryContracts();
		$this->service = new ContractService(repository: $this->store);

	}//end setUp()

	/**
	 * An active contract with every field the renewal carries.
	 *
	 * @return string The uuid.
	 */
	private function activeContract(): string {
		return $this->store->seed(
			[
				'title' => 'Raamovereenkomst groenonderhoud',
				'contractType' => 'inkoop',
				'parties' => [['contactRef' => 'urn:nc:contact:00000000-0000-0000-0000-000000000010', 'role' => 'opdrachtnemer'], ['role' => 'opdrachtgever', 'displayName' => 'Gemeente Demostad']],
				'internalOwner' => 'alice',
				'startDate' => '2026-01-01',
				'endDate' => '2028-12-31',
				'noticePeriodDays' => 90,
				'noticeDeadline' => '2028-10-02',
				'renewalType' => 'manual',
				'value' => 240000,
				'currency' => 'EUR',
				'status' => 'active',
				'documents' => ['42'],
				'notes' => 'Blijft staan.',
			]
		);

	}//end activeContract()

	/**
	 * Renewal makes a draft successor that carries parties, type, owner and
	 * value, links both ways, marks the original renewed, and keeps every
	 * field of the original it did not change.
	 *
	 * @return void
	 */
	public function testRenewalCreatesALinkedSuccessor(): void {
		$uuid = $this->activeContract();

		$result = $this->service->renew(uuid: $uuid);

		$successor = $this->store->rows[$result['successor']['uuid']];
		$original = $this->store->rows[$uuid];
		$this->assertSame('draft', $successor['status']);
		$this->assertSame($uuid, $successor['renews']);
		$this->assertSame($result['successor']['uuid'], $original['renewedBy']);
		$this->assertSame('renewed', $original['status']);
		foreach (['title', 'contractType', 'parties', 'internalOwner', 'value', 'currency'] as $carried) {
			$this->assertSame($original[$carried], $successor[$carried], $carried . ' is carried forward');
		}

		$this->assertArrayNotHasKey('endDate', $successor);
		$this->assertSame('Blijft staan.', $original['notes']);
		$this->assertSame(['42'], $original['documents']);
		foreach ($this->store->writes as $write) {
			$this->assertValidContract($write);
		}

	}//end testRenewalCreatesALinkedSuccessor()

	/**
	 * Termination without a reason is refused and writes nothing; with a
	 * reason it stores the reason and the status.
	 *
	 * @return void
	 */
	public function testTerminationNeedsAReason(): void {
		$uuid = $this->activeContract();

		try {
			$this->service->terminate(uuid: $uuid, reason: '   ');
			$this->fail('A termination without a reason must be refused.');
		} catch (InvalidArgumentException $e) {
			$this->assertSame(400, $e->getCode());
		}

		$this->assertSame([], $this->store->writes);
		$this->assertSame('active', $this->store->rows[$uuid]['status']);

		$stored = $this->service->terminate(uuid: $uuid, reason: 'Leverancier failliet');

		$this->assertSame('terminated', $stored['status']);
		$this->assertSame('Leverancier failliet', $stored['terminationReason']);
		$this->assertSame(240000, $stored['value']);
		$this->assertValidContract($this->store->writes[0]);

	}//end testTerminationNeedsAReason()

	/**
	 * Only an active contract is renewed or terminated.
	 *
	 * @return void
	 */
	public function testADraftIsNeitherRenewedNorTerminated(): void {
		$uuid = $this->store->seed(['title' => 'Concept', 'status' => 'draft']);

		foreach ([fn () => $this->service->renew(uuid: $uuid), fn () => $this->service->terminate(uuid: $uuid, reason: 'x')] as $action) {
			try {
				$action();
				$this->fail('A draft must be refused.');
			} catch (InvalidArgumentException $e) {
				$this->assertSame(409, $e->getCode());
			}
		}

		$this->assertSame([], $this->store->writes);

	}//end testADraftIsNeitherRenewedNorTerminated()

	/**
	 * A contract the caller cannot read is not found, and nothing is written.
	 *
	 * @return void
	 */
	public function testAnUnreadableContractIsNotFound(): void {
		$this->expectException(ContractNotFoundException::class);

		$this->service->renew(uuid: 'not-mine');

	}//end testAnUnreadableContractIsNotFound()

	/**
	 * Accepting writes the value (and derives the notice deadline); rejecting
	 * only marks the suggestion; a decided suggestion is not decided again.
	 *
	 * @return void
	 */
	public function testSuggestionsProposeAndThePersonDisposes(): void {
		$uuid = $this->store->seed(
			[
				'title' => 'Schoonmaak',
				'status' => 'active',
				'noticePeriodDays' => 90,
				'keyTermSuggestions' => [
					['field' => 'endDate', 'value' => '2028-12-31', 'confidence' => 0.8, 'source' => 'file:42', 'status' => 'proposed'],
					['field' => 'value', 'value' => '86000', 'confidence' => 0.75, 'source' => 'file:42', 'status' => 'proposed'],
				],
			]
		);

		$this->service->decideSuggestion(uuid: $uuid, index: 0, decision: 'accepted');
		$stored = $this->service->decideSuggestion(uuid: $uuid, index: 1, decision: 'rejected');

		$this->assertSame('2028-12-31', $stored['endDate']);
		$this->assertSame('2028-10-02', $stored['noticeDeadline']);
		$this->assertArrayNotHasKey('value', $stored);
		$this->assertSame(['accepted', 'rejected'], array_column($stored['keyTermSuggestions'], 'status'));
		foreach ($this->store->writes as $write) {
			$this->assertValidContract($write);
		}

		$this->expectExceptionCode(409);
		$this->service->decideSuggestion(uuid: $uuid, index: 1, decision: 'accepted');

	}//end testSuggestionsProposeAndThePersonDisposes()

	/**
	 * Each accepted field type lands in the type its schema property declares.
	 *
	 * @return void
	 */
	public function testAcceptedValuesTakeTheirFieldsType(): void {
		$uuid = $this->store->seed(
			[
				'title' => 'Types',
				'status' => 'draft',
				'keyTermSuggestions' => [
					['field' => 'noticePeriodDays', 'value' => '60', 'status' => 'proposed'],
					['field' => 'value', 'value' => '1250.5', 'status' => 'proposed'],
					['field' => 'currency', 'value' => 'eur', 'status' => 'proposed'],
					['field' => 'party', 'value' => 'Schoon B.V.', 'status' => 'proposed'],
					['field' => 'startDate', 'value' => 'soon', 'status' => 'proposed'],
				],
			]
		);

		foreach ([0, 1, 2, 3] as $index) {
			$stored = $this->service->decideSuggestion(uuid: $uuid, index: $index, decision: 'accepted');
		}

		$this->assertSame(60, $stored['noticePeriodDays']);
		$this->assertSame(1250.5, $stored['value']);
		$this->assertSame('EUR', $stored['currency']);
		$this->assertSame([['displayName' => 'Schoon B.V.']], $stored['parties']);
		$this->assertValidContract(end($this->store->writes));

		$this->expectExceptionCode(422);
		$this->service->decideSuggestion(uuid: $uuid, index: 4, decision: 'accepted');

	}//end testAcceptedValuesTakeTheirFieldsType()
}//end class
