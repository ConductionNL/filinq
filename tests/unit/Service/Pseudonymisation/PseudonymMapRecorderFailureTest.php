<?php

/**
 * Unit tests for the failure paths of PseudonymMapRecorder.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Pseudonymisation
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Pseudonymisation;

use OCA\Filinq\Service\AnonymizationPersistenceService;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCA\Filinq\Service\Pseudonymisation\PseudonymMapRecorder;
use OCA\Filinq\Service\Pseudonymisation\PseudonymMapService;
use OCA\Filinq\Service\Pseudonymisation\PseudonymPairs;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * A key that cannot be kept or removed says so in the result and the log,
 * and never claims otherwise.
 *
 * @covers \OCA\Filinq\Service\Pseudonymisation\PseudonymMapRecorder
 * @uses   \OCA\Filinq\Service\Pseudonymisation\PseudonymPairs
 */
class PseudonymMapRecorderFailureTest extends TestCase {

	/**
	 * @var PseudonymMapService&MockObject
	 */
	private PseudonymMapService $maps;

	/**
	 * @var AnonymizationPersistenceService&MockObject
	 */
	private AnonymizationPersistenceService $persistence;

	/**
	 * @var OpenRegisterServiceLocator&MockObject
	 */
	private OpenRegisterServiceLocator $locator;

	/**
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface $logger;

	/**
	 * Build the collaborators; the entity mapper knows both people.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->maps = $this->createMock(PseudonymMapService::class);
		$this->persistence = $this->createMock(AnonymizationPersistenceService::class);
		$this->locator = $this->createMock(OpenRegisterServiceLocator::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$mapper = new class {
			/**
			 * Entity ids by value for a file.
			 *
			 * @param int $fileId The file.
			 *
			 * @return array<string, array<string, mixed>>
			 */
			public function findEntityIdsByValueForFile(int $fileId): array {
				return [
					'Jan Jansen' => ['id' => 41, 'type' => 'PERSON'],
					'Marieke de Vries' => ['id' => 42, 'type' => 'PERSON'],
				];
			}
		};
		$this->locator->method('get')->willReturn($mapper);

	}//end setUp()

	/**
	 * The recorder under test.
	 *
	 * @return PseudonymMapRecorder
	 */
	private function recorder(): PseudonymMapRecorder {
		return new PseudonymMapRecorder(
			pairs: new PseudonymPairs(),
			maps: $this->maps,
			persistence: $this->persistence,
			locator: $this->locator,
			logger: $this->logger
		);

	}//end recorder()

	/**
	 * A run over file 812200 with two people.
	 *
	 * @param bool $reversible Whether the run keeps a key.
	 *
	 * @return array<string, mixed>
	 */
	private function aRun(bool $reversible): array {
		return [
			'fileId' => 812200,
			'entities' => [
				['text' => 'Jan Jansen', 'entityType' => 'PERSON', 'key' => 'k1'],
				['text' => 'Marieke de Vries', 'entityType' => 'PERSON', 'key' => 'k2'],
			],
			'placeholderMap' => ['41' => '[PERSOON: 1]', '42' => '[PERSOON: 2]'],
			'reversible' => $reversible,
			'userId' => 'alice',
		];

	}//end aRun()

	/**
	 * A reversible run whose link was not recorded keeps no key.
	 *
	 * @return void
	 */
	public function testAReversibleRunWithoutALinkKeepsNoKey(): void {
		$this->maps->expects($this->never())->method('store');

		$result = $this->recorder()->record(resultInfo: ['anonymizedFileId' => 812201], run: $this->aRun(reversible: true));

		$this->assertSame(['reversible' => true, 'keyKept' => false, 'reason' => 'link_not_recorded'], $result['pseudonymisation']);

	}//end testAReversibleRunWithoutALinkKeepsNoKey()

	/**
	 * A key that cannot be stored is reported as not kept, logged, and no
	 * mapping reference is written.
	 *
	 * @return void
	 */
	public function testAKeyThatCannotBeStoredIsReportedNotKept(): void {
		$this->maps->expects($this->once())->method('store')
			->with('link-1', 812200, $this->countOf(2), 'document', 'alice')
			->willThrowException(new RuntimeException('register unavailable'));
		$this->persistence->expects($this->never())->method('setMappingRef');
		$this->logger->expects($this->once())->method('error')->with(
			'[PseudonymMapRecorder] the key of a reversible run was not kept',
			['fileId' => 812200, 'linkId' => 'link-1', 'error' => 'register unavailable']
		);

		$result = $this->recorder()->record(resultInfo: ['anonymizationLinkId' => 'link-1'], run: $this->aRun(reversible: true));

		$this->assertSame(['reversible' => true, 'keyKept' => false, 'reason' => 'store_failed'], $result['pseudonymisation']);

	}//end testAKeyThatCannotBeStoredIsReportedNotKept()

	/**
	 * An earlier key that cannot be removed is reported as still there.
	 *
	 * @return void
	 */
	public function testAnEarlierKeyThatCannotBeRemovedIsReported(): void {
		$this->maps->method('deleteForLink')->with('link-1')->willThrowException(new RuntimeException('locked'));
		$this->persistence->expects($this->never())->method('setMappingRef');
		$this->logger->expects($this->once())->method('error')->with(
			'[PseudonymMapRecorder] a key from an earlier run could not be removed',
			['fileId' => 812200, 'linkId' => 'link-1', 'error' => 'locked']
		);

		$result = $this->recorder()->record(resultInfo: ['anonymizationLinkId' => 'link-1'], run: $this->aRun(reversible: false));

		$this->assertSame(['reversible' => false, 'previousKeyRemoved' => false], $result['pseudonymisation']);

	}//end testAnEarlierKeyThatCannotBeRemovedIsReported()

	/**
	 * Without an earlier key, an irreversible run leaves the result and the
	 * link's mapping reference alone.
	 *
	 * @return void
	 */
	public function testWithoutAnEarlierKeyNothingIsTouched(): void {
		$this->maps->expects($this->once())->method('deleteForLink')->with('link-1')->willReturn(false);
		$this->persistence->expects($this->never())->method('setMappingRef');

		$resultInfo = ['anonymizationLinkId' => 'link-1'];
		$this->assertSame($resultInfo, $this->recorder()->record(resultInfo: $resultInfo, run: $this->aRun(reversible: false)));

	}//end testWithoutAnEarlierKeyNothingIsTouched()

	/**
	 * When the entity rows cannot be read there are no placeholders to keep:
	 * the read is logged and the earlier key is removed.
	 *
	 * @return void
	 */
	public function testUnreadableEntityRowsMeanNoPlaceholders(): void {
		$locator = $this->createMock(OpenRegisterServiceLocator::class);
		$locator->method('get')->willThrowException(new RuntimeException('mapper missing'));
		$this->locator = $locator;
		$this->logger->expects($this->once())->method('warning')->with(
			'[PseudonymMapRecorder] the entity rows of the file could not be read',
			['fileId' => 812200, 'error' => 'mapper missing']
		);
		$this->maps->expects($this->never())->method('store');
		$this->maps->expects($this->once())->method('deleteForLink')->with('link-1')->willReturn(true);
		$this->persistence->expects($this->once())->method('setMappingRef')->with(812200, '');

		$result = $this->recorder()->record(resultInfo: ['anonymizationLinkId' => 'link-1'], run: $this->aRun(reversible: true));

		$this->assertSame(['reversible' => true, 'keyKept' => false, 'reason' => 'no_placeholders'], $result['pseudonymisation']);

	}//end testUnreadableEntityRowsMeanNoPlaceholders()
}//end class
