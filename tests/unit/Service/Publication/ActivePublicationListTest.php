<?php

/**
 * Unit tests for ActivePublicationList.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Publication
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/specify-capability-rows/specs/woo-publicatie-pipeline/spec.md#requirement-documents-that-must-be-published-actively-are-listed-with-what-they-still-lack-req-scr-008
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Publication;

use OCA\Filinq\Service\Publication\ActivePublicationList;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;

/**
 * The records in an active category, published or with what blocks them.
 *
 * @covers \OCA\Filinq\Service\Publication\ActivePublicationList
 */
class ActivePublicationListTest extends TestCase {

	/**
	 * The list over the configured categories.
	 *
	 * @param string $configured The stored JSON.
	 *
	 * @return ActivePublicationList
	 */
	private function list(string $configured): ActivePublicationList {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->with('filinq', ActivePublicationList::CONFIG_KEY, '[]')->willReturn($configured);

		return new ActivePublicationList($config);

	}//end list()

	/**
	 * A complete record of the active category.
	 *
	 * @param array<string, mixed> $override Fields to change.
	 *
	 * @return array<string, mixed>
	 */
	private function record(array $override): array {
		return array_merge(
			[
				'uuid' => 'r', 'wooCategory' => 'c_active', 'status' => 'ready', 'entitiesReviewed' => true,
				'consentClear' => true, 'officieleTitel' => 'Besluit', 'publicatiedatum' => '2026-11-01',
			],
			$override
		);

	}//end record()

	/**
	 * Three records in the category are listed; the unpublished one names its blocker.
	 *
	 * @return void
	 */
	public function testEveryRecordOfTheCategoryIsListedWithWhatItLacks(): void {
		$entries = $this->list('["c_active", " ", 7]')->entries(
			[
				$this->record(['uuid' => 'a', 'status' => 'published']),
				$this->record(['uuid' => 'other', 'wooCategory' => 'c_other']),
				$this->record(['uuid' => 'b', 'status' => 'published']),
				$this->record(['uuid' => 'c', 'status' => 'draft', 'entitiesReviewed' => false]),
			]
		);

		$this->assertSame(['a', 'b', 'c'], array_map(static fn (array $e): string => $e['record']['uuid'], $entries));
		$this->assertSame([true, true, false], array_column($entries, 'published'));
		$this->assertSame([], $entries[0]['blockers']);
		$this->assertSame([ActivePublicationList::BLOCK_REDACTED_COPY], $entries[2]['blockers']);

	}//end testEveryRecordOfTheCategoryIsListedWithWhatItLacks()

	/**
	 * Pending consent, missing metadata and a withdrawal are named.
	 *
	 * @return void
	 */
	public function testEachBlockerIsNamed(): void {
		$entries = $this->list('["c_active"]')->entries(
			[
				$this->record(['consentClear' => false, 'officieleTitel' => ' ']),
				$this->record(['status' => 'depublished', 'entitiesReviewed' => false]),
			]
		);

		$this->assertSame([ActivePublicationList::BLOCK_CONSENT, ActivePublicationList::BLOCK_METADATA], $entries[0]['blockers']);
		$this->assertSame([ActivePublicationList::BLOCK_WITHDRAWN], $entries[1]['blockers']);

	}//end testEachBlockerIsNamed()

	/**
	 * Without configured categories, or with an unreadable value, nothing is listed.
	 *
	 * @return void
	 */
	public function testNoCategoriesListNothing(): void {
		$this->assertSame([], $this->list('not json')->entries([$this->record([])]));
		$this->assertSame([], $this->list('[]')->categories());
		$this->assertSame(['c_active'], $this->list('["c_active","c_active"]')->categories());

	}//end testNoCategoriesListNothing()
}//end class
