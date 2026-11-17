<?php

/**
 * Unit tests for RefusalGroundLabels
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Grounds
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

namespace OCA\Filinq\Tests\Unit\Service\Grounds;

use OCA\Filinq\Service\Grounds\RefusalGroundsResolver;
use OCA\Filinq\Service\Grounds\RefusalGroundLabels;
use OCA\Filinq\Service\Grounds\RefusalGroundsLegacyMap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Woo grounds read as labels and picker options through the resolver, by
 * code or by old slug, and fail soft when the list or the map cannot be read.
 *
 * @covers \OCA\Filinq\Service\Grounds\RefusalGroundLabels
 * @uses   \OCA\Filinq\Service\Grounds\RefusalGroundsLegacyMap
 */
class RefusalGroundLabelsTest extends TestCase {

	/**
	 * The helper's logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * Set up the logger mock.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * A helper over a resolver answering the list (or throwing it), with the shipped legacy map.
	 *
	 * @param mixed                    $list   The list, or a Throwable to throw.
	 * @param RefusalGroundsLegacyMap|null $legacy The legacy map; the shipped one when omitted.
	 *
	 * @return RefusalGroundLabels
	 */
	private function labelsOver(mixed $list, ?RefusalGroundsLegacyMap $legacy = null): RefusalGroundLabels {
		$grounds = $this->createMock(RefusalGroundsResolver::class);
		if ($list instanceof \Throwable) {
			$grounds->expects($this->once())->method('list')->willThrowException($list);
		} else {
			$grounds->expects($this->once())->method('list')->willReturn($list);
		}

		return new RefusalGroundLabels($grounds, ($legacy ?? new RefusalGroundsLegacyMap()), $this->logger);

	}//end labelsOver()

	/**
	 * The list as read: two usable grounds and two that are skipped.
	 *
	 * @return array<string, mixed>
	 */
	private static function list(): array {
		return [
			'source'  => 'snapshot',
			'version' => '1.0.0',
			'grounds' => [
				['code' => '5.1.2.e', 'label' => 'Persoonlijke levenssfeer', 'description' => 'Privacy.'],
				['code' => '5.1.4', 'label' => 'Geadresseerde'],
				['code' => '', 'label' => 'zonder code'],
				'geen ground',
			],
		];

	}//end list()

	/**
	 * The grounds are keyed by code, skipping a ground without code or label, and read once.
	 *
	 * @return void
	 */
	public function testByCodeKeysTheUsableGroundsAndReadsTheListOnce(): void {
		$labels = $this->labelsOver(self::list());

		$expected = [
			'5.1.2.e' => ['name' => 'Persoonlijke levenssfeer', 'description' => 'Privacy.'],
			'5.1.4'   => ['name' => 'Geadresseerde', 'description' => ''],
		];
		$this->assertSame($expected, $labels->byCode());
		$this->assertSame($expected, $labels->byCode());

	}//end testByCodeKeysTheUsableGroundsAndReadsTheListOnce()

	/**
	 * A code labels directly, an old slug through the map, an unresolvable Woo
	 * reference as unknown, and a tenant's own basis not at all.
	 *
	 * @return void
	 */
	public function testLabelForResolvesCodesAndOldSlugsAndLeavesOtherBases(): void {
		$labels = $this->labelsOver(self::list());

		$this->assertSame(['name' => 'Persoonlijke levenssfeer', 'description' => 'Privacy.'], $labels->labelFor(ref: '5.1.2.e'));
		$this->assertSame(['name' => 'Persoonlijke levenssfeer', 'description' => 'Privacy.'], $labels->labelFor(ref: 'art-5-1-2-e'));
		$this->assertSame(['name' => 'unknown ground 5.9.9', 'description' => ''], $labels->labelFor(ref: '5.9.9'));
		$this->assertSame(['name' => 'unknown ground art-5-1-1-a', 'description' => ''], $labels->labelFor(ref: 'art-5-1-1-a'));
		$this->assertNull($labels->labelFor(ref: 'eigen-grond'));

	}//end testLabelForResolvesCodesAndOldSlugsAndLeavesOtherBases()

	/**
	 * The options carry the code as the stored value.
	 *
	 * @return void
	 */
	public function testOptionsOfferTheCodeAsTheValue(): void {
		$this->assertSame(
			[
				['slug' => '5.1.2.e', 'name' => 'Persoonlijke levenssfeer', 'description' => 'Privacy.'],
				['slug' => '5.1.4', 'name' => 'Geadresseerde', 'description' => ''],
			],
			$this->labelsOver(self::list())->options()
		);

	}//end testOptionsOfferTheCodeAsTheValue()

	/**
	 * An unreadable list gives no labels and no options, logged once, without throwing.
	 *
	 * @return void
	 */
	public function testAnUnreadableListGivesNothingAndIsLogged(): void {
		$this->logger->expects($this->once())->method('warning')->with('Filinq: the Woo refusal grounds cannot be read');

		$labels = $this->labelsOver(new RuntimeException('snapshot gone'));

		$this->assertSame([], $labels->options());
		$this->assertSame(['name' => 'unknown ground 5.1.4', 'description' => ''], $labels->labelFor(ref: '5.1.4'));

	}//end testAnUnreadableListGivesNothingAndIsLogged()

	/**
	 * An unreadable legacy map re-points nothing and is logged; a Woo article code still counts as Woo.
	 *
	 * @return void
	 */
	public function testAnUnreadableMapRepointsNothing(): void {
		$legacy = $this->createMock(RefusalGroundsLegacyMap::class);
		$legacy->method('codeFor')->willThrowException(new RuntimeException('map gone'));
		$legacy->method('isLegacySlug')->willThrowException(new RuntimeException('map gone'));
		$this->logger->expects($this->once())->method('warning')->with('Filinq: the Woo grounds legacy map cannot be read');

		$grounds = $this->createMock(RefusalGroundsResolver::class);
		$labels = new RefusalGroundLabels($grounds, $legacy, $this->logger);

		$this->assertNull($labels->codeFor(ref: 'art-5-1-2-e'));
		$this->assertFalse($labels->isWooReference(ref: 'art-5-1-2-e'));
		$this->assertTrue($labels->isWooReference(ref: '5.1.2.e'));

	}//end testAnUnreadableMapRepointsNothing()

	/**
	 * Without a legacy map an old slug is not recognised and re-points nowhere.
	 *
	 * @return void
	 */
	public function testWithoutAMapOnlyCodesAreWoo(): void {
		$labels = new RefusalGroundLabels($this->createMock(RefusalGroundsResolver::class), null, $this->logger);

		$this->assertNull($labels->codeFor(ref: 'art-5-1-2-e'));
		$this->assertFalse($labels->isWooReference(ref: 'art-5-1-2-e'));

	}//end testWithoutAMapOnlyCodesAreWoo()
}//end class
