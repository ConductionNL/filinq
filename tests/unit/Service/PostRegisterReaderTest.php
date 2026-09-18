<?php

/**
 * What answered what, and where the gaps came from.
 *
 * 🔴 THE DISCHARGE IS READ, NEVER WRITTEN. An inbound entry carries no
 * `answered` flag and never will: a flag can be set by anybody, at any time,
 * without an answer existing, and then the register reports a letter as dealt
 * with because somebody ticked a box. Reading it from the outbound entry that
 * NAMES the inbound one means the register can only claim a discharge that has a
 * document behind it.
 *
 * 🔑 THE SEARCH CONTRACT IS ASSERTED, NOT ASSUMED. OpenRegister's objects search
 * takes BARE property keys beside a `@self` block, and the sibling aggregations
 * endpoint spells the same filter the opposite way. A `filter[...]` wrapper here
 * would be read as the empty set, and this reader would report every inbound
 * entry as undischarged, confidently and silently. So the query shape is pinned
 * by a test rather than trusted.
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

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\PostRegisterReader;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * `PostRegisterReader`.
 *
 * @covers \OCA\Filinq\Service\PostRegisterReader
 */
class PostRegisterReaderTest extends TestCase {

	/**
	 * The query the reader last built, for assertion.
	 *
	 * @var array<string, mixed>
	 */
	private array $lastQuery = [];

	/**
	 * A reader whose search returns the given rows.
	 *
	 * @param array<int, array<string, mixed>>|null $rows What the search returns, or null to throw.
	 *
	 * @return PostRegisterReader The reader.
	 */
	private function readerReturning(?array $rows): PostRegisterReader {
		// `ObjectService` is mocked rather than replaced by an anonymous class:
		// the resolver's return type is the concrete service, so a double of any
		// other type is refused at the boundary. That refusal is useful — it is
		// the same protection that stops a test passing against a shape
		// production never returns.
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('searchObjects')->willReturnCallback(
			function (array $query) use ($rows): array {
				$this->lastQuery = $query;

				if ($rows === null) {
					throw new RuntimeException('register unreachable');
				}

				return $rows;
			}
		);

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);

