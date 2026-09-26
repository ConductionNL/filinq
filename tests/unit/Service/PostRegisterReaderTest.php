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
	 * The register slug the last read asked for.
	 *
	 * @var string
	 */
	private string $lastRegisterSlug = '';

	/**
	 * The schema slug the last read asked for.
	 *
	 * @var string
	 */
	private string $lastSchemaSlug = '';

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
		// 🔴 `searchObjectsBySlug`, not `searchObjects`. The reader passes the
		// slugs `filinq` and `documentRegistration`, and `searchObjects` has a
		// numeric-id contract: handed a slug it answers zero rows and no
		// error. Doubling `searchObjects` here is what let every assertion in
		// this file pass while production read nothing at all.
		$objectService->method('searchObjectsBySlug')->willReturnCallback(
			function (string $registerSlug, string $schemaSlug, array $filters) use ($rows): array {
				$this->lastRegisterSlug = $registerSlug;
				$this->lastSchemaSlug   = $schemaSlug;
				$this->lastQuery        = $filters;

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
		$this->assertArrayNotHasKey('@self', $this->lastQuery);
		$this->assertSame('filinq', $this->lastRegisterSlug);
		$this->assertSame('documentRegistration', $this->lastSchemaSlug);
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

	/**
	 * A reader whose register answers the inbound list and each discharge read.
	 *
	 * The two reads are told apart by the query, not by call order: a reader
	 * built on call order would keep passing after somebody reorders the loop,
	 * while answering the wrong question in production.
	 *
	 * @param array<int, array<string, mixed>>      $inbound  The unit's inbound entries.
	 * @param array<string, array<int, mixed>>      $answers  The answers per inbound uuid.
	 * @param string                                $throwsOn A uuid whose discharge read fails, or ''.
	 *
	 * @return PostRegisterReader The reader.
	 */
	private function readerWithPost(array $inbound, array $answers, string $throwsOn = ''): PostRegisterReader {
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('searchObjectsBySlug')->willReturnCallback(
			function (string $registerSlug, string $schemaSlug, array $filters) use ($inbound, $answers, $throwsOn): array {
				$this->lastRegisterSlug = $registerSlug;
				$this->lastSchemaSlug   = $schemaSlug;
				$this->lastQuery        = $filters;

				if (isset($filters['answers']) === true) {
					$uuid = (string)$filters['answers'];
					if ($uuid === $throwsOn) {
						throw new RuntimeException('register unreachable');
					}

					return ($answers[$uuid] ?? []);
				}

				return $inbound;
			}
		);

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);

		return new PostRegisterReader($resolver, new NullLogger());
	}//end readerWithPost()

	/**
	 * The open post list holds exactly the undischarged entries, oldest first.
	 *
	 * @return void
	 */
	public function testTheOpenPostListHoldsTheUndischargedEntriesOldestFirst(): void {
		$reader = $this->readerWithPost(
			[
				['id' => 'in-2', 'registeredAt' => '2026-03-01', 'unit' => 'burgerzaken'],
				['id' => 'in-1', 'registeredAt' => '2026-01-15', 'unit' => 'burgerzaken'],
				['id' => 'in-3', 'registeredAt' => '2026-05-20', 'unit' => 'burgerzaken'],
			],
			['in-3' => [['registrationNumber' => '2026-00099']]]
		);

		$open = $reader->openPostFor('burgerzaken');

		// in-3 is discharged and drops out; the rest come oldest first, which is
		// what makes this a work list rather than a dump.
		$this->assertSame(['in-1', 'in-2'], array_column($open, 'id'));
	}//end testTheOpenPostListHoldsTheUndischargedEntriesOldestFirst()

	/**
	 * The open post query uses the objects endpoint's bare-key spelling.
	 *
	 * A `filter[unit]` wrapper is read as the EMPTY SET by that endpoint, so
	 * this method would report a unit with no post at all, confidently and with
	 * nothing in the log.
	 *
	 * @return void
	 */
	public function testTheOpenPostQueryUsesTheObjectsEndpointsSpelling(): void {
		$reader = $this->readerWithPost([['id' => 'in-1', 'registeredAt' => '2026-01-15']], []);
		$reader->openPostFor('burgerzaken');

		// The LAST query is the discharge read; the inbound one is asserted by
		// the entry actually coming back above.
		$this->assertArrayNotHasKey('filter', $this->lastQuery);
		$this->assertArrayNotHasKey('@self', $this->lastQuery);
		$this->assertSame('filinq', $this->lastRegisterSlug);
	}//end testTheOpenPostQueryUsesTheObjectsEndpointsSpelling()

	/**
	 * The open post query filters on the direction the schema actually stores.
	 *
	 * 🔴 THE VOCABULARY IS THE SCHEMA'S. `documentRegistration` declares
	 * `enum: [inbound, outbound]` and OpenRegister refuses anything else on
	 * save, so filtering on `incoming` matched no row ever written and this
	 * list came back empty for every unit, with no error anywhere. The value
	 * was only in the proposal's prose, and nothing compared the two.
	 *
	 * @return void
	 */
	public function testTheOpenPostQueryFiltersOnTheSchemasDirectionValue(): void {
		$directions = [];
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('searchObjectsBySlug')->willReturnCallback(
			static function (string $registerSlug, string $schemaSlug, array $filters) use (&$directions): array {
				if (isset($filters['direction']) === true) {
					$directions[] = (string)$filters['direction'];
				}

				return [];
			}
		);

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);

		(new PostRegisterReader($resolver, new NullLogger()))->openPostFor('burgerzaken');

		$this->assertSame(
			['inbound'],
			$directions,
			'the open post list asks for the direction value the schema allows, not the one the prose uses'
		);
	}//end testTheOpenPostQueryFiltersOnTheSchemasDirectionValue()

	/**
	 * An entry whose discharge could not be read is raised, not listed as open.
	 *
	 * Listing it would put a letter somebody answered a month ago at the top of
	 * the work list, and the handler would answer it again.
	 *
	 * @return void
	 */
	public function testAFailedDischargeReadIsRaisedRatherThanListedAsOpen(): void {
		$reader = $this->readerWithPost(
			[['id' => 'in-1', 'registeredAt' => '2026-01-15']],
			[],
			'in-1'
		);

		$this->expectException(RuntimeException::class);

		$reader->openPostFor('burgerzaken');
	}//end testAFailedDischargeReadIsRaisedRatherThanListedAsOpen()

	/**
	 * An entry with no registration date sorts last, never first.
	 *
	 * An unknown date is not evidence of age, and sorting it first would put it
	 * above letters that really have been waiting.
	 *
	 * @return void
	 */
	public function testAnUndatedEntrySortsLast(): void {
		$reader = $this->readerWithPost(
			[
				['id' => 'in-undated'],
				['id' => 'in-old', 'registeredAt' => '2026-01-15'],
			],
			[]
		);

		$this->assertSame(['in-old', 'in-undated'], array_column($reader->openPostFor('burgerzaken'), 'id'));
	}//end testAnUndatedEntrySortsLast()

	/**
	 * A unit nobody named reads as no post, without touching the register.
	 *
	 * @return void
	 */
	public function testAnEmptyUnitReadsAsNoPost(): void {
		$this->assertSame([], $this->readerWithPost([['id' => 'in-1']], [])->openPostFor('   '));
	}//end testAnEmptyUnitReadsAsNoPost()

	/**
	 * A unit's series is read from the register and ordered by number.
	 *
	 * The fetch lives beside `series()` rather than in the controller: a caller
	 * that built this query itself would be a second place the objects
	 * endpoint's bare-key spelling has to be right, and the wrong spelling
	 * answers the EMPTY SET rather than an error.
	 *
	 * @return void
	 */
	public function testTheUnitsSeriesIsReadAndOrdered(): void {
		$reader = $this->readerReturning(
			[
				['registrationNumber' => '2026-00042', 'direction' => 'outgoing'],
				['registrationNumber' => '2026-00041', 'direction' => 'incoming', 'withdrawnReason' => 'verkeerd geadresseerd', 'withdrawnAt' => '2026-02-01'],
			]
		);

		$series = $reader->seriesFor('burgerzaken');

		$this->assertSame(['2026-00041', '2026-00042'], array_column($series, 'registrationNumber'));
		$this->assertTrue($series[0]['withdrawal']['withdrawn']);
		$this->assertArrayNotHasKey('filter', $this->lastQuery);
	}//end testTheUnitsSeriesIsReadAndOrdered()

	/**
	 * A register that could not be read raises rather than reading as a unit
	 * that has registered nothing.
	 *
	 * @return void
	 */
	public function testAFailedSeriesReadIsRaised(): void {
		$this->expectException(RuntimeException::class);

		$this->readerReturning(null)->seriesFor('burgerzaken');
	}//end testAFailedSeriesReadIsRaised()
}//end class
