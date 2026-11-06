<?php

/**
 * Tests for the download gated on an accepted agreement.
 *
 * 🔴 THE CASE THAT DECIDES THE DESIGN IS THE STORE THAT REFUSES THE WRITE. The
 * convenient order serves the file and records the acceptance afterwards, best
 * effort, because the reader is standing there and the store is usually up.
 * What that produces is a file out of the building with nothing saying anybody
 * agreed to anything, which is exactly the evidence the agreement exists to
 * create. So the last test here asserts that nothing is served.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\Redaction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Redaction;

use OCA\Filinq\Service\Redaction\DownloadAgreementGate;
use OCA\Filinq\Service\Redaction\DownloadAgreementRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for DownloadAgreementGate.
 */
class DownloadAgreementGateTest extends TestCase {

	/**
	 * The terms in force in most of these cases.
	 *
	 * @var array<string, mixed>
	 */
	private const TERMS = [
		'version' => '2',
		'text' => 'Hergebruik is toegestaan met bronvermelding.',
		'locale' => 'nl',
	];

	/**
	 * The store.
	 *
	 * @var DownloadAgreementRepository&MockObject
	 */
	private DownloadAgreementRepository $store;

	/**
	 * The gate under test.
	 *
	 * @var DownloadAgreementGate
	 */
	private DownloadAgreementGate $gate;

	/**
	 * Wire the gate over a store that holds nothing by default.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->store = $this->getMockBuilder(DownloadAgreementRepository::class)
			->disableOriginalConstructor()
			->onlyMethods(['forDocument', 'acceptanceOf', 'recordAcceptance'])
			->getMock();

		$this->gate = new DownloadAgreementGate(agreements: $this->store);

	}//end setUp()

	/**
	 * A file nobody gated downloads as it always did.
	 *
	 * @return void
	 */
	public function testAnUngatedFileIsNotHeldUp(): void {
		$this->store->method('forDocument')->willReturn(null);

		$decision = $this->gate->check(document: 'doc-1', person: 'sem');

		$this->assertTrue($decision['mayDownload']);
		$this->assertFalse($decision['gated']);

	}//end testAnUngatedFileIsNotHeldUp()

	/**
	 * A gated file is refused until the reader has accepted, and the terms are shown.
	 *
	 * @return void
	 */
	public function testAGatedFileIsRefusedUntilAccepted(): void {
		$this->store->method('forDocument')->willReturn(self::TERMS);
		$this->store->method('acceptanceOf')->willReturn(null);

		$decision = $this->gate->check(document: 'doc-1', person: 'sem');

		$this->assertFalse($decision['mayDownload']);
		$this->assertSame(DownloadAgreementGate::NOT_ACCEPTED, $decision['reason']);
		$this->assertSame('Hergebruik is toegestaan met bronvermelding.', $decision['agreement']['text']);

	}//end testAGatedFileIsRefusedUntilAccepted()

	/**
	 * A reader who accepted the version in force gets the file.
	 *
	 * @return void
	 */
	public function testAReaderWhoAcceptedTheCurrentVersionMayDownload(): void {
		$this->store->method('forDocument')->willReturn(self::TERMS);
		$this->store->method('acceptanceOf')->willReturn(
			['acceptedBy' => 'sem', 'acceptedVersion' => '2', 'acceptedAt' => '2026-09-18T10:00:00+00:00']
		);

		$decision = $this->gate->check(document: 'doc-1', person: 'sem');

		$this->assertTrue($decision['mayDownload']);
		$this->assertSame('2', $decision['acceptedVersion']);

	}//end testAReaderWhoAcceptedTheCurrentVersionMayDownload()

	/**
	 * A new version asks again.
	 *
	 * 🔴 ACCEPTANCE OF AN OLDER TEXT IS ACCEPTANCE OF A DIFFERENT TEXT. Reading
	 * the acceptance as "this person has agreed" rather than "this person has
	 * agreed to version 1" lets the terms be rewritten under everybody who
	 * already agreed, silently and retroactively.
	 *
	 * @return void
	 */
	public function testANewVersionAsksAgain(): void {
		$this->store->method('forDocument')->willReturn(self::TERMS);
		$this->store->method('acceptanceOf')->willReturn(
			['acceptedBy' => 'sem', 'acceptedVersion' => '1', 'acceptedAt' => '2026-08-01T10:00:00+00:00']
		);

		$decision = $this->gate->check(document: 'doc-1', person: 'sem');

		$this->assertFalse($decision['mayDownload']);
		$this->assertSame(DownloadAgreementGate::VERSION_MOVED_ON, $decision['reason']);
		$this->assertStringContainsString('version 1', $decision['message']);

	}//end testANewVersionAsksAgain()

	/**
	 * Accepting records who, when and which version, before anything is served.
	 *
	 * @return void
	 */
	public function testAcceptingRecordsWhoWhenAndWhichVersion(): void {
		$this->store->method('forDocument')->willReturn(self::TERMS);

		$recorded = [];
		$this->store->expects($this->once())
			->method('recordAcceptance')
			->willReturnCallback(
				function (string $document, string $person, string $version, string $text) use (&$recorded): array {
					$recorded = [
						'document' => $document,
						'person' => $person,
						'version' => $version,
						'text' => $text,
					];

					return $recorded;
				}
			);

		$decision = $this->gate->accept(document: 'doc-1', person: 'sem', version: '2');

		$this->assertTrue($decision['mayDownload']);
		$this->assertSame('sem', $recorded['person']);
		$this->assertSame('2', $recorded['version']);
		$this->assertSame('doc-1', $recorded['document']);

	}//end testAcceptingRecordsWhoWhenAndWhichVersion()

	/**
	 * An acceptance that cannot be written does not serve the file.
	 *
	 * @return void
	 */
	public function testAnUnrecordableAcceptanceStopsTheDownload(): void {
		$this->store->method('forDocument')->willReturn(self::TERMS);
		$this->store->method('recordAcceptance')->willThrowException(
			new RuntimeException(message: 'the object store refused the write')
		);

		$decision = $this->gate->accept(document: 'doc-1', person: 'sem', version: '2');

		$this->assertFalse(
			$decision['mayDownload'],
			'an unrecorded acceptance is the same as none, so the file stays inside'
		);
		$this->assertSame(DownloadAgreementGate::NOT_RECORDED, $decision['reason']);
		$this->assertStringContainsString('could not be recorded', $decision['message']);

	}//end testAnUnrecordableAcceptanceStopsTheDownload()

	/**
	 * Accepting a text that changed while it was on screen asks again.
	 *
	 * @return void
	 */
	public function testAcceptingATextThatChangedWhileReadingAsksAgain(): void {
		$this->store->method('forDocument')->willReturn(self::TERMS);
		$this->store->expects($this->never())->method('recordAcceptance');

		$decision = $this->gate->accept(document: 'doc-1', person: 'sem', version: '1');

		$this->assertFalse($decision['mayDownload']);
		$this->assertSame(DownloadAgreementGate::VERSION_MOVED_ON, $decision['reason']);

	}//end testAcceptingATextThatChangedWhileReadingAsksAgain()
}//end class
