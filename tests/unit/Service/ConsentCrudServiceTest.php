<?php

/**
 * Unit tests for ConsentCrudService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\ConsentCrudService;
use OCA\DocuDesk\Service\ConsentService;
use OCA\DocuDesk\Service\SettingsService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for ConsentCrudService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ConsentCrudServiceTest extends TestCase
{

    /**
     * The system under test.
     *
     * @var ConsentCrudService
     */
    private ConsentCrudService $service;

    /**
     * Mocked settings service exposing IAppConfig values via getAllSettings().
     *
     * @var SettingsService|MockObject
     */
    private SettingsService|MockObject $mockSettingsService;

    /**
     * Mocked consent service that the CRUD service delegates to.
     *
     * @var ConsentService|MockObject
     */
    private ConsentService|MockObject $mockConsentService;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockSettingsService = $this->createMock(SettingsService::class);
        $this->mockConsentService  = $this->createMock(ConsentService::class);
        $mockLogger = $this->createMock(LoggerInterface::class);

        $this->service = new ConsentCrudService(
            $this->mockSettingsService,
            $this->mockConsentService,
            $mockLogger
        );

    }//end setUp()


    /**
     * Test getConsentConfig returns null when both IAppConfig keys are empty.
     *
     * After the `auto-configure-object-type-defaults` change, the auto-default
     * helper in `SettingsInitializer` is the only path that populates these
     * keys (admins can override but rarely need to). When the keys are cleared,
     * `getConsentConfig()` MUST still return `null` — this test documents that
     * the failure-mode contract is preserved (admin-settings spec REQ-SET-02).
     *
     * @return void
     */
    public function testGetConsentConfigReturnsNullWhenNotConfigured(): void
    {
        $this->mockSettingsService->method('getAllSettings')
            ->willReturn(
                    [
                        'configuration' => [
                            'publicationConsent_register' => '',
                            'publicationConsent_schema'   => '',
                        ],
                    ]
                    );

        $result = $this->service->getConsentConfig();
        $this->assertNull($result);

    }//end testGetConsentConfigReturnsNullWhenNotConfigured()


    /**
     * Test getConsentConfig returns config when configured
     *
     * @return void
     */
    public function testGetConsentConfigReturnsConfigWhenConfigured(): void
    {
        $this->mockSettingsService->method('getAllSettings')
            ->willReturn(
                    [
                        'configuration' => [
                            'publicationConsent_register' => 'reg-1',
                            'publicationConsent_schema'   => 'schema-1',
                        ],
                    ]
                    );

        $result = $this->service->getConsentConfig();
        $this->assertNotNull($result);
        $this->assertEquals('reg-1', $result['register']);
        $this->assertEquals('schema-1', $result['schema']);

    }//end testGetConsentConfigReturnsConfigWhenConfigured()


    /**
     * Test getConsentConfig returns auto-populated integer IDs after init.
     *
     * After `SettingsInitializer::applyObjectTypeConfigurationDefaults()` runs
     * on a fresh install, the IAppConfig keys hold integer IDs (cast to string)
     * resolved from `docudesk_register.json` via `RegisterMapper::find()` /
     * `SchemaMapper::find()`. This test confirms `getConsentConfig()` accepts
     * those integer-string values transparently — the contract does not depend
     * on whether the values are slugs or integer IDs (admin-settings REQ-SET-02
     * scenario "Defaults are populated automatically on fresh install").
     *
     * @return void
     */
    public function testGetConsentConfigReturnsAutoPopulatedValues(): void
    {
        $this->mockSettingsService->method('getAllSettings')
            ->willReturn(
                [
                    'configuration' => [
                        'publicationConsent_register' => '11',
                        'publicationConsent_schema'   => '22',
                    ],
                ]
            );

        $result = $this->service->getConsentConfig();
        $this->assertNotNull($result);
        $this->assertSame('11', $result['register']);
        $this->assertSame('22', $result['schema']);

    }//end testGetConsentConfigReturnsAutoPopulatedValues()


    /**
     * Test createFromRequest delegates to ConsentService
     *
     * @return void
     */
    public function testCreateFromRequestDelegatesToConsentService(): void
    {
        $expected = ['id' => 'uuid-1', 'consentStatus' => 'pending'];
        $this->mockConsentService->expects($this->once())
            ->method('createConsentRequest')
            ->with('doc-1', 'PERSON', 'Jan de Vries', 'reg-1', 'schema-1', [])
            ->willReturn($expected);

        $result = $this->service->createFromRequest(
            ['documentId' => 'doc-1', 'entityType' => 'PERSON', 'entityText' => 'Jan de Vries'],
            'reg-1',
            'schema-1'
        );

        $this->assertEquals($expected, $result);

    }//end testCreateFromRequestDelegatesToConsentService()


    /**
     * Test getConsentsByDocument delegates to ConsentService
     *
     * @return void
     */
    public function testGetConsentsByDocumentDelegatesToConsentService(): void
    {
        $expected = [['id' => '1'], ['id' => '2']];
        $this->mockConsentService->expects($this->once())
            ->method('getConsentsByDocument')
            ->with('doc-1', 'reg-1', 'schema-1')
            ->willReturn($expected);

        $result = $this->service->getConsentsByDocument('doc-1', 'reg-1', 'schema-1');
        $this->assertCount(2, $result);

    }//end testGetConsentsByDocumentDelegatesToConsentService()


}//end class
