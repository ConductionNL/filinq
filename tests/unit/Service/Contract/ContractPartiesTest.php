<?php

/**
 * Unit tests for ContractParties
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
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Contract;

use OCA\Filinq\Service\Contract\ContractParties;
use OCP\Contacts\IManager;
use PHPUnit\Framework\TestCase;

/**
 * A party linked to a contact takes its name from the contact; a party
 * without one, or whose contact is gone, keeps the name stored on the contract.
 */
class ContractPartiesTest extends TestCase {

	/**
	 * The searches made, as [pattern, properties, options].
	 *
	 * @var array<int, array<int, mixed>>
	 */
	private array $searches = [];

	/**
	 * A contacts manager holding one contact.
	 *
	 * @param bool $enabled Whether an address book is available.
	 *
	 * @return ContractParties
	 */
	private function parties(bool $enabled=true): ContractParties {
		$manager = $this->createMock(IManager::class);
		$manager->method('isEnabled')->willReturn($enabled);
		$manager->method('search')->willReturnCallback(
			function ($pattern, $properties=[], $options=[]): array {
				$this->searches[] = [$pattern, $properties, $options];
				$found = [
					// A contact whose UID merely contains the one asked for.
					['UID' => '00000000-0000-0000-0000-0000000000100', 'FN' => 'Wrong B.V.'],
					['UID' => '00000000-0000-0000-0000-000000000010', 'FN' => 'Groenbedrijf Demostad B.V.', 'EMAIL' => ['info@groen.example']],
				];

				return array_values(array_filter($found, fn (array $c): bool => str_contains($c['UID'], (string) $pattern)));
			}
		);

		return new ContractParties(contacts: $manager);

	}//end parties()

	/**
	 * The linked party renders from its contact, the other from its stored name.
	 *
	 * @return void
	 */
	public function testTheContactIsTheSourceOfTruth(): void {
		$resolved = $this->parties()->resolve(
			contract: [
				'parties' => [
					['contactRef' => 'urn:nc:contact:00000000-0000-0000-0000-000000000010', 'role' => 'opdrachtnemer', 'displayName' => 'Oude naam B.V.'],
					['role' => 'opdrachtgever', 'displayName' => 'Gemeente Demostad'],
				],
			]
		);

		$this->assertSame('Groenbedrijf Demostad B.V.', $resolved[0]['displayName']);
		$this->assertTrue($resolved[0]['linked']);
		$this->assertSame('info@groen.example', $resolved[0]['email']);
		$this->assertSame('opdrachtnemer', $resolved[0]['role']);
		$this->assertSame('Gemeente Demostad', $resolved[1]['displayName']);
		$this->assertFalse($resolved[1]['linked']);
		$this->assertSame(['UID'], $this->searches[0][1]);

	}//end testTheContactIsTheSourceOfTruth()

	/**
	 * A contact that is gone, a reference that is not a contact, and no address book: the stored name.
	 *
	 * @return void
	 */
	public function testTheStoredNameIsTheFallback(): void {
		$contract = [
			'parties' => [
				['contactRef' => 'urn:nc:contact:ffffffff-0000-0000-0000-000000000000', 'displayName' => 'Vertrokken B.V.'],
				['contactRef' => 'https://example.org/party/1', 'displayName' => 'Extern'],
			],
		];

		$resolved = $this->parties()->resolve(contract: $contract);
		$this->assertSame(['Vertrokken B.V.', 'Extern'], array_column($resolved, 'displayName'));
		$this->assertSame([false, false], array_column($resolved, 'linked'));
		$this->assertCount(1, $this->searches, 'a reference that is no contact is not searched');

		$offline = $this->parties(enabled: false)->resolve(
			contract: ['parties' => [['contactRef' => 'urn:nc:contact:00000000-0000-0000-0000-000000000010', 'displayName' => 'Opgeslagen']]]
		);
		$this->assertSame('Opgeslagen', $offline[0]['displayName']);
		$this->assertFalse($offline[0]['linked']);

	}//end testTheStoredNameIsTheFallback()

	/**
	 * A contract without parties, or with a party that is not an object, gives what it can.
	 *
	 * @return void
	 */
	public function testOddShapesDoNotBreak(): void {
		$this->assertSame([], $this->parties()->resolve(contract: []));
		$this->assertSame([], $this->parties()->resolve(contract: ['parties' => ['text']]));

	}//end testOddShapesDoNotBreak()
}//end class
