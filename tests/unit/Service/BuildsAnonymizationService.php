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
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\AnonymisedPdfOutputService;
use OCA\Filinq\Service\AnonymizationPersistenceService;
use OCA\Filinq\Service\AnonymizationResultParser;
use OCA\Filinq\Service\AnonymizationService;
use OCA\Filinq\Service\ConfidentialityLabelService;
use OCA\Filinq\Service\ConsentCrudService;
use OCA\Filinq\Service\ConsentService;
use OCA\Filinq\Service\CustomDictionaryDetectionRunner;
use OCA\Filinq\Service\DocumentAnonymizeRunner;
use OCA\Filinq\Service\EmlAnonymizationService;
use OCA\Filinq\Service\EmlPdfAssemblyService;
use OCA\Filinq\Service\EntityDetectionService;
use OCA\Filinq\Service\FileEntityStatsService;
use OCA\Filinq\Service\GrondslagenSummaryAttacher;
use OCA\Filinq\Service\LegalBasesSummaryService;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCA\Filinq\Service\PdfConversionService;
use OCA\Filinq\Service\ProhibitionGateService;
use OCA\Filinq\Service\ProhibitionPolicyService;
use OCA\Filinq\Service\RelationSkipDecisionService;
use OCA\Filinq\Service\ReplacementVerificationService;
use OCP\App\IAppManager;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

/**
 * Builds a real AnonymizationService object graph from test doubles.
 */
trait BuildsAnonymizationService {
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
	private function makeAnonymizationServiceFrom(array $deps = []): AnonymizationService {
		$logger = ($deps['logger'] ?? new NullLogger());
		$container = ($deps['container'] ?? $this->createMock(ContainerInterface::class));
		$appManager = ($deps['appManager'] ?? $this->createMock(IAppManager::class));
		$appConfig = ($deps['appConfig'] ?? $this->createMock(IAppConfig::class));
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
				grondslagenSummary: ($deps['grondslagenSummary'] ?? $this->createMock(LegalBasesSummaryService::class))
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
