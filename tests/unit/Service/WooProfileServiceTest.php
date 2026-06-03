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
 * @spec openspec/changes/enhanced-anonymization/specs/batch-anonymization/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

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
     * The service under test.
     *
     * @var WooProfileService
     */
    private WooProfileService $service;

    /**
     * Mock IAppConfig.
     *
     * @var MockObject&IAppConfig
     */
    private MockObject $appConfig;

    /**
     * Set up test fixtures.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->appConfig = $this->createMock(originalClassName: IAppConfig::class);
        $this->service   = new WooProfileService(appConfig: $this->appConfig);

    }//end setUp()

    /**
     * WHEN GET /api/anonymization/profiles is called with no stored profile,
     * THEN the default WOO profile is returned with PERSON and PHONE in anonymize.
     *
     * @return void
     *
     * @spec openspec/changes/enhanced-anonymization/specs/batch-anonymization/spec.md
     */
    public function testGetProfileReturnsDefaultWhenNotConfigured(): void
    {
        $this->appConfig
            ->method('getValueString')
            ->willReturn('');

        $profile = $this->service->getProfile();

        $this->assertArrayHasKey(key: 'anonymize', array: $profile);
        $this->assertArrayHasKey(key: 'keep', array: $profile);
        $this->assertContains(needle: 'PERSON', haystack: $profile['anonymize']);
        $this->assertContains(needle: 'PHONE', haystack: $profile['anonymize']);
        $this->assertContains(needle: 'ORGANIZATION', haystack: $profile['keep']);

    }//end testGetProfileReturnsDefaultWhenNotConfigured()

    /**
     * WHEN a stored profile exists in IAppConfig,
     * THEN getProfile returns that profile.
     *
     * @return void
     *
     * @spec openspec/changes/enhanced-anonymization/specs/batch-anonymization/spec.md
     */
    public function testGetProfileReturnsStoredProfile(): void
    {
        $stored = json_encode(['anonymize' => ['PERSON', 'PHONE'], 'keep' => ['ORGANIZATION']]);
        $this->appConfig
            ->method('getValueString')
            ->willReturn($stored);

        $profile = $this->service->getProfile();

        $this->assertSame(expected: ['PERSON', 'PHONE'], actual: $profile['anonymize']);
        $this->assertSame(expected: ['ORGANIZATION'], actual: $profile['keep']);

    }//end testGetProfileReturnsStoredProfile()

    /**
     * GIVEN a "Woo publication" rule set that anonymizes PERSON but keeps ORGANIZATION,
     * WHEN shouldAnonymize is called,
     * THEN it returns true for PERSON and false for ORGANIZATION.
     *
     * @return void
     *
     * @spec openspec/changes/enhanced-anonymization/specs/batch-anonymization/spec.md
     */
    public function testShouldAnonymizeMatchesProfile(): void
    {
        $stored = json_encode(['anonymize' => ['PERSON', 'PHONE'], 'keep' => ['ORGANIZATION']]);
        $this->appConfig
            ->method('getValueString')
            ->willReturn($stored);

        $this->assertTrue(condition: $this->service->shouldAnonymize('PERSON'));
        $this->assertTrue(condition: $this->service->shouldAnonymize('PHONE'));
        $this->assertFalse(condition: $this->service->shouldAnonymize('ORGANIZATION'));
        $this->assertFalse(condition: $this->service->shouldAnonymize('LOCATION'));

    }//end testShouldAnonymizeMatchesProfile()

    /**
     * WHEN saveProfile is called, THEN the new profile is persisted.
     *
     * @return void
     *
     * @spec openspec/changes/enhanced-anonymization/specs/batch-anonymization/spec.md
     */
    public function testSaveProfilePersistsData(): void
    {
        $this->appConfig
            ->expects($this->once())
            ->method('setValueString')
            ->with('docudesk', 'docudesk_woo_entity_profiles', $this->anything());

        $this->service->saveProfile(['anonymize' => ['BSN'], 'keep' => ['DATE']]);

    }//end testSaveProfilePersistsData()

    /**
     * WHEN IAppConfig returns malformed JSON,
     * THEN getProfile falls back to defaults.
     *
     * @return void
     *
     * @spec openspec/changes/enhanced-anonymization/specs/batch-anonymization/spec.md
     */
    public function testGetProfileFallsBackOnInvalidJson(): void
    {
        $this->appConfig
            ->method('getValueString')
            ->willReturn('not-valid-json');

        $profile = $this->service->getProfile();

        $this->assertContains(needle: 'PERSON', haystack: $profile['anonymize']);

    }//end testGetProfileFallsBackOnInvalidJson()
}//end class
