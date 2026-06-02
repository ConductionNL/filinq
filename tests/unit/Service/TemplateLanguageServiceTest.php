<?php

/**
 * Unit tests for TemplateLanguageService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/register-i18n/tasks.md#task-2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\TemplateLanguageService;
use OCP\IConfig;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TemplateLanguageService.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @spec openspec/changes/register-i18n/tasks.md#task-2
 */
class TemplateLanguageServiceTest extends TestCase
{

    /**
     * Mocked IConfig instance.
     *
     * @var MockObject&IConfig
     */
    private MockObject $config;

    /**
     * The service under test.
     *
     * @var TemplateLanguageService
     */
    private TemplateLanguageService $service;

    /**
     * Set up test fixtures.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->config  = $this->createMock(IConfig::class);
        $this->service = new TemplateLanguageService(config: $this->config);

    }//end setUp()

    /**
     * resolveUserLanguage returns DEFAULT_LANGUAGE when user is null.
     *
     * @return void
     *
     * @spec openspec/changes/register-i18n/tasks.md#task-2
     */
    public function testResolveUserLanguageWithNullUserReturnsDefault(): void
    {
        $result = $this->service->resolveUserLanguage(user: null);
        $this->assertSame(TemplateLanguageService::DEFAULT_LANGUAGE, $result);

    }//end testResolveUserLanguageWithNullUserReturnsDefault()

    /**
     * resolveUserLanguage returns user's Nextcloud language when it is supported.
     *
     * @return void
     *
     * @spec openspec/changes/register-i18n/tasks.md#task-2
     */
    public function testResolveUserLanguageWithSupportedLanguage(): void
    {
        /*
         * @var MockObject&IUser $user
         */
        $user = $this->createMock(IUser::class);
        $user->method('getUID')->willReturn('alice');

        $this->config
            ->expects($this->once())
            ->method('getUserValue')
            ->with('alice', 'core', 'lang', '')
            ->willReturn('en');

        $result = $this->service->resolveUserLanguage(user: $user);
        $this->assertSame('en', $result);

    }//end testResolveUserLanguageWithSupportedLanguage()

    /**
     * resolveUserLanguage falls back to DEFAULT_LANGUAGE for an unsupported language.
     *
     * @return void
     *
     * @spec openspec/changes/register-i18n/tasks.md#task-2
     */
    public function testResolveUserLanguageWithUnsupportedLanguageFallsBack(): void
    {
        /*
         * @var MockObject&IUser $user
         */
        $user = $this->createMock(IUser::class);
        $user->method('getUID')->willReturn('bob');

        $this->config
            ->method('getUserValue')
            ->willReturn('de');

        $result = $this->service->resolveUserLanguage(user: $user);
        $this->assertSame(TemplateLanguageService::DEFAULT_LANGUAGE, $result);

    }//end testResolveUserLanguageWithUnsupportedLanguageFallsBack()

    /**
     * resolveUserLanguage strips region subtag from locale codes like 'en_US'.
     *
     * @return void
     *
     * @spec openspec/changes/register-i18n/tasks.md#task-2
     */
    public function testResolveUserLanguageStripsRegionSubtag(): void
    {
        /*
         * @var MockObject&IUser $user
         */
        $user = $this->createMock(IUser::class);
        $user->method('getUID')->willReturn('carol');

        $this->config
            ->method('getUserValue')
            ->willReturn('en_US');

        $result = $this->service->resolveUserLanguage(user: $user);
        $this->assertSame('en', $result);

    }//end testResolveUserLanguageStripsRegionSubtag()

    /**
     * getAvailableLanguages returns empty array for a plain string value.
     *
     * @return void
     *
     * @spec openspec/changes/register-i18n/tasks.md#task-2
     */
    public function testGetAvailableLanguagesWithStringReturnsEmpty(): void
    {
        $result = $this->service->getAvailableLanguages(fieldValue: 'Beschikking');
        $this->assertSame([], $result);

    }//end testGetAvailableLanguagesWithStringReturnsEmpty()

