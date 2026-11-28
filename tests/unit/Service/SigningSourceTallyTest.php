<?php

/**
 * Unit tests for the count of signing requests per source app
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-26-one-signing-rail-for-the-fleet/specs/signing-rail-consolidation/spec.md#requirement-an-admin-can-see-which-apps-use-the-rail-req-ddsrc-004
 */

declare(strict_types=1);

// phpcs:disable PEAR.Commenting.FunctionComment.Missing -- arrange/act/assert PHPUnit conventions.

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\SigningSourceTally;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OCA\Filinq\Service\SigningSourceTally
 */
final class SigningSourceTallyTest extends TestCase {

	/**
	 * The pages the fake object service answered, in order.
	 *
	 * @var list<int>
	 */
	private array $pages = [];

	/**
	 * A tally over these requests, paged as the object service pages them.
	 *
	 * @param list<array<string, mixed>> $requests The stored signing requests.
	 * @param bool                       $bound    Whether the signing register is configured.
	 *
	 * @return SigningSourceTally
	 */
	private function tally(array $requests, bool $bound = true): SigningSourceTally {
		$objects = $this->getMockBuilder(ObjectService::class)
			->disableOriginalConstructor()
			->onlyMethods(['buildSearchQuery', 'searchObjectsPaginated'])
			->getMock();
		$objects->method('buildSearchQuery')->willReturnCallback(
			static fn (array $requestParams = [], string $register = '', string $schema = ''): array => $requestParams + ['@register' => $register, '@schema' => $schema]
		);
		$objects->method('searchObjectsPaginated')->willReturnCallback(
			function (array $query = [], bool $_rbac = true): array {
				$this->assertFalse($_rbac);
				$this->assertSame(['filinq', 'signingRequest'], [$query['@register'], $query['@schema']]);
				$this->pages[] = (int)$query['_page'];
				$offset = (((int)$query['_page'] - 1) * (int)$query['_limit']);

				return ['results' => array_slice($this->requests, $offset, (int)$query['_limit'])];
			}
		);
		$this->requests = $requests;

		$settings = $this->createMock(SettingsService::class);
		$settings->method('getObjectService')->willReturn($objects);
		$settings->method('resolveSigningRequestBinding')->willReturn($bound === true ? ['register' => 'filinq', 'schema' => 'signingRequest'] : null);

		return new SigningSourceTally(settingsService: $settings);
	}//end tally()

	/**
	 * The stored requests.
	 *
	 * @var list<array<string, mixed>>
	 */
	private array $requests = [];

	public function testEachSourceAppIsListedWithItsCountMostRequestsFirst(): void {
		$requests = [
			['sourceApp' => 'shillinq'],
			['sourceApp' => 'dossiq'],
			['sourceApp' => 'dossiq'],
			['sourceApp' => ' decidiq '],
			['sourceApp' => 'shillinq'],
			['sourceApp' => 'dossiq'],
		];

		$this->assertSame(
			[
				'sources' => [['app' => 'dossiq', 'count' => 3], ['app' => 'shillinq', 'count' => 2], ['app' => 'decidiq', 'count' => 1]],
				'total' => 6,
				'truncated' => false,
			],
			$this->tally(requests: $requests)->bySourceApp()
		);
	}//end testEachSourceAppIsListedWithItsCountMostRequestsFirst()

	public function testARequestRaisedInsideFilinqIsNotCounted(): void {
		$result = $this->tally(requests: [['documentName' => 'a.pdf'], ['sourceApp' => ''], ['sourceApp' => 'dossiq']])->bySourceApp();

		$this->assertSame([['app' => 'dossiq', 'count' => 1]], $result['sources']);
		$this->assertSame(1, $result['total']);
	}//end testARequestRaisedInsideFilinqIsNotCounted()

	public function testNoRequestsIsAnEmptyListNotAnError(): void {
		$this->assertSame(['sources' => [], 'total' => 0, 'truncated' => false], $this->tally(requests: [])->bySourceApp());
	}//end testNoRequestsIsAnEmptyListNotAnError()

	public function testAnUnconfiguredRegisterIsAnEmptyList(): void {
		$this->assertSame(['sources' => [], 'total' => 0, 'truncated' => false], $this->tally(requests: [['sourceApp' => 'x']], bound: false)->bySourceApp());
		$this->assertSame([], $this->pages);
	}//end testAnUnconfiguredRegisterIsAnEmptyList()

	public function testEveryPageIsReadUntilAShortPage(): void {
		$requests = array_fill(0, (SigningSourceTally::PAGE_SIZE + 3), ['sourceApp' => 'dossiq']);

		$result = $this->tally(requests: $requests)->bySourceApp();

		$this->assertSame([1, 2], $this->pages);
		$this->assertSame((SigningSourceTally::PAGE_SIZE + 3), $result['sources'][0]['count']);
	}//end testEveryPageIsReadUntilAShortPage()

	public function testEqualCountsAreOrderedByName(): void {
		$result = $this->tally(requests: [['sourceApp' => 'shillinq'], ['sourceApp' => 'dossiq']])->bySourceApp();

		$this->assertSame(['dossiq', 'shillinq'], array_column($result['sources'], 'app'));
	}//end testEqualCountsAreOrderedByName()
}//end class
