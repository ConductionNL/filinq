<?php

/**
 * Unit tests for ScanProfileService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
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

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\ScanProfileService;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Scan profiles are read from app config, matched to a path by the longest
 * watched folder, and declared with defaults filled in.
 *
 * @covers \OCA\Filinq\Service\ScanProfileService
 */
class ScanProfileServiceTest extends TestCase {

	/**
	 * The app config the profiles live in.
	 *
	 * @var IAppConfig&MockObject
	 */
	private IAppConfig&MockObject $config;

	/**
	 * The service's logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * Set up the mocks.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->config = $this->createMock(IAppConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * A service whose stored profiles are the given raw string.
	 *
	 * @param string $raw The stored config value.
	 *
	 * @return ScanProfileService
	 */
	private function serviceWith(string $raw): ScanProfileService {
		$this->config->method('getValueString')
			->with('filinq', 'scanProfiles', '')
			->willReturn($raw);

		return new ScanProfileService($this->config, $this->logger);

	}//end serviceWith()

	/**
	 * Nothing stored means no profiles.
	 *
	 * @return void
	 */
	public function testAllIsEmptyWhenNothingIsStored(): void {
		$this->assertSame([], $this->serviceWith('  ')->all());

	}//end testAllIsEmptyWhenNothingIsStored()

	/**
	 * Invalid JSON is logged as an error and yields no profiles.
	 *
	 * @return void
	 */
	public function testAllLogsAndReturnsEmptyOnInvalidJson(): void {
		$this->logger->expects($this->once())
			->method('error')
			->with($this->stringContains('not valid JSON'));

		$this->assertSame([], $this->serviceWith('{not json')->all());

	}//end testAllLogsAndReturnsEmptyOnInvalidJson()

	/**
	 * A JSON scalar is not a profile list.
	 *
	 * @return void
	 */
	public function testAllIsEmptyForAJsonScalar(): void {
		$this->logger->expects($this->never())->method('error');

		$this->assertSame([], $this->serviceWith('42')->all());

	}//end testAllIsEmptyForAJsonScalar()

	/**
	 * Entries that are not arrays or have no id are dropped.
	 *
	 * @return void
	 */
	public function testAllDropsEntriesWithoutAnId(): void {
		$raw = json_encode([
			['id' => 'front', 'watchedFolder' => '/Scans'],
			['id' => '  ', 'watchedFolder' => '/Other'],
			['watchedFolder' => '/NoId'],
			'loose string',
			['id' => 'back', 'watchedFolder' => '/Back'],
		]);

		$this->assertSame(
			[
				['id' => 'front', 'watchedFolder' => '/Scans'],
				['id' => 'back', 'watchedFolder' => '/Back'],
			],
			$this->serviceWith($raw)->all()
		);

	}//end testAllDropsEntriesWithoutAnId()

	/**
	 * Find returns the profile with that id, or null.
	 *
	 * @return void
	 */
	public function testFindReturnsTheProfileWithThatId(): void {
		$service = $this->serviceWith(json_encode([
			['id' => 'front', 'watchedFolder' => '/Scans'],
			['id' => 'back', 'watchedFolder' => '/Back'],
		]));

		$this->assertSame(['id' => 'back', 'watchedFolder' => '/Back'], $service->find('back'));
		$this->assertNull($service->find('side'));

	}//end testFindReturnsTheProfileWithThatId()

	/**
	 * The deepest watched folder containing the path wins; the root folder never matches.
	 *
	 * @return void
	 */
	public function testForPathPicksTheLongestMatchingFolder(): void {
		$service = $this->serviceWith(json_encode([
			['id' => 'root', 'watchedFolder' => '/'],
			['id' => 'scans', 'watchedFolder' => 'Scans/'],
			['id' => 'deep', 'watchedFolder' => '/Scans/Inbox'],
			['id' => 'prefix', 'watchedFolder' => '/Scan'],
		]));

		$this->assertSame('deep', $service->forPath('/Scans/Inbox/batch.pdf')['id']);
		$this->assertSame('scans', $service->forPath('Scans/other.pdf')['id']);
		$this->assertSame('scans', $service->forPath('/Scans')['id']);
		$this->assertNull($service->forPath('/Documents/file.pdf'));

	}//end testForPathPicksTheLongestMatchingFolder()

	/**
	 * Declaring fills defaults and stores the normalised list as JSON.
	 *
	 * @return void
	 */
	public function testDeclareFillsDefaultsAndStoresTheList(): void {
		$expected = [
			[
				'id' => 'front',
				'label' => 'front',
				'watchedFolder' => '/Scans',
				'separatorMode' => 'qr',
				'inkThreshold' => 4096,
				'targetFolder' => '/Scans',
			],
			[
				'id' => 'back',
				'label' => 'Back office',
				'watchedFolder' => '/Back',
				'separatorMode' => 'blankPage',
				'inkThreshold' => 100,
				'targetFolder' => '/Sorted',
			],
		];

		$this->config->expects($this->once())
			->method('setValueString')
			->with('filinq', 'scanProfiles', json_encode($expected));

		$service = new ScanProfileService($this->config, $this->logger);
		$stored  = $service->declare([
			['id' => ' front ', 'watchedFolder' => ' /Scans '],
			[
				'id' => 'back',
				'label' => 'Back office',
				'watchedFolder' => '/Back',
				'separatorMode' => 'blankPage',
				'inkThreshold' => '100',
				'targetFolder' => '/Sorted',
			],
		]);

		$this->assertSame($expected, $stored);

	}//end testDeclareFillsDefaultsAndStoresTheList()

	/**
	 * A profile without a folder is refused and nothing is stored.
	 *
	 * @return void
	 */
	public function testDeclareRefusesAProfileWithoutAFolder(): void {
		$this->config->expects($this->never())->method('setValueString');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('A scan profile names an id and the folder its scanner writes to.');

		(new ScanProfileService($this->config, $this->logger))->declare([['id' => 'front']]);

	}//end testDeclareRefusesAProfileWithoutAFolder()

	/**
	 * An unknown separator mode is refused.
	 *
	 * @return void
	 */
	public function testDeclareRefusesAnUnknownSeparatorMode(): void {
		$this->config->expects($this->never())->method('setValueString');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('A scan profile cuts on qr or on blankPage, not on "barcode".');

		(new ScanProfileService($this->config, $this->logger))->declare([
			['id' => 'front', 'watchedFolder' => '/Scans', 'separatorMode' => 'barcode'],
		]);

	}//end testDeclareRefusesAnUnknownSeparatorMode()
}//end class
