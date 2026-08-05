<?php

/**
 * GrondslagenSummaryByTypeTest
 *
 * This file is part of the DocuDesk app for Nextcloud.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category  Test
 * @package   OCA\DocuDesk
 * @author    Conduction <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://github.com/ConductionNL/docudesk
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\GrondslagenSummaryService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Covers the by-type summarisation of the grondslagen report.
 *
 * The report listed one row per placeholder, which on a real document runs to
 * dozens of near-identical rows. It now summarises to "N entities of type X,
 * removed M times, on grondslagen [a, b, c]".
 *
 * The grouping key is type AND the distinct SET of grondslagen, not type alone.
 * A grondslagen report exists to justify each removal, so collapsing a type
 * whose entities carry DIFFERENT bases into one row — leaving the reader unable
 * to tell which basis covered which removals — would defeat its purpose.
 *
 * @category Test
 * @package  OCA\DocuDesk
 * @author   Conduction <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://github.com/ConductionNL/docudesk
 */
class GrondslagenSummaryByTypeTest extends TestCase
{


    /**
     * Invoke the private summariser without booting the service's dependencies.
     *
     * @param array<int, array<string, mixed>> $entities Per-entity rows.
     *
     * @return array<int, array<string, mixed>>
     */
    private function summarise(array $entities): array
    {
        $reflection = new \ReflectionClass(GrondslagenSummaryService::class);
        $service    = $reflection->newInstanceWithoutConstructor();

        // `$l10n` is nullable but TYPED, so bypassing the constructor leaves it
        // uninitialised and reading it throws. Set it to null explicitly, which
        // makes localizeEntityType() return the raw type — the grouping under
        // test is independent of the label, and asserting on raw types keeps
        // these cases readable.
        $l10n = new \ReflectionProperty(GrondslagenSummaryService::class, 'l10n');
        $l10n->setAccessible(true);
        $l10n->setValue($service, null);

        $method = new ReflectionMethod(GrondslagenSummaryService::class, 'summariseEntitiesByType');
        $method->setAccessible(true);

        return (array) $method->invoke($service, $entities);
    }//end summarise()


    /**
     * Build a per-entity row.
     *
     * @param string             $type   Raw entity type.
     * @param int                $count  Occurrences.
     * @param array<int, string> $bases  Grondslag labels.
     * @param array<int, string> $files  Files the entity appeared in.
     *
     * @return array<string, mixed>
     */
    private function row(string $type, int $count, array $bases, array $files=[]): array
    {
        return [
            'entityType' => $type,
            'count'      => $count,
            'baseLabels' => $bases,
            'files'      => $files,
        ];
    }//end row()


    /**
     * Entities of one type sharing one basis collapse to a single row, with both
     * counts preserved.
     *
     * @return void
     */
    public function testEntitiesSharingABasisCollapseToOneRow(): void
    {
        $rows = $this->summarise(
            [
                $this->row('PERSON', 3, ['wettelijke plicht']),
                $this->row('PERSON', 4, ['wettelijke plicht']),
                $this->row('PERSON', 2, ['wettelijke plicht']),
            ]
        );

        $this->assertCount(1, $rows);
        $this->assertSame(3, $rows[0]['entityCount'], 'three distinct entities');
        $this->assertSame(9, $rows[0]['occurrenceCount'], 'nine replacements in total');
        $this->assertSame('wettelijke plicht', $rows[0]['basesText']);
    }//end testEntitiesSharingABasisCollapseToOneRow()


    /**
     * Entities of one type with DIFFERENT bases split, so each count stays tied
     * to the basis that justified it.
     *
     * @return void
     */
    public function testSameTypeWithDifferentBasesSplits(): void
    {
        $rows = $this->summarise(
            [
                $this->row('PERSON', 3, ['wettelijke plicht']),
                $this->row('PERSON', 4, ['gerechtvaardigd belang']),
                $this->row('PERSON', 2, ['wettelijke plicht']),
            ]
        );

        $this->assertCount(2, $rows, 'the type must split by basis set');

        $byBasis = [];
        foreach ($rows as $row) {
            $byBasis[$row['basesText']] = $row;
        }

        $this->assertSame(2, $byBasis['wettelijke plicht']['entityCount']);
        $this->assertSame(5, $byBasis['wettelijke plicht']['occurrenceCount']);
        $this->assertSame(1, $byBasis['gerechtvaardigd belang']['entityCount']);
        $this->assertSame(4, $byBasis['gerechtvaardigd belang']['occurrenceCount']);
    }//end testSameTypeWithDifferentBasesSplits()


    /**
     * The same basis set in a different ORDER is one group, not two.
     *
     * @return void
     */
    public function testBasisSetOrderDoesNotSplitAGroup(): void
    {
        $rows = $this->summarise(
            [
                $this->row('PERSON', 1, ['wettelijke plicht', 'gerechtvaardigd belang']),
                $this->row('PERSON', 1, ['gerechtvaardigd belang', 'wettelijke plicht']),
            ]
        );

        $this->assertCount(1, $rows);
        $this->assertSame(2, $rows[0]['entityCount']);
        $this->assertSame('gerechtvaardigd belang, wettelijke plicht', $rows[0]['basesText']);
    }//end testBasisSetOrderDoesNotSplitAGroup()


    /**
     * Duplicate labels on one entity do not inflate the basis set.
     *
     * @return void
     */
    public function testDuplicateLabelsAreDeduplicated(): void
    {
        $rows = $this->summarise([$this->row('PERSON', 1, ['wettelijke plicht', 'wettelijke plicht'])]);

        $this->assertSame(['wettelijke plicht'], $rows[0]['baseLabels']);
    }//end testDuplicateLabelsAreDeduplicated()


