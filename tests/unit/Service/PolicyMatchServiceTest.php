<?php
/**
 * Unit tests for PolicyMatchService — prohibition-only matching + threshold.
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
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\PolicyMatchService;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionProperty;

/**
 * Tests for the prohibition matcher and the high-confidence threshold reader.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @phpstan-extends TestCase
 */
class PolicyMatchServiceTest extends TestCase
{

    /**
     * Build a service with the given rule cache seeded and a threshold config.
     *
     * @param array<int, array<string, mixed>> $rules     Rule-cache contents.
     * @param string                           $threshold Raw config value for the threshold key.
     *
     * @return PolicyMatchService The service under test.
     */
    private function makeService(array $rules, string $threshold='0.85'): PolicyMatchService
    {
        $config = $this->createMock(IAppConfig::class);
        $config->method('getValueString')->willReturnCallback(
            static function (string $app, string $key, string $default='') use ($threshold): string {
                if ($key === 'docudesk.prohibition.high_confidence_threshold') {
                    return $threshold;
                }

                return $default;
            }
        );

        $service = new PolicyMatchService(
            $this->createMock(LoggerInterface::class),
            $this->createMock(ContainerInterface::class),
            $this->createMock(IAppManager::class),
            $config
        );

        // Seed the private rule cache so matching runs without OpenRegister.
        $cache = new ReflectionProperty(PolicyMatchService::class, 'rulesCache');
        $cache->setAccessible(true);
        $cache->setValue($service, $rules);

        return $service;

    }//end makeService()


    /**
     * A prohibition rule matching the entity is returned.
     *
     * @return void
     */
    public function testMatchProhibitionReturnsProhibitionRule(): void
    {
        $service = $this->makeService(
            [
                [
                    'uuid'        => 'R-PROHIBIT-1',
                    'kind'        => PolicyMatchService::KIND_PROHIBITION,
                    'entityType'  => 'PERSON',
                    'primaryName' => 'Politiemedewerker undercover',
                    'matchRules'  => [['type' => 'exact', 'value' => 'Jansen']],
                ],
            ]
        );

        $match = $service->matchProhibition('Jansen', 'PERSON');

        $this->assertIsArray($match);
        $this->assertSame('R-PROHIBIT-1', $match['uuid']);
        $this->assertSame(PolicyMatchService::KIND_PROHIBITION, $match['kind']);
        $this->assertSame('Politiemedewerker undercover', $match['primaryName']);

    }//end testMatchProhibitionReturnsProhibitionRule()


    /**
     * A standing-consent rule is never returned by matchProhibition, even
     * though the general match() would surface it.
     *
     * @return void
     */
    public function testMatchProhibitionIgnoresStandingConsent(): void
    {
        $rules   = [
            [
                'uuid'        => 'R-SC-1',
                'kind'        => PolicyMatchService::KIND_STANDING_CONSENT,
                'entityType'  => 'PERSON',
                'primaryName' => 'Woordvoerder',
                'matchRules'  => [['type' => 'exact', 'value' => 'Kuiper']],
            ],
        ];
        $service = $this->makeService($rules);

        $this->assertNull($service->matchProhibition('Kuiper', 'PERSON'));
        // Sanity: the general matcher DOES surface the standing consent.
        $this->assertSame('R-SC-1', $service->match('Kuiper', 'PERSON')['uuid']);

    }//end testMatchProhibitionIgnoresStandingConsent()


    /**
     * No rule matches — null.
     *
     * @return void
     */
    public function testMatchProhibitionReturnsNullWhenNoMatch(): void
    {
        $service = $this->makeService(
            [
                [
                    'uuid'        => 'R-PROHIBIT-1',
                    'kind'        => PolicyMatchService::KIND_PROHIBITION,
                    'entityType'  => 'PERSON',
                    'primaryName' => 'X',
                    'matchRules'  => [['type' => 'exact', 'value' => 'Jansen']],
                ],
            ]
        );

        $this->assertNull($service->matchProhibition('De Vries', 'PERSON'));

    }//end testMatchProhibitionReturnsNullWhenNoMatch()


    /**
     * The threshold defaults to 0.85 and honours a configured override.
     *
     * @return void
     */
    public function testHighConfidenceThresholdReadsConfig(): void
    {
        $this->assertSame(0.85, $this->makeService([])->highConfidenceThreshold());
        $this->assertSame(0.90, $this->makeService([], '0.90')->highConfidenceThreshold());

    }//end testHighConfidenceThresholdReadsConfig()


}//end class
