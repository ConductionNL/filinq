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
     * @var ConsentCrudService
     */
    private ConsentCrudService $service;

    /**
     * @var SettingsService|MockObject
     */
    private SettingsService|MockObject $mockSettingsService;

    /**
     * @var ConsentService|MockObject
     */
    private ConsentService|MockObject $mockConsentService;

    /**
     * Mock logger — promoted from a local variable to a property so the
     * server-field-strip tests (PR #147 fifth-pass) can assert on its
     * warning() calls.
     *
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;


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
        $this->mockLogger          = $this->createMock(LoggerInterface::class);

        $this->service = new ConsentCrudService(
            $this->mockSettingsService,
            $this->mockConsentService,
            $this->mockLogger
        );

    }//end setUp()


    /**
     * Test getConsentConfig returns null when not configured
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


    /**
     * Regression lock for PR #147 fifth-pass blocker — HTTP-input
     * boundary defense. The CrudService MUST strip server-controlled
     * fields from `$extra` before forwarding to `ConsentService`, AND
     * MUST log a structured security warning naming the stripped
     * keys (so probing attempts are visible in the audit stream).
     *
     * ADR-005: the log payload MUST NOT echo the attacker-supplied
     * VALUES — only the keys. The test asserts this explicitly.
     *
     * @return void
     */
    public function testCreateFromRequestStripsServerControlledFields(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('warning')
            ->willReturnCallback(
                callback: function (string $message, array $context): void {
                    $this->assertStringContainsString(needle: 'stripped', haystack: $message);
                    $this->assertArrayHasKey(key: 'strippedKeys', array: $context);
                    $this->assertContains(needle: 'policyMatch', haystack: $context['strippedKeys']);
                    $this->assertContains(needle: 'matchKind', haystack: $context['strippedKeys']);
                    // ADR-005: values MUST NOT appear in the log payload.
                    $jsonContext = json_encode($context);
                    $this->assertStringNotContainsString(needle: 'attacker-supplied-uuid', haystack: (string) $jsonContext);
                    $this->assertStringNotContainsString(needle: 'standing_consent', haystack: (string) $jsonContext);
                }
            );

        // The downstream call MUST receive an $extra WITHOUT the server-
        // controlled fields (here: empty, since the input only carried
        // server-controlled fields and the framework keys).
        $this->mockConsentService->expects($this->once())
            ->method('createConsentRequest')
            ->with('doc-1', 'PERSON', 'Jan de Vries', 'reg-1', 'schema-1', [])
            ->willReturn(['id' => 'uuid-1']);

        $this->service->createFromRequest(
            [
                'documentId'          => 'doc-1',
                'entityType'          => 'PERSON',
                'entityText'          => 'Jan de Vries',
                // Injection attempt.
                'policyMatch'         => 'attacker-supplied-uuid',
                'matchKind'           => 'standing_consent',
                'consentStatus'       => 'consent_given',
                'publicationDecision' => 'publish_with_consent',
                'notificationStatus'  => 'skipped',
                'objectionDeadline'   => null,
            ],
            'reg-1',
            'schema-1'
        );

    }//end testCreateFromRequestStripsServerControlledFields()


    /**
     * Legitimate `$extra` carrying only non-server-controlled fields
     * (e.g. `consentScope`) MUST pass through to ConsentService
     * unchanged. The strip MUST NOT over-reach.
     *
     * @return void
     */
    public function testCreateFromRequestForwardsLegitimateExtraFields(): void
    {
        $this->mockLogger->expects($this->never())->method('warning');

        $this->mockConsentService->expects($this->once())
            ->method('createConsentRequest')
            ->with('doc-1', 'PERSON', 'Jan de Vries', 'reg-1', 'schema-1', ['consentScope' => 'limited'])
            ->willReturn(['id' => 'uuid-1']);

        $this->service->createFromRequest(
            [
                'documentId'   => 'doc-1',
                'entityType'   => 'PERSON',
                'entityText'   => 'Jan de Vries',
                'consentScope' => 'limited',
            ],
            'reg-1',
            'schema-1'
        );

    }//end testCreateFromRequestForwardsLegitimateExtraFields()


}//end class
