<?php

/**
 * Shared factory for AnonymizationService test doubles.
 *
 * AnonymizationService is assembled from already-extracted collaborators
 * (OpenRegisterServiceLocator, ProhibitionPolicyService and
 * DocumentAnonymizeRunner). This trait wires that graph from the same low-level
 * doubles the suites used before the extraction, so each test file keeps a
 * one-call factory.
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
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\AnonymisedPdfOutputService;
use OCA\DocuDesk\Service\AnonymizationPersistenceService;
use OCA\DocuDesk\Service\AnonymizationResultParser;
use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\ConfidentialityLabelService;
use OCA\DocuDesk\Service\ConsentCrudService;
use OCA\DocuDesk\Service\ConsentService;
use OCA\DocuDesk\Service\CustomDictionaryDetectionRunner;
use OCA\DocuDesk\Service\DocumentAnonymizeRunner;
use OCA\DocuDesk\Service\EmlAnonymizationService;
use OCA\DocuDesk\Service\EmlPdfAssemblyService;
use OCA\DocuDesk\Service\EntityDetectionService;
use OCA\DocuDesk\Service\FileEntityStatsService;
use OCA\DocuDesk\Service\GrondslagenSummaryAttacher;
use OCA\DocuDesk\Service\GrondslagenSummaryService;
use OCA\DocuDesk\Service\OpenRegisterServiceLocator;
use OCA\DocuDesk\Service\PdfConversionService;
use OCA\DocuDesk\Service\ProhibitionGateService;
use OCA\DocuDesk\Service\ProhibitionPolicyService;
use OCA\DocuDesk\Service\RelationSkipDecisionService;
use OCA\DocuDesk\Service\ReplacementVerificationService;
use OCP\App\IAppManager;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

/**
 * Builds a real AnonymizationService object graph from test doubles.
 */
trait BuildsAnonymizationService
{
    /**
     * Assemble an AnonymizationService from the given (or defaulted) doubles.
     *
     * Recognised `$deps` keys: logger, container, appManager, appConfig,
     * entityDetection, consentCrud, consentService, grondslagenSummary,
     * fileEntityStats, pdfConversion, emlAssembly, confidentialityLabel,
     * dictionaryRunner, userSession, rootFolder. Anything omitted gets a
     * permissive mock.
     *
     * NOTE on `userSession` / `rootFolder`: the DEFAULT mocks deny — an
     * IUserSession mock returns null from getUser(), so the relation
     * skip-decision ownership guard refuses. Tests that exercise that path must
     * pass doubles that grant (see AnonymizationServiceTest::grantingSession()
     * and ::rootFolderResolving()).
     *
     * @param array<string, object> $deps Dependency overrides.
     *
     * @return AnonymizationService The service under test.
     */
    private function makeAnonymizationServiceFrom(array $deps=[]): AnonymizationService
    {
        $logger          = ($deps['logger'] ?? new NullLogger());
        $container       = ($deps['container'] ?? $this->createMock(ContainerInterface::class));
        $appManager      = ($deps['appManager'] ?? $this->createMock(IAppManager::class));
        $appConfig       = ($deps['appConfig'] ?? $this->createMock(IAppConfig::class));
        $entityDetection = ($deps['entityDetection'] ?? new EntityDetectionService(new AnonymizationResultParser()));

        $locator = new OpenRegisterServiceLocator($appManager, $container);

        $prohibitionPolicy = new ProhibitionPolicyService(
            logger: $logger,
            container: $container,
            locator: $locator,
            gate: new ProhibitionGateService(
                logger: $logger,
                appConfig: $appConfig,
                container: $container,
                locator: $locator
            ),
            skipDecisions: new RelationSkipDecisionService(
                logger: $logger,
                container: $container,
                locator: $locator,
                userSession: ($deps['userSession'] ?? $this->createMock(IUserSession::class)),
                rootFolder: ($deps['rootFolder'] ?? $this->createMock(IRootFolder::class))
            )
        );

        $anonymizeRunner = new DocumentAnonymizeRunner(
            logger: $logger,
            locator: $locator,
            entityDetection: $entityDetection,
            emlAnonymizer: new EmlAnonymizationService(
                logger: $logger,
                entityDetection: $entityDetection,
                emlAssembly: ($deps['emlAssembly'] ?? $this->createMock(EmlPdfAssemblyService::class))
            ),
            pdfOutput: new AnonymisedPdfOutputService(
                logger: $logger,
                pdfConversion: ($deps['pdfConversion'] ?? $this->createMock(PdfConversionService::class))
            ),
            replacementVerifier: new ReplacementVerificationService(logger: $logger),
            persistence: new AnonymizationPersistenceService(
                logger: $logger,
                locator: $locator,
                consentCrud: ($deps['consentCrud'] ?? $this->createMock(ConsentCrudService::class)),
                consentService: ($deps['consentService'] ?? $this->createMock(ConsentService::class))
            ),
            summaryAttacher: new GrondslagenSummaryAttacher(
                logger: $logger,
                grondslagenSummary: ($deps['grondslagenSummary'] ?? $this->createMock(GrondslagenSummaryService::class))
            )
        );

        return new AnonymizationService(
            logger: $logger,
            container: $container,
            locator: $locator,
            entityDetection: $entityDetection,
            dictionaryRunner: ($deps['dictionaryRunner'] ?? $this->createMock(CustomDictionaryDetectionRunner::class)),
            fileEntityStats: ($deps['fileEntityStats'] ?? $this->createMock(FileEntityStatsService::class)),
            confidentialityLabel: ($deps['confidentialityLabel'] ?? $this->createMock(ConfidentialityLabelService::class)),
            prohibitionPolicy: $prohibitionPolicy,
            anonymizeRunner: $anonymizeRunner
        );

    }//end makeAnonymizationServiceFrom()
}//end trait