		return new PostRegisterReader($resolver, new NullLogger());
	}//end readerReturning()

	/**
	 * An outbound entry naming the inbound one is the discharge.
	 *
	 * @return void
	 */
	public function testTheDischargeIsTheOutboundEntryThatNamesIt(): void {
		$answers = $this->readerReturning([['registrationNumber' => '2026-00043', 'direction' => 'outbound']])
			->answersFor('inbound-1');

		$this->assertCount(1, $answers);
		$this->assertSame('2026-00043', $answers[0]['registrationNumber']);
	}//end testTheDischargeIsTheOutboundEntryThatNamesIt()

	/**
	 * 🔑 THE QUERY USES BARE PROPERTY KEYS BESIDE `@self`.
	 *
	 * A `filter[answers]` wrapper is how the sibling aggregations endpoint
	 * spells it, and the objects endpoint reads that as the empty set. The
	 * reader would then report every inbound entry as undischarged, with no
	 * error anywhere.
	 *
	 * @return void
	 */
	public function testTheQueryUsesTheObjectsEndpointsSpelling(): void {
		$this->readerReturning([])->answersFor('inbound-1');

		$this->assertSame('inbound-1', ($this->lastQuery['answers'] ?? null));
		$this->assertArrayNotHasKey('filter', $this->lastQuery);
		$this->assertSame('documentRegistration', ($this->lastQuery['@self']['schema'] ?? null));
	}//end testTheQueryUsesTheObjectsEndpointsSpelling()

	/**
	 * Nothing answering it is an empty list, which is a real answer.
	 *
	 * @return void
	 */
	public function testAnUnansweredEntryHasNoAnswers(): void {
		$this->assertSame([], $this->readerReturning([])->answersFor('inbound-1'));
	}//end testAnUnansweredEntryHasNoAnswers()

	/**
	 * 🔴 A FAILED READ IS NOT "NO ANSWERS".
	 *
	 * Reporting an empty list would tell the reader the letter is still open
	 * when it may have been answered a month ago, which is the reading that gets
	 * a second reply sent.
	 *
	 * @return void
	 */
	public function testAFailedReadIsRaisedRatherThanReportedAsUnanswered(): void {
		$this->expectException(RuntimeException::class);

		$this->readerReturning(null)->answersFor('inbound-1');
	}//end testAFailedReadIsRaisedRatherThanReportedAsUnanswered()

	/**
	 * A withdrawal needs both its reason and its moment.
	 *
	 * Half a record is reported as incomplete rather than quietly treated as
	 * either, because an auditor reading the series a year later needs to place
	 * the gap in time as well as explain it.
	 *
	 * @return void
	 */
	public function testAHalfRecordedWithdrawalIsReportedIncomplete(): void {
		$reader = $this->readerReturning([]);

		$this->assertFalse($reader->withdrawalOf(['withdrawnReason' => 'verkeerd geadresseerd'])['complete']);
		$this->assertFalse($reader->withdrawalOf(['withdrawnAt' => '2026-09-18T10:00:00+00:00'])['complete']);
		$this->assertTrue(
			$reader->withdrawalOf(
				['withdrawnReason' => 'verkeerd geadresseerd', 'withdrawnAt' => '2026-09-18T10:00:00+00:00']
			)['complete']
		);
	}//end testAHalfRecordedWithdrawalIsReportedIncomplete()

	/**
	 * An entry nobody withdrew is not withdrawn, and that is complete.
	 *
	 * The control: without it, a reader that called everything incomplete would
	 * pass the test above.
	 *
	 * @return void
	 */
	public function testAnOrdinaryEntryIsNotWithdrawn(): void {
		$state = $this->readerReturning([])->withdrawalOf(['registrationNumber' => '2026-00042']);

		$this->assertFalse($state['withdrawn']);
		$this->assertTrue($state['complete']);
	}//end testAnOrdinaryEntryIsNotWithdrawn()

	/**
	 * 🔴 THE SERIES READS END TO END, WITH EVERY GAP ACCOUNTED FOR.
	 *
	 * An unexplained hole is the failure the requirement exists to prevent: an
	 * auditor cannot tell a withdrawn allocation from a lost document, and
	 * neither can the register.
	 *
	 * @return void
	 */
	public function testTheSeriesIsOrderedAndCarriesEveryWithdrawal(): void {
		$series = $this->readerReturning([])->series(
			[
				['registrationNumber' => '2026-00044', 'direction' => 'outbound'],
				[
					'registrationNumber' => '2026-00043',
					'direction' => 'inbound',
					'withdrawnReason' => 'verkeerd geadresseerd',
					'withdrawnAt' => '2026-09-18T10:00:00+00:00',
				],
				['registrationNumber' => '2026-00042', 'direction' => 'inbound'],
			]
		);

		$this->assertSame(
			['2026-00042', '2026-00043', '2026-00044'],
			array_column($series, 'registrationNumber')
		);
		$this->assertTrue($series[1]['withdrawal']['withdrawn']);
		$this->assertSame('verkeerd geadresseerd', $series[1]['withdrawal']['reason']);
	}//end testTheSeriesIsOrderedAndCarriesEveryWithdrawal()

	/**
	 * An entry with no number is not in the series yet.
	 *
	 * Including it would put a row with no position among rows ordered by
	 * position.
	 *
	 * @return void
	 */
	public function testAnUnnumberedEntryIsNotInTheSeries(): void {
		$series = $this->readerReturning([])->series([['direction' => 'inbound'], ['registrationNumber' => '2026-00042']]);

		$this->assertCount(1, $series);
	}//end testAnUnnumberedEntryIsNotInTheSeries()
}//end class
