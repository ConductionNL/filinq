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
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\ConsentCrudService;
use OCA\DocuDesk\Service\ConsentService;
use OCA\DocuDesk\Service\SettingsService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockSettingsService = $this->createMock(SettingsService::class);
        $this->mockConsentService  = $this->createMock(ConsentService::class);

        $this->service = new ConsentCrudService(
            $this->mockSettingsService,
            $this->mockConsentService
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
            ->willReturn([
                'configuration' => [
                    'publicationConsent_register' => '',
                    'publicationConsent_schema'   => '',
                ],
            ]);

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
            ->willReturn([
                'configuration' => [
                    'publicationConsent_register' => 'reg-1',
                    'publicationConsent_schema'   => 'schema-1',
                ],
            ]);

        $result = $this->service->getConsentConfig();
        $this->assertNotNull($result);
        $this->assertEquals('reg-1', $result['register']);
        $this->assertEquals('schema-1', $result['schema']);

    }//end testGetConsentConfigReturnsConfigWhenConfigured()


    /**
     * Test createFromRequest delegates to ConsentService with only the three
     * documented fields (documentId, entityType, entityText) — no $extra.
     *
     * @return void
     */
    public function testCreateFromRequestDelegatesToConsentService(): void
    {
        $expected = ['id' => 'uuid-1', 'consentStatus' => 'pending'];
        $this->mockConsentService->expects($this->once())
            ->method('createConsentRequest')
            ->with('doc-1', 'PERSON', 'Jan de Vries', 'reg-1', 'schema-1')
            ->willReturn($expected);

        $result = $this->service->createFromRequest(
            ['documentId' => 'doc-1', 'entityType' => 'PERSON', 'entityText' => 'Jan de Vries'],
            'reg-1',
            'schema-1'
        );

        $this->assertEquals($expected, $result);

    }//end testCreateFromRequestDelegatesToConsentService()


    /**
     * Extra request fields (e.g. consentStatus, publicationDecision, userId)
     * must be dropped before the record is written — callers must not be able
     * to force internal state at creation time (finding #290b).
     *
     * The mock asserts that createConsentRequest is called WITHOUT any extra
     * fields, regardless of what the caller sends.
     *
     * @return void
     */
    public function testCreateFromRequestDropsExtraFields(): void
    {
        $expected = ['id' => 'uuid-2', 'consentStatus' => 'pending'];

        // The mock must be called with only the three positional args + register + schema.
        // No extra $extra array should be forwarded.
        $this->mockConsentService->expects($this->once())
            ->method('createConsentRequest')
            ->with('doc-2', 'ORGANISATION', 'Gemeente Amsterdam', 'reg-1', 'schema-1')
            ->willReturn($expected);

        $result = $this->service->createFromRequest(
            [
                'documentId'          => 'doc-2',
                'entityType'          => 'ORGANISATION',
                'entityText'          => 'Gemeente Amsterdam',
                // These extra fields must be silently dropped (finding #290b).
                'consentStatus'       => 'granted',
                'publicationDecision' => 'approved',
                'userId'              => 'attacker',
                '_route'              => 'some.route',
                '_method'             => 'POST',
            ],
            'reg-1',
            'schema-1'
        );

        $this->assertEquals($expected, $result);

    }//end testCreateFromRequestDropsExtraFields()


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
