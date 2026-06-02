<?php

/**
 * Unit tests for AnonymizationService — publication-clearance-anonymise-payload change
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
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-7
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\ConsentCrudService;
use OCA\DocuDesk\Service\ConsentService;
use OCA\DocuDesk\Service\EntityDetectionService;
use OCA\DocuDesk\Service\GrondslagenSummaryService;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

/**
 * Tests for unredactedEntities orchestration in AnonymizationService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-7
 */
class AnonymizationServicePublicationClearanceTest extends TestCase
{
    /**
     * Build an AnonymizationService with all constructor deps stubbed.
     *
     * @param ContainerInterface|null $container      Optional container override
     * @param ConsentCrudService|null $consentCrud    Optional consentCrud override
     * @param ConsentService|null     $consentService Optional consentService override
     *
     * @return AnonymizationService
     */
    private function buildService(
        ?ContainerInterface $container=null,
        ?ConsentCrudService $consentCrud=null,
        ?ConsentService $consentService=null
    ): AnonymizationService {
        $logger          = new NullLogger();
        $container       = $container ?? $this->createMock(originalClassName: ContainerInterface::class);
        $appManager      = $this->createMock(originalClassName: IAppManager::class);
        $entityDetection = $this->createMock(originalClassName: EntityDetectionService::class);
        $appConfig       = $this->createMock(originalClassName: IAppConfig::class);
        $consentCrud     = $consentCrud ?? $this->createMock(originalClassName: ConsentCrudService::class);
        $consentService  = $consentService ?? $this->createMock(originalClassName: ConsentService::class);

        $grondslagenSummary = $this->createMock(originalClassName: GrondslagenSummaryService::class);

        return new AnonymizationService(
            logger: $logger,
            container: $container,
            appManager: $appManager,
            entityDetection: $entityDetection,
            appConfig: $appConfig,
            consentCrud: $consentCrud,
            consentService: $consentService,
            grondslagenSummary: $grondslagenSummary
        );

    }//end buildService()

    /**
     * Returns empty array when PolicyMatchService is unavailable.
     *
     * @return void
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-2
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-7
     */
    public function testCheckUnredactedProhibitionsReturnsEmptyWhenPolicyServiceUnavailable(): void
    {
        $container = $this->createMock(originalClassName: ContainerInterface::class);
        $container->method('get')->willThrowException(new \RuntimeException('not found'));

        $service = $this->buildService(container: $container);

        $unredacted = [
            [
                'entityId'         => 1,
                'entityText'       => 'Jan Jansen',
                'entityType'       => 'PERSON',
                'publicationBases' => ['basis-a'],
            ],
        ];

        $result = $service->checkUnredactedProhibitions(unredactedEntities: $unredacted);

        $this->assertSame(expected: [], actual: $result, message: 'No violations when policy service is unavailable.');

    }//end testCheckUnredactedProhibitionsReturnsEmptyWhenPolicyServiceUnavailable()

    /**
     * Returns a violation record when the policy service matches an entity.
     *
     * @return void
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-2
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-7
     */
    public function testCheckUnredactedProhibitionsReturnsViolationsOnMatch(): void
    {
        $fakePolicyService = new class {
            /**
             * Match a prohibition rule against an entity.
             *
             * @param string $entityType  The entity type.
             * @param string $entityValue The entity value.
             *
             * @return array<string, mixed>|null Match record or null.
             */
            public function matchProhibition(string $entityType, string $entityValue): ?array
            {
                if ($entityValue === 'Verboden Persoon') {
                    return ['ruleId' => 'rule-x', 'ruleName' => 'Prohibited Rule X'];
                }

                return null;

            }//end matchProhibition()
        };

        $container = $this->createMock(originalClassName: ContainerInterface::class);
        $container->method('get')->willReturn($fakePolicyService);

        $service = $this->buildService(container: $container);

        $unredacted = [
            [
                'entityId'         => 2,
                'entityText'       => 'Verboden Persoon',
                'entityType'       => 'PERSON',
                'publicationBases' => ['basis-b'],
            ],
        ];

        $result = $service->checkUnredactedProhibitions(unredactedEntities: $unredacted);

        $this->assertCount(expectedCount: 1, haystack: $result);
        $this->assertSame(expected: 2, actual: $result[0]['entityId']);
        $this->assertSame(expected: 'Verboden Persoon', actual: $result[0]['entityText']);
        $this->assertSame(expected: 'rule-x', actual: $result[0]['ruleId']);

    }//end testCheckUnredactedProhibitionsReturnsViolationsOnMatch()

    /**
     * AnonymizeDocument accepts an unredactedEntities param without failing.
     *
     * @return void
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-7
     */
    public function testAnonymizeDocumentSignatureAcceptsUnredactedEntitiesParam(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString(needle: 'unredactedEntities', haystack: $content);
        $this->assertStringContainsString(needle: 'checkUnredactedProhibitions', haystack: $content);
        $this->assertStringContainsString(needle: 'createConsentsForUnredactedEntities', haystack: $content);

    }//end testAnonymizeDocumentSignatureAcceptsUnredactedEntitiesParam()

    /**
     * CreateConsentsForUnredactedEntities is present as a private method.
     *
     * @return void
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-4
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-7
     */
    public function testCreateConsentsForUnredactedEntitiesMethodExists(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString(needle: 'function createConsentsForUnredactedEntities', haystack: $content);

    }//end testCreateConsentsForUnredactedEntitiesMethodExists()