    /**
     * Distinct types stay separate.
     *
     * @return void
     */
    public function testDistinctTypesStaySeparate(): void
    {
        $rows = $this->summarise(
            [
                $this->row('PERSON', 2, ['wettelijke plicht']),
                $this->row('EMAIL', 5, ['wettelijke plicht']),
            ]
        );

        $this->assertCount(2, $rows);
    }//end testDistinctTypesStaySeparate()


    /**
     * Files are unioned across a group, so the dossier report can report how
     * many files a type spanned without double-counting.
     *
     * @return void
     */
    public function testFilesAreUnionedAcrossTheGroup(): void
    {
        $rows = $this->summarise(
            [
                $this->row('PERSON', 1, ['wettelijke plicht'], ['a.pdf', 'b.pdf']),
                $this->row('PERSON', 1, ['wettelijke plicht'], ['b.pdf', 'c.pdf']),
            ]
        );

        $this->assertCount(1, $rows);
        $this->assertSame(3, $rows[0]['fileCount'], 'a.pdf, b.pdf, c.pdf — b counted once');
        $this->assertSame(['a.pdf', 'b.pdf', 'c.pdf'], $rows[0]['files']);
    }//end testFilesAreUnionedAcrossTheGroup()


    /**
     * Per-document rows carry no files, which must not error and must report a
     * zero file count.
     *
     * @return void
     */
    public function testRowsWithoutFilesAreSafe(): void
    {
        $rows = $this->summarise([['entityType' => 'PERSON', 'count' => 2, 'baseLabels' => ['x']]]);

        $this->assertSame(0, $rows[0]['fileCount']);
        $this->assertSame([], $rows[0]['files']);
    }//end testRowsWithoutFilesAreSafe()


    /**
     * An entity with no grondslag is grouped separately and rendered as empty,
     * so "no basis recorded" never looks like a basis.
     *
     * @return void
     */
    public function testEntitiesWithoutABasisGroupSeparately(): void
    {
        $rows = $this->summarise(
            [
                $this->row('PERSON', 1, ['wettelijke plicht']),
                $this->row('PERSON', 3, []),
            ]
        );

        $this->assertCount(2, $rows);

        $empty = null;
        foreach ($rows as $row) {
            if ($row['basesText'] === '') {
                $empty = $row;
            }
        }

        $this->assertNotNull($empty, 'the no-basis group must exist');
        $this->assertSame(1, $empty['entityCount']);
        $this->assertSame(3, $empty['occurrenceCount']);
    }//end testEntitiesWithoutABasisGroupSeparately()


    /**
     * Output order is deterministic, so re-running on unchanged input produces
     * an identical document.
     *
     * @return void
     */
    public function testOrderIsDeterministic(): void
    {
        $entities = [
            $this->row('PERSON', 1, ['b']),
            $this->row('EMAIL', 1, ['a']),
            $this->row('PERSON', 1, ['a']),
        ];

        $first  = array_column($this->summarise($entities), 'basesText');
        $second = array_column($this->summarise(array_reverse($entities)), 'basesText');

        $this->assertSame($first, $second, 'input order must not affect output order');
    }//end testOrderIsDeterministic()


    /**
     * ANY entity type is localised, not an enumerated subset.
     *
     * Regression: the summary carried its own LOCALIZABLE_ENTITY_TYPES whitelist
     * that openregister's equivalent had outgrown. openregister localises five
     * GLiNER / OpenAnonymiser tags as literals — STREET_ADDRESS, BSN, KENTEKEN,
     * INCOME, EDUCATION_LEVEL — none of which the whitelist gained, so the
     * appended summary rendered them raw even though this app's own l10n carries
     * the Dutch for all five. The frontend's `entityTypeLabel()` never had a
     * whitelist, which is why the UI translated them and the summary did not.
     *
     * @return void
     */
    public function testAnyEntityTypeIsLocalisedNotAWhitelistedSubset(): void
    {
        $reflection = new \ReflectionClass(GrondslagenSummaryService::class);
        $service    = $reflection->newInstanceWithoutConstructor();

        // An IL10N that upper-cases and prefixes, so a translated call is
        // distinguishable from the raw-passthrough fallback.
        $l10n = $this->createMock(\OCP\IL10N::class);
        $l10n->method('t')->willReturnCallback(
            static function (string $text): string {
                return 'X-'.$text;
            }
        );

        $property = new \ReflectionProperty(GrondslagenSummaryService::class, 'l10n');
        $property->setAccessible(true);
        $property->setValue($service, $l10n);

        $method = new ReflectionMethod(GrondslagenSummaryService::class, 'localizeEntityType');
        $method->setAccessible(true);

        foreach (['PERSON', 'STREET_ADDRESS', 'BSN', 'KENTEKEN', 'INCOME', 'EDUCATION_LEVEL'] as $type) {
            $this->assertSame(
                'X-'.$type,
                $method->invoke($service, $type),
                $type.' must be passed to the translator, not returned raw'
            );
        }

        // A type nobody enumerated anywhere still reaches the translator; IL10N
        // returning the msgid unchanged is what provides the fallback.
        $this->assertSame('X-SOMETHING_NEW', $method->invoke($service, 'SOMETHING_NEW'));
    }//end testAnyEntityTypeIsLocalisedNotAWhitelistedSubset()


    /**
     * An empty entity list yields no rows rather than erroring.
     *
     * @return void
     */
    public function testEmptyInputYieldsNoRows(): void
    {
        $this->assertSame([], $this->summarise([]));
    }//end testEmptyInputYieldsNoRows()
}//end class
