<?php

/**
 * Who took a document, and who gets told.
 *
 * 🔴 A PUBLIC LINK NAMES THE LINK, NOT A PERSON. Whoever opened it is
 * unauthenticated, so any name is a guess, and a guessed name in an audit record
 * is worse than none: "J. de Vries downloaded this" is read as fact by whoever
 * reads it next. The link is what was actually used, and it is what can be
 * revoked.
 *
 * 🔑 THE COLLAPSE LIVES IN FILINQ BECAUSE THE PLATFORM DIALECT CANNOT DO IT,
 * checked rather than assumed: `x-openregister-notifications` supports
 * `trigger.dedupeFields`, and it is honoured ONLY by `ScheduledNotificationJob`
 * and `TaskScheduledNotificationJob` — both scheduled. There is no collapsing
 * for an event-driven notification and no time-window concept in the dialect at
 * all, so declaring a window there would be a key nobody reads.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use DateTimeImmutable;
use OCA\Filinq\Service\DocumentDownloadRecorder;
use PHPUnit\Framework\TestCase;

/**
 * `DocumentDownloadRecorder`.
 *
 * @covers \OCA\Filinq\Service\DocumentDownloadRecorder
 */
class DocumentDownloadRecorderTest extends TestCase {

	/**
	 * The recorder.
	 *
	 * @var DocumentDownloadRecorder
	 */
	private DocumentDownloadRecorder $recorder;

	/**
	 * Build it.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->recorder = new DocumentDownloadRecorder();
	}//end setUp()

	/**
	 * One download, with a named caller.
	 *
	 * @param string|null $userId The caller.
	 * @param string|null $linkId The link.
	 * @param string      $at     The moment.
	 *
	 * @return array<string, mixed> The record.
	 */
	private function download(?string $userId, ?string $linkId = null, string $at = '2026-09-18T10:00:00+00:00'): array {
		return $this->recorder->record(
			fileId: 7,
			version: '1695024000',
			route: 'version#download',
			userId: $userId,
			linkId: $linkId,
			moment: new DateTimeImmutable($at)
		);
	}//end download()

	/**
	 * The record carries file, version, moment, route and identity.
	 *
	 * @return void
	 */
	public function testTheRecordCarriesWhatTheRequirementNames(): void {
		$record = $this->download('a.jansen');

		$this->assertSame(7, $record['fileId']);
		$this->assertSame('1695024000', $record['version']);
		$this->assertSame('version#download', $record['route']);
		$this->assertSame('a.jansen', $record['identity']);
		$this->assertStringContainsString('2026-09-18', $record['downloadedAt']);
	}//end testTheRecordCarriesWhatTheRequirementNames()

	/**
	 * 🔴 A PUBLIC LINK NAMES THE LINK, NOT A PERSON.
	 *
	 * @return void
	 */
	public function testAPublicLinkNamesTheLink(): void {
		$this->assertSame('link:abc123', $this->download(null, 'abc123')['identity']);
	}//end testAPublicLinkNamesTheLink()

	/**
	 * With neither a caller nor a link, nobody is named.
	 *
	 * The alternative is inventing one, and a guessed name in an audit record is
	 * read as fact.
	 *
	 * @return void
	 */
	public function testWithNoCallerAndNoLinkNobodyIsNamed(): void {
		$this->assertSame(
			DocumentDownloadRecorder::ANONYMOUS,
			$this->download(null, null)['identity']
		);
	}//end testWithNoCallerAndNoLinkNobodyIsNamed()

	/**
	 * The first download of a file always notifies.
	 *
	 * @return void
	 */
	public function testTheFirstDownloadNotifies(): void {
		$this->assertTrue($this->recorder->shouldNotify($this->download('a.jansen'), null));
	}//end testTheFirstDownloadNotifies()

	/**
	 * A repeat inside the window collapses.
	 *
	 * Somebody opening a document three times while reading it is one event to
	 * anybody being told, and three notifications is how a useful signal becomes
	 * noise that gets muted.
	 *
	 * @return void
	 */
	public function testARepeatInsideTheWindowCollapses(): void {
		$first = $this->download('a.jansen', null, '2026-09-18T10:00:00+00:00');
		$again = $this->download('a.jansen', null, '2026-09-18T10:05:00+00:00');

		$this->assertFalse($this->recorder->shouldNotify($again, $first));
	}//end testARepeatInsideTheWindowCollapses()

	/**
	 * A download after the window notifies again.
	 *
	 * The control: without it, a recorder that collapsed everything would pass
	 * the test above while silencing the feature.
	 *
	 * @return void
	 */
	public function testADownloadAfterTheWindowNotifiesAgain(): void {
		$first = $this->download('a.jansen', null, '2026-09-18T10:00:00+00:00');
		$later = $this->download('a.jansen', null, '2026-09-18T10:30:00+00:00');

		$this->assertTrue($this->recorder->shouldNotify($later, $first));
	}//end testADownloadAfterTheWindowNotifiesAgain()

	/**
	 * 🔑 A SECOND PERSON IS A SECOND FACT, EVEN INSIDE THE WINDOW.
	 *
	 * Collapsing per file alone would hide the second person entirely, which on
	 * a confidential document is the one you most want to know about.
	 *
	 * @return void
	 */
	public function testADifferentPersonInsideTheWindowStillNotifies(): void {
		$first = $this->download('a.jansen', null, '2026-09-18T10:00:00+00:00');
		$other = $this->download('b.devries', null, '2026-09-18T10:05:00+00:00');

		$this->assertTrue($this->recorder->shouldNotify($other, $first));
	}//end testADifferentPersonInsideTheWindowStillNotifies()

	/**
	 * 🔴 AN UNREADABLE TIMESTAMP NOTIFIES.
	 *
	 * The alternative is silently swallowing a download because a date could not
	 * be parsed, and a missing notification about a document leaving is the
	 * failure this requirement exists to prevent. Noise is the cheaper error.
	 *
	 * @return void
	 */
	public function testAnUnreadableTimestampNotifiesRatherThanSwallowing(): void {
		$record = $this->download('a.jansen');

		$this->assertTrue(
			$this->recorder->shouldNotify($record, ['fileId' => 7, 'identity' => 'a.jansen', 'downloadedAt' => 'niet een datum'])
		);
	}//end testAnUnreadableTimestampNotifiesRatherThanSwallowing()
}//end class