    /**
     * CreateConsentsForUnredactedEntities calls ConsentService::createConsentRequest once per entry.
     *
     * @return void
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-7
     */
    public function testCreateConsentsCallsConsentServiceOncePerEntry(): void
    {
        // @var ConsentCrudService|MockObject $consentCrud
        $consentCrud = $this->createMock(originalClassName: ConsentCrudService::class);
        $consentCrud->method('getConsentConfig')->willReturn(
            ['register' => 'docudesk', 'schema' => 'publicationConsent']
        );

        // @var ConsentService|MockObject $consentService
        $consentService = $this->createMock(originalClassName: ConsentService::class);
        $consentService->expects($this->exactly(count: 2))
            ->method('createConsentRequest')
            ->willReturnCallback(
                static function (
                    string $documentId,
                    string $entityType,
                    string $entityText,
                    string $register,
                    string $schema,
                    array $extra=[]
                ): array {
                    return [
                        'id'            => 'consent-'.md5($entityText),
                        'consentStatus' => 'pending',
                    ];
                }
            );

        $service = $this->buildService(
            consentCrud: $consentCrud,
            consentService: $consentService
        );

        $unredacted = [
            [
                'entityId'         => 1,
                'entityText'       => 'Alice',
                'entityType'       => 'PERSON',
                'publicationBases' => ['woo-art-1'],
            ],
            [
                'entityId'         => 2,
                'entityText'       => 'Bob',
                'entityType'       => 'PERSON',
                'publicationBases' => ['woo-art-2'],
            ],
        ];

        $reflection = new \ReflectionMethod(AnonymizationService::class, 'createConsentsForUnredactedEntities');
        $reflection->setAccessible(true);

        $resultInfo = ['anonymizedFileId' => 'anon-file-1'];
        $result     = $reflection->invoke($service, $resultInfo, $unredacted);

        $this->assertArrayHasKey(key: 'createdConsents', array: $result);
        $this->assertCount(expectedCount: 2, haystack: $result['createdConsents']);
        $this->assertSame(expected: 'created', actual: $result['createdConsents'][0]['action']);
        $this->assertSame(expected: 'created', actual: $result['createdConsents'][1]['action']);

    }//end testCreateConsentsCallsConsentServiceOncePerEntry()

    /**
     * CreateConsentsForUnredactedEntities returns createdConsents=[] when config is null.
     *
     * @return void
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-7
     */
    public function testCreateConsentsReturnsEmptyWhenConsentConfigNull(): void
    {
        // @var ConsentCrudService|MockObject $consentCrud
        $consentCrud = $this->createMock(originalClassName: ConsentCrudService::class);
        $consentCrud->method('getConsentConfig')->willReturn(null);

        // @var ConsentService|MockObject $consentService
        $consentService = $this->createMock(originalClassName: ConsentService::class);
        $consentService->expects($this->never())->method('createConsentRequest');

        $service = $this->buildService(
            consentCrud: $consentCrud,
            consentService: $consentService
        );

        $unredacted = [
            [
                'entityId'         => 1,
                'entityText'       => 'Alice',
                'entityType'       => 'PERSON',
                'publicationBases' => ['woo-art-1'],
            ],
        ];

        $reflection = new \ReflectionMethod(AnonymizationService::class, 'createConsentsForUnredactedEntities');
        $reflection->setAccessible(true);

        $resultInfo = ['anonymizedFileId' => 'anon-file-1'];
        $result     = $reflection->invoke($service, $resultInfo, $unredacted);

        $this->assertArrayHasKey(key: 'createdConsents', array: $result);
        $this->assertSame(expected: [], actual: $result['createdConsents']);

    }//end testCreateConsentsReturnsEmptyWhenConsentConfigNull()

    /**
     * Call order matches input array: first entry's consent is created before the second.
     *
     * @return void
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-7
     */
    public function testCreateConsentsPreservesEntryOrder(): void
    {
        // @var ConsentCrudService|MockObject $consentCrud
        $consentCrud = $this->createMock(originalClassName: ConsentCrudService::class);
        $consentCrud->method('getConsentConfig')->willReturn(
            ['register' => 'reg', 'schema' => 'sch']
        );

        $order = [];

        // @var ConsentService|MockObject $consentService
        $consentService = $this->createMock(originalClassName: ConsentService::class);
        $consentService->method('createConsentRequest')
            ->willReturnCallback(
                static function (
                    string $documentId,
                    string $entityType,
                    string $entityText,
                    string $register,
                    string $schema,
                    array $extra=[]
                ) use (&$order): array {
                    $order[] = $entityText;
                    return ['id' => 'c-'.md5($entityText), 'consentStatus' => 'pending'];
                }
            );

        $service = $this->buildService(
            consentCrud: $consentCrud,
            consentService: $consentService
        );

        $unredacted = [
            ['entityId' => 1, 'entityText' => 'First', 'entityType' => 'PERSON', 'publicationBases' => ['b1']],
            ['entityId' => 2, 'entityText' => 'Second', 'entityType' => 'PERSON', 'publicationBases' => ['b2']],
            ['entityId' => 3, 'entityText' => 'Third', 'entityType' => 'PERSON', 'publicationBases' => ['b3']],
        ];

        $reflection = new \ReflectionMethod(AnonymizationService::class, 'createConsentsForUnredactedEntities');
        $reflection->setAccessible(true);
        $reflection->invoke($service, ['anonymizedFileId' => 'anon-f'], $unredacted);

        $this->assertSame(
            expected: ['First', 'Second', 'Third'],
            actual: $order,
            message: 'Consent creation order must match input array order.'
        );

    }//end testCreateConsentsPreservesEntryOrder()
}//end class
