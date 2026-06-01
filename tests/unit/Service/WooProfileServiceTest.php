<?php

/**
 * Unit tests for WooProfileService
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
 * @spec openspec/changes/unit-test-coverage-75/tasks.md#task-4.6
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\WooProfileService;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for WooProfileService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class WooProfileServiceTest extends TestCase
{

    /**
     * @var WooProfileService
     */
    private WooProfileService $service;

    /**
     * @var IAppConfig|MockObject
     */
    private IAppConfig|MockObject $mockAppConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockAppConfig = $this->createMock(IAppConfig::class);
        $this->service       = new WooProfileService(appConfig: $this->mockAppConfig);

    }//end setUp()

    /**
     * Test getProfile returns default profile when nothing is configured.
     *
     * @return void
     */
    public function testGetProfileReturnsDefaultWhenNotConfigured(): void
    {
        $this->mockAppConfig->method('getValueString')->willReturn('');

        $profile = $this->service->getProfile();

        $this->assertArrayHasKey('anonymize', $profile);
        $this->assertArrayHasKey('keep', $profile);
        $this->assertContains('PERSON', $profile['anonymize']);
        $this->assertContains('ORGANIZATION', $profile['keep']);

    }//end testGetProfileReturnsDefaultWhenNotConfigured()

    /**
     * Test getProfile returns stored profile when configured.
     *
     * @return void
     */
    public function testGetProfileReturnsStoredProfile(): void
    {
        $stored = json_encode(['anonymize' => ['PERSON', 'BSN'], 'keep' => ['DATE']]);
        $this->mockAppConfig->method('getValueString')->willReturn($stored);

        $profile = $this->service->getProfile();

        $this->assertSame(['PERSON', 'BSN'], $profile['anonymize']);
        $this->assertSame(['DATE'], $profile['keep']);

    }//end testGetProfileReturnsStoredProfile()

    /**
     * Test shouldAnonymize returns true for types in the anonymize list.
     *
     * @return void
     */
    public function testShouldAnonymizeReturnsTrueForAnonymizeType(): void
    {
        $this->mockAppConfig->method('getValueString')->willReturn('');

        $this->assertTrue($this->service->shouldAnonymize('PERSON'));

    }//end testShouldAnonymizeReturnsTrueForAnonymizeType()

    /**
     * Test shouldAnonymize returns false for types in the keep list.
     *
     * @return void
     */
    public function testShouldAnonymizeReturnsFalseForKeepType(): void
    {
        $this->mockAppConfig->method('getValueString')->willReturn('');

        $this->assertFalse($this->service->shouldAnonymize('ORGANIZATION'));

    }//end testShouldAnonymizeReturnsFalseForKeepType()

    /**
     * Test saveProfile calls setValueString on appConfig.
     *
     * @return void
     */
    public function testSaveProfileCallsSetValueString(): void
    {
        $profile = ['anonymize' => ['PERSON'], 'keep' => ['ORGANIZATION']];

        $this->mockAppConfig->expects($this->once())
            ->method('setValueString')
            ->with('docudesk', 'docudesk_woo_entity_profiles', json_encode($profile));

        $this->service->saveProfile($profile);

    }//end testSaveProfileCallsSetValueString()

    /**
     * Test getProfile falls back to default on invalid JSON.
     *
     * @return void
     */
    public function testGetProfileFallsBackOnInvalidJson(): void
    {
        $this->mockAppConfig->method('getValueString')->willReturn('not-valid-json');

        $profile = $this->service->getProfile();

        $this->assertContains('PERSON', $profile['anonymize']);

    }//end testGetProfileFallsBackOnInvalidJson()
}//end class
