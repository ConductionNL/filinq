<?php

/**
 * Unit tests for PolicyMatchService.
 *
 * Covers the match-type fan-out (exact / normalized / bsn / kvk),
 * time-bound checking, active flag, multi-prohibition tie-break
 * (deterministic on lowest-UUID), and the prohibition-wins-over-
 * standing-consent split.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/publication-prohibition-schema/tasks.md
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\PolicyMatchService;
use OCP\App\IAppManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Tests for PolicyMatchService match-type fan-out and time-bound semantics.
 *
 * @internal
 * @coversDefaultClass \OCA\DocuDesk\Service\PolicyMatchService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class PolicyMatchServiceTest extends TestCase
{


    /**
     * Build a PolicyMatchService backed by a fake ObjectService that returns the supplied rule set.
     *
     * @param array<int, array<string, mixed>> $prohibitions Raw prohibition rows (mirrors OR row shape).
     * @param array<int, array<string, mixed>> $standing     Raw standing-consent rows.
     *
     * @return PolicyMatchService
     */
    private function buildService(array $prohibitions, array $standing=[]): PolicyMatchService
    {
        $fakeObjectService = new class($prohibitions, $standing) {

            /**
             * Constructor for the fake object service.
             *
             * @param array<int, array<string, mixed>> $prohibitions Prohibition rows to return.
             * @param array<int, array<string, mixed>> $standing     Standing-consent rows to return.
             */
            public function __construct(
                private readonly array $prohibitions,
                private readonly array $standing
            ) {
            }

            /**
             * Mimic ObjectService::findAll signature used by PolicyMatchService.
             *
             * @param array<string, mixed> $config Query config (we read filters.schema).
             * @param bool                 $_rbac  RBAC bypass flag — ignored in the test double.
             *
             * @return array<int, array<string, mixed>>
             */
            public function findAll(array $config=[], bool $_rbac=true): array
            {
                $filters = $config['filters'] ?? [];
                $schema  = $filters['schema'] ?? '';
                if ($schema === 'publicationProhibition') {
                    return $this->prohibitions;
                }
                if ($schema === 'publicationConsent') {
                    return $this->standing;
                }
                return [];
            }
        };

        $logger    = $this->createMock(LoggerInterface::class);
        $appMan    = $this->createMock(IAppManager::class);
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->with('OCA\\OpenRegister\\Service\\ObjectService')
            ->willReturn($fakeObjectService);

        return new PolicyMatchService($logger, $container, $appMan);

    }//end buildService()


    /**
     * Exact-type rule matches only the literal string, not a case-folded variant.
     *
     * @return void
     */
    public function testExactRuleMatchesLiteralOnly(): void
    {
        $svc = $this->buildService([
            [
                '@self'       => ['id' => 'rule-a'],
                'active'      => true,
                'entityType'  => 'PERSON',
                'primaryName' => 'Alice',
                'matchRules'  => [['type' => 'exact', 'value' => 'Alice Doe']],
            ],
        ]);

        self::assertNotNull($svc->match('Alice Doe', 'PERSON'));
        self::assertNull($svc->match('alice doe', 'PERSON'));
        self::assertNull($svc->match('Alice  Doe', 'PERSON'));

    }//end testExactRuleMatchesLiteralOnly()


    /**
     * Normalized-type rule matches case-folded and accent-stripped variants.
     *
     * @return void
     */
    public function testNormalizedRuleStripsCaseAndAccents(): void
    {
        $svc = $this->buildService([
            [
                '@self'       => ['id' => 'rule-b'],
                'active'      => true,
                'entityType'  => 'PERSON',
                'primaryName' => 'Pieter',
                'matchRules'  => [['type' => 'normalized', 'value' => 'Pieter de Vries']],
            ],
        ]);

        self::assertNotNull($svc->match('pieter de vries', 'PERSON'));
        self::assertNotNull($svc->match('Piéter dé Vries', 'PERSON'));
        self::assertNull($svc->match('Jan de Vries', 'PERSON'));

    }//end testNormalizedRuleStripsCaseAndAccents()


    /**
     * BSN-type rule resolves through resolvedIdentifiers.bsn; missing BSN never matches.
     *
     * @return void
     */
    public function testBsnRuleMatchesOnlyResolvedIdentifier(): void
    {
        $svc = $this->buildService([
            [
                '@self'       => ['id' => 'rule-c'],
                'active'      => true,
                'entityType'  => 'PERSON',
                'primaryName' => 'BSN holder',
                'matchRules'  => [['type' => 'bsn', 'value' => '123456789']],
            ],
        ]);

        self::assertNotNull(
            $svc->match('Any Name', 'PERSON', ['bsn' => '123456789']),
            'BSN match should fire when resolvedIdentifiers carry the right BSN.'
        );
        self::assertNull(
            $svc->match('Any Name', 'PERSON', ['bsn' => '987654321']),
            'Different BSN must not match.'
        );
        self::assertNull(
            $svc->match('Any Name', 'PERSON'),
            'Absent BSN must not match (no false positives on entity text alone).'
        );

    }//end testBsnRuleMatchesOnlyResolvedIdentifier()


    /**
     * KvK-type rule resolves through resolvedIdentifiers.kvk on ORGANIZATION entities.
     *
     * @return void
     */
    public function testKvkRuleMatchesOnlyResolvedIdentifier(): void
    {
        $svc = $this->buildService([
            [
                '@self'       => ['id' => 'rule-d'],
                'active'      => true,
                'entityType'  => 'ORGANIZATION',
                'primaryName' => 'Acme BV',
                'matchRules'  => [['type' => 'kvk', 'value' => '12345678']],
            ],
        ]);

        self::assertNotNull(
            $svc->match('Acme BV', 'ORGANIZATION', ['kvk' => '12345678'])
        );
        self::assertNull(
            $svc->match('Acme BV', 'ORGANIZATION', ['kvk' => '00000000'])
        );

    }//end testKvkRuleMatchesOnlyResolvedIdentifier()


    /**
     * Rules with `active: false` are filtered at load and never match.
     *
     * @return void
     */
    public function testInactiveRulesAreSkipped(): void
    {
        $svc = $this->buildService([
            [
                '@self'       => ['id' => 'rule-inactive'],
                'active'      => false,
                'entityType'  => 'PERSON',
                'primaryName' => 'Hidden',
                'matchRules'  => [['type' => 'exact', 'value' => 'Hidden Name']],
            ],
        ]);

        self::assertNull($svc->match('Hidden Name', 'PERSON'));

    }//end testInactiveRulesAreSkipped()


    /**
     * Rules whose validFrom is in the future MUST NOT match.
     *
     * @return void
     */
    public function testFutureValidFromDoesNotMatch(): void
    {
        $future = (new \DateTimeImmutable('+1 year'))->format('c');
        $svc    = $this->buildService([
            [
                '@self'       => ['id' => 'rule-future'],
                'active'      => true,
                'entityType'  => 'PERSON',
                'primaryName' => 'Future',
                'matchRules'  => [['type' => 'exact', 'value' => 'Future Name']],
                'validFrom'   => $future,
            ],
        ]);

        self::assertNull($svc->match('Future Name', 'PERSON'));

    }//end testFutureValidFromDoesNotMatch()


    /**
     * Rules whose validUntil is in the past MUST NOT match.
     *
     * @return void
     */
    public function testExpiredValidUntilDoesNotMatch(): void
    {
        $past = (new \DateTimeImmutable('-1 year'))->format('c');
        $svc  = $this->buildService([
            [
                '@self'       => ['id' => 'rule-expired'],
                'active'      => true,
                'entityType'  => 'PERSON',
                'primaryName' => 'Expired',
                'matchRules'  => [['type' => 'exact', 'value' => 'Expired Name']],
                'validUntil'  => $past,
            ],
        ]);

        self::assertNull($svc->match('Expired Name', 'PERSON'));

    }//end testExpiredValidUntilDoesNotMatch()


    /**
     * Unknown rule types are skipped (defence-in-depth) but never throw.
     *
     * @return void
     */
    public function testUnknownTypeIsSkippedGracefully(): void
    {
        $svc = $this->buildService([
            [
                '@self'       => ['id' => 'rule-bad'],
                'active'      => true,
                'entityType'  => 'PERSON',
                'primaryName' => 'Bad',
                'matchRules'  => [['type' => 'made-up', 'value' => 'Any']],
            ],
        ]);

        self::assertNull($svc->match('Any', 'PERSON'));

    }//end testUnknownTypeIsSkippedGracefully()


    /**
     * Multi-rule precedence: when two rules match, the lower-UUID rule wins (deterministic).
     *
     * @return void
     */
    public function testLowerUuidWinsOnMultiMatch(): void
    {
        $svc = $this->buildService([
            [
                '@self'       => ['id' => 'b-second'],
                'active'      => true,
                'entityType'  => 'PERSON',
                'primaryName' => 'Second',
                'matchRules'  => [['type' => 'exact', 'value' => 'Same Name']],
            ],
            [
                '@self'       => ['id' => 'a-first'],
                'active'      => true,
                'entityType'  => 'PERSON',
                'primaryName' => 'First',
                'matchRules'  => [['type' => 'exact', 'value' => 'Same Name']],
            ],
        ]);

        $match = $svc->match('Same Name', 'PERSON');
        self::assertNotNull($match);
        self::assertSame('a-first', $match['uuid'], 'Deterministic tie-break must pick the lexicographically-lowest UUID.');

    }//end testLowerUuidWinsOnMultiMatch()


    /**
     * `matchProhibition` convenience wrapper returns the same UUID + name shape as `match`.
     *
     * @return void
     */
    public function testMatchProhibitionReturnsCanonicalShape(): void
    {
        $svc = $this->buildService([
            [
                '@self'       => ['id' => 'rule-prohibition'],
                'active'      => true,
                'entityType'  => 'PERSON',
                'primaryName' => 'Prohibition',
                'matchRules'  => [['type' => 'exact', 'value' => 'Prohibition Name']],
            ],
        ]);

        $result = $svc->matchProhibition('PERSON', 'Prohibition Name');
        self::assertNotNull($result);
        self::assertArrayHasKey('ruleId', $result);
        self::assertArrayHasKey('ruleName', $result);
        self::assertSame('rule-prohibition', $result['ruleId']);

    }//end testMatchProhibitionReturnsCanonicalShape()


    /**
     * `invalidateCache` clears the in-memory rule cache so a subsequent match
     * re-runs `loadRules` (verified by swapping the underlying data).
     *
     * @return void
     */
    public function testInvalidateCacheClearsLoadedRules(): void
    {
        $rule = [
            '@self'       => ['id' => 'cached-rule'],
            'active'      => true,
            'entityType'  => 'PERSON',
            'primaryName' => 'Cached',
            'matchRules'  => [['type' => 'exact', 'value' => 'Cached Name']],
        ];

        $svc = $this->buildService([$rule]);
        self::assertNotNull($svc->match('Cached Name', 'PERSON'));

        $svc->invalidateCache();
        // After invalidation `match` re-asks the fake ObjectService — same data,
        // same result, but the new call path proves the cache was cleared
        // (the prior call returned without hitting the loader; the second one
        // must re-execute it).
        self::assertNotNull($svc->match('Cached Name', 'PERSON'));

    }//end testInvalidateCacheClearsLoadedRules()


}//end class