    /**
     * getAvailableLanguages returns language codes from a keyed array.
     *
     * @return void
     *
     * @spec openspec/changes/register-i18n/tasks.md#task-2
     */
    public function testGetAvailableLanguagesWithKeyedArray(): void
    {
        $value  = ['nl' => 'Beschikking', 'en' => 'Decision'];
        $result = $this->service->getAvailableLanguages(fieldValue: $value);
        $this->assertEqualsCanonicalizing(['nl', 'en'], $result);

    }//end testGetAvailableLanguagesWithKeyedArray()

    /**
     * resolveFieldValue returns the preferred language when available.
     *
     * @return void
     *
     * @spec openspec/changes/register-i18n/tasks.md#task-2
     */
    public function testResolveFieldValueReturnsPreferredLanguage(): void
    {
        $value  = ['nl' => 'Beschikking', 'en' => 'Decision'];
        $result = $this->service->resolveFieldValue(fieldValue: $value, preferredLanguage: 'en');

        $this->assertSame('Decision', $result['value']);
        $this->assertSame('en', $result['language']);
        $this->assertFalse($result['fallback']);

    }//end testResolveFieldValueReturnsPreferredLanguage()

    /**
     * resolveFieldValue falls back to nl when preferred language is unavailable.
     *
     * @return void
     *
     * @spec openspec/changes/register-i18n/tasks.md#task-2
     */
    public function testResolveFieldValueFallsBackToNl(): void
    {
        $value  = ['nl' => 'Beschikking', 'en' => 'Decision'];
        $result = $this->service->resolveFieldValue(fieldValue: $value, preferredLanguage: 'de');

        $this->assertSame('Beschikking', $result['value']);
        $this->assertSame('nl', $result['language']);
        $this->assertTrue($result['fallback']);

    }//end testResolveFieldValueFallsBackToNl()

    /**
     * resolveFieldValue falls back to first available when nl and en are unavailable.
     *
     * @return void
     *
     * @spec openspec/changes/register-i18n/tasks.md#task-2
     */
    public function testResolveFieldValueFallsBackToFirstAvailable(): void
    {
        $value  = ['es' => 'Decisión'];
        $result = $this->service->resolveFieldValue(fieldValue: $value, preferredLanguage: 'fr');

        $this->assertSame('Decisión', $result['value']);
        $this->assertSame('es', $result['language']);
        $this->assertTrue($result['fallback']);

    }//end testResolveFieldValueFallsBackToFirstAvailable()

    /**
     * resolveFieldValue handles a plain string (not yet language-keyed).
     *
     * @return void
     *
     * @spec openspec/changes/register-i18n/tasks.md#task-2
     */
    public function testResolveFieldValueHandlesPlainString(): void
    {
        $result = $this->service->resolveFieldValue(fieldValue: 'Hello', preferredLanguage: 'nl');

        $this->assertSame('Hello', $result['value']);
        $this->assertFalse($result['fallback']);

    }//end testResolveFieldValueHandlesPlainString()

    /**
     * buildAcceptLanguageHeader generates correct header for 'nl'.
     *
     * @return void
     *
     * @spec openspec/changes/register-i18n/tasks.md#task-2
     */
    public function testBuildAcceptLanguageHeaderForNl(): void
    {
        $header = $this->service->buildAcceptLanguageHeader(preferred: 'nl');
        $this->assertStringContainsString('nl', $header);
        $this->assertStringContainsString('en', $header);
        $this->assertStringStartsWith('nl', $header);

    }//end testBuildAcceptLanguageHeaderForNl()

    /**
     * buildAcceptLanguageHeader generates correct header for 'en'.
     *
     * @return void
     *
     * @spec openspec/changes/register-i18n/tasks.md#task-2
     */
    public function testBuildAcceptLanguageHeaderForEn(): void
    {
        $header = $this->service->buildAcceptLanguageHeader(preferred: 'en');
        $this->assertStringContainsString('en', $header);
        $this->assertStringStartsWith('en', $header);

    }//end testBuildAcceptLanguageHeaderForEn()
}//end class
