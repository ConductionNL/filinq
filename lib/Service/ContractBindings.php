<?php

/**
 * Bindings of the Kernel contracts to their Service implementations
 *
 * Nextcloud autowires concrete classes but not interfaces, so every contract
 * in OCA\Filinq\Kernel\Contracts that a caller type-hints needs an alias to
 * the class that implements it. The Service component states its own
 * bindings here, so the AppInfo bootstrap does not name each service.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Kernel\Contracts\AnonymiserBackendStateClientInterface;
use OCA\Filinq\Kernel\Contracts\AnonymizationServiceInterface;
use OCA\Filinq\Kernel\Contracts\AnonymizeRequestServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchAnonymizeServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchExtractionServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchReportServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchRequestServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchStateServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchUploadServiceInterface;
use OCA\Filinq\Kernel\Contracts\BulkSigningRunnerInterface;
use OCA\Filinq\Kernel\Contracts\BulkSigningServiceInterface;
use OCA\Filinq\Kernel\Contracts\CaseArchiveServiceInterface;
use OCA\Filinq\Kernel\Contracts\ClassificationDecisionServiceInterface;
use OCA\Filinq\Kernel\Contracts\ConformanceServiceInterface;
use OCA\Filinq\Kernel\Contracts\ConsentCrudServiceInterface;
use OCA\Filinq\Kernel\Contracts\ConsentServiceInterface;
use OCA\Filinq\Kernel\Contracts\ContractDatesInterface;
use OCA\Filinq\Kernel\Contracts\ContractPartiesInterface;
use OCA\Filinq\Kernel\Contracts\ContractRepositoryInterface;
use OCA\Filinq\Kernel\Contracts\ContractServiceInterface;
use OCA\Filinq\Kernel\Contracts\ContractSigningLinkInterface;
use OCA\Filinq\Kernel\Contracts\ContractTermSuggestionServiceInterface;
use OCA\Filinq\Kernel\Contracts\CorrespondenceServiceInterface;
use OCA\Filinq\Kernel\Contracts\CustomDictionaryAccessGateInterface;
use OCA\Filinq\Kernel\Contracts\CustomDictionaryServiceInterface;
use OCA\Filinq\Kernel\Contracts\DemoDataServiceInterface;
use OCA\Filinq\Kernel\Contracts\DocumentComparisonServiceInterface;
use OCA\Filinq\Kernel\Contracts\DocumentDomainServiceInterface;
use OCA\Filinq\Kernel\Contracts\DocumentFinalityRuleServiceInterface;
use OCA\Filinq\Kernel\Contracts\DocumentMergeServiceInterface;
use OCA\Filinq\Kernel\Contracts\DocumentReviewServiceInterface;
use OCA\Filinq\Kernel\Contracts\DocumentSanitizationServiceInterface;
use OCA\Filinq\Kernel\Contracts\DocumentServiceInterface;
use OCA\Filinq\Kernel\Contracts\DocumentValidationServiceInterface;
use OCA\Filinq\Kernel\Contracts\DocumentVersionServiceInterface;
use OCA\Filinq\Kernel\Contracts\DomainFolderReconcilerInterface;
use OCA\Filinq\Kernel\Contracts\DomainFolderServiceInterface;
use OCA\Filinq\Kernel\Contracts\DossierManagementServiceInterface;
use OCA\Filinq\Kernel\Contracts\DownloadAgreementGateInterface;
use OCA\Filinq\Kernel\Contracts\EmailDocumentRepositoryInterface;
use OCA\Filinq\Kernel\Contracts\EmailIngestionServiceInterface;
use OCA\Filinq\Kernel\Contracts\EmailIngestionSettingsInterface;
use OCA\Filinq\Kernel\Contracts\EmlPreviewServiceInterface;
use OCA\Filinq\Kernel\Contracts\EntityConsolidationServiceInterface;
use OCA\Filinq\Kernel\Contracts\EntitySearchServiceInterface;
use OCA\Filinq\Kernel\Contracts\ExternalMountValidatorInterface;
use OCA\Filinq\Kernel\Contracts\FileListingServiceInterface;
use OCA\Filinq\Kernel\Contracts\FinalDocumentCorrectionServiceInterface;
use OCA\Filinq\Kernel\Contracts\FinalDocumentFailureMapperInterface;
use OCA\Filinq\Kernel\Contracts\FinalDocumentServiceInterface;
use OCA\Filinq\Kernel\Contracts\FinalDocumentUnfreezeServiceInterface;
use OCA\Filinq\Kernel\Contracts\FinancialExtractionServiceInterface;
use OCA\Filinq\Kernel\Contracts\FlatFileListServiceInterface;
use OCA\Filinq\Kernel\Contracts\FolderBatchServiceInterface;
use OCA\Filinq\Kernel\Contracts\FormatMatrixServiceInterface;
use OCA\Filinq\Kernel\Contracts\GenerationRequestServiceInterface;
use OCA\Filinq\Kernel\Contracts\GlAccountSuggestionServiceInterface;
use OCA\Filinq\Kernel\Contracts\InboundClassificationServiceInterface;
use OCA\Filinq\Kernel\Contracts\IntakeDetachmentServiceInterface;
use OCA\Filinq\Kernel\Contracts\IntakeNotificationReachInterface;
use OCA\Filinq\Kernel\Contracts\IntakeReadingProgressInterface;
use OCA\Filinq\Kernel\Contracts\IntakeRepositoryInterface;
use OCA\Filinq\Kernel\Contracts\IntakeRoutingServiceInterface;
use OCA\Filinq\Kernel\Contracts\IntakeServiceInterface;
use OCA\Filinq\Kernel\Contracts\LegalBasesSummaryServiceInterface;
use OCA\Filinq\Kernel\Contracts\LegalHoldCaseServiceInterface;
use OCA\Filinq\Kernel\Contracts\LegalHoldNotificationsInterface;
use OCA\Filinq\Kernel\Contracts\LibreSignCompletionInterface;
use OCA\Filinq\Kernel\Contracts\MergeJobRepositoryInterface;
use OCA\Filinq\Kernel\Contracts\MetadataServiceInterface;
use OCA\Filinq\Kernel\Contracts\MultiFormatOutputProducerInterface;
use OCA\Filinq\Kernel\Contracts\OcrResultRepositoryInterface;
use OCA\Filinq\Kernel\Contracts\OcrRunServiceInterface;
use OCA\Filinq\Kernel\Contracts\OcrServiceInterface;
use OCA\Filinq\Kernel\Contracts\OfficeSuiteCapabilityServiceInterface;
use OCA\Filinq\Kernel\Contracts\OpenCatalogiPlatformInterface;
use OCA\Filinq\Kernel\Contracts\OpenRegisterResolverInterface;
use OCA\Filinq\Kernel\Contracts\OutputLayoutMoverInterface;
use OCA\Filinq\Kernel\Contracts\OutputLayoutResolverInterface;
use OCA\Filinq\Kernel\Contracts\PageLayoutServiceInterface;
use OCA\Filinq\Kernel\Contracts\PartySuggestionServiceInterface;
use OCA\Filinq\Kernel\Contracts\Pdfa3ConversionServiceInterface;
use OCA\Filinq\Kernel\Contracts\PdfServiceInterface;
use OCA\Filinq\Kernel\Contracts\PeriodicDocumentServiceInterface;
use OCA\Filinq\Kernel\Contracts\PolicyCrudServiceInterface;
use OCA\Filinq\Kernel\Contracts\PolicyRetroactiveServiceInterface;
use OCA\Filinq\Kernel\Contracts\PortalSigningDocumentResolverInterface;
use OCA\Filinq\Kernel\Contracts\PostRegisterReaderInterface;
use OCA\Filinq\Kernel\Contracts\PrintJobFileStoreInterface;
use OCA\Filinq\Kernel\Contracts\PrintJobRepositoryInterface;
use OCA\Filinq\Kernel\Contracts\PrintJobServiceInterface;
use OCA\Filinq\Kernel\Contracts\PseudonymMapServiceInterface;
use OCA\Filinq\Kernel\Contracts\PseudonymRestoreServiceInterface;
use OCA\Filinq\Kernel\Contracts\PublicationAccessInterface;
use OCA\Filinq\Kernel\Contracts\PublicationListComposerInterface;
use OCA\Filinq\Kernel\Contracts\PublicationPipelineServiceInterface;
use OCA\Filinq\Kernel\Contracts\PublicationStoreInterface;
use OCA\Filinq\Kernel\Contracts\RedactionOutputGuardInterface;
use OCA\Filinq\Kernel\Contracts\ReportRenderServiceInterface;
use OCA\Filinq\Kernel\Contracts\SanitizationStatusInterface;
use OCA\Filinq\Kernel\Contracts\ScanBatchRepositoryInterface;
use OCA\Filinq\Kernel\Contracts\ScanBatchServiceInterface;
use OCA\Filinq\Kernel\Contracts\ScanProfileServiceInterface;
use OCA\Filinq\Kernel\Contracts\SeparatorSheetServiceInterface;
use OCA\Filinq\Kernel\Contracts\SettingsServiceInterface;
use OCA\Filinq\Kernel\Contracts\SignerAuthSettingsInterface;
use OCA\Filinq\Kernel\Contracts\SignerStepUpServiceInterface;
use OCA\Filinq\Kernel\Contracts\SigningAuditServiceInterface;
use OCA\Filinq\Kernel\Contracts\SigningEnvelopeServiceInterface;
use OCA\Filinq\Kernel\Contracts\SigningFolderServiceInterface;
use OCA\Filinq\Kernel\Contracts\SigningMandateServiceInterface;
use OCA\Filinq\Kernel\Contracts\SigningServiceInterface;
use OCA\Filinq\Kernel\Contracts\SigningVerificationServiceInterface;
use OCA\Filinq\Kernel\Contracts\SubjectErasureRunInterface;
use OCA\Filinq\Kernel\Contracts\SubjectErasureServiceInterface;
use OCA\Filinq\Kernel\Contracts\TemplatePreviewServiceInterface;
use OCA\Filinq\Kernel\Contracts\TemplateServiceInterface;
use OCA\Filinq\Kernel\Contracts\TemplateVersionServiceInterface;
use OCA\Filinq\Kernel\Contracts\UploadFragmentReaperInterface;
use OCA\Filinq\Kernel\Contracts\UploadPolicyServiceInterface;
use OCA\Filinq\Kernel\Contracts\WizardServiceInterface;
use OCA\Filinq\Kernel\Contracts\WooProfileServiceInterface;
use OCA\Filinq\Service\BulkSigning\BulkSigningRunner;
use OCA\Filinq\Service\BulkSigning\BulkSigningService;
use OCA\Filinq\Service\Classification\ClassificationDecisionService;
use OCA\Filinq\Service\Contract\ContractDates;
use OCA\Filinq\Service\Contract\ContractParties;
use OCA\Filinq\Service\Contract\ContractRepository;
use OCA\Filinq\Service\Contract\ContractService;
use OCA\Filinq\Service\Contract\ContractSigningLink;
use OCA\Filinq\Service\Contract\ContractTermSuggestionService;
use OCA\Filinq\Service\Conversion\OutputLayoutMover;
use OCA\Filinq\Service\Conversion\OutputLayoutResolver;
use OCA\Filinq\Service\EmailIngestion\EmailDocumentRepository;
use OCA\Filinq\Service\EmailIngestion\EmailIngestionService;
use OCA\Filinq\Service\EmailIngestion\EmailIngestionSettings;
use OCA\Filinq\Service\EntitySearch\EntitySearchService;
use OCA\Filinq\Service\Intake\IntakeNotificationReach;
use OCA\Filinq\Service\Intake\IntakeReadingProgress;
use OCA\Filinq\Service\LegalHold\LegalHoldCaseService;
use OCA\Filinq\Service\LegalHold\LegalHoldNotifications;
use OCA\Filinq\Service\Ocr\OcrResultRepository;
use OCA\Filinq\Service\Ocr\OcrRunService;
use OCA\Filinq\Service\Office\OfficeSuiteCapabilityService;
use OCA\Filinq\Service\Pseudonymisation\PseudonymMapService;
use OCA\Filinq\Service\Pseudonymisation\PseudonymRestoreService;
use OCA\Filinq\Service\Publication\OpenCatalogiPlatform;
use OCA\Filinq\Service\Publication\PublicationAccess;
use OCA\Filinq\Service\Publication\PublicationPipelineService;
use OCA\Filinq\Service\Publication\PublicationStore;
use OCA\Filinq\Service\Redaction\DownloadAgreementGate;
use OCA\Filinq\Service\Redaction\PublicationListComposer;
use OCA\Filinq\Service\Redaction\RedactionOutputGuard;
use OCA\Filinq\Service\Sanitization\DocumentSanitizationService;
use OCA\Filinq\Service\Sanitization\SanitizationStatus;
use OCA\Filinq\Service\SignerAuth\SignerAuthSettings;
use OCA\Filinq\Service\SignerAuth\SignerStepUpService;
use OCA\Filinq\Service\Signing\LibreSignCompletion;
use OCA\Filinq\Service\SigningEnvelope\SigningEnvelopeService;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureRun;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureService;
use OCA\Filinq\Service\VeraPdf\ConformanceService;
use OCA\Filinq\Service\Wizard\WizardService;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

/**
 * Registers the Service component's contract bindings.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural wiring, no functional requirement.
 */
final class ContractBindings {

	/**
	 * Contract to implementing class.
	 *
	 * @var array<class-string, class-string>
	 */
	public const BINDINGS = [
		AnonymiserBackendStateClientInterface::class   => AnonymiserBackendStateClient::class,
		AnonymizationServiceInterface::class           => AnonymizationService::class,
		AnonymizeRequestServiceInterface::class        => AnonymizeRequestService::class,
		BatchAnonymizeServiceInterface::class          => BatchAnonymizeService::class,
		BatchExtractionServiceInterface::class         => BatchExtractionService::class,
		BatchReportServiceInterface::class             => BatchReportService::class,
		BatchRequestServiceInterface::class            => BatchRequestService::class,
		BatchStateServiceInterface::class              => BatchStateService::class,
		BatchUploadServiceInterface::class             => BatchUploadService::class,
		BulkSigningRunnerInterface::class              => BulkSigningRunner::class,
		BulkSigningServiceInterface::class             => BulkSigningService::class,
		CaseArchiveServiceInterface::class             => CaseArchiveService::class,
		ClassificationDecisionServiceInterface::class  => ClassificationDecisionService::class,
		ConformanceServiceInterface::class             => ConformanceService::class,
		ConsentCrudServiceInterface::class             => ConsentCrudService::class,
		ConsentServiceInterface::class                 => ConsentService::class,
		ContractDatesInterface::class                  => ContractDates::class,
		ContractPartiesInterface::class                => ContractParties::class,
		ContractRepositoryInterface::class             => ContractRepository::class,
		ContractServiceInterface::class                => ContractService::class,
		ContractSigningLinkInterface::class            => ContractSigningLink::class,
		ContractTermSuggestionServiceInterface::class  => ContractTermSuggestionService::class,
		CorrespondenceServiceInterface::class          => CorrespondenceService::class,
		CustomDictionaryAccessGateInterface::class     => CustomDictionaryAccessGate::class,
		CustomDictionaryServiceInterface::class        => CustomDictionaryService::class,
		DemoDataServiceInterface::class                => DemoDataService::class,
		DocumentComparisonServiceInterface::class      => DocumentComparisonService::class,
		DocumentDomainServiceInterface::class          => DocumentDomainService::class,
		DocumentFinalityRuleServiceInterface::class    => DocumentFinalityRuleService::class,
		DocumentMergeServiceInterface::class           => DocumentMergeService::class,
		DocumentReviewServiceInterface::class          => DocumentReviewService::class,
		DocumentSanitizationServiceInterface::class    => DocumentSanitizationService::class,
		DocumentServiceInterface::class                => DocumentService::class,
		DocumentValidationServiceInterface::class      => DocumentValidationService::class,
		DocumentVersionServiceInterface::class         => DocumentVersionService::class,
		DomainFolderReconcilerInterface::class         => DomainFolderReconciler::class,
		DomainFolderServiceInterface::class            => DomainFolderService::class,
		DossierManagementServiceInterface::class       => DossierManagementService::class,
		DownloadAgreementGateInterface::class          => DownloadAgreementGate::class,
		EmailDocumentRepositoryInterface::class        => EmailDocumentRepository::class,
		EmailIngestionServiceInterface::class          => EmailIngestionService::class,
		EmailIngestionSettingsInterface::class         => EmailIngestionSettings::class,
		EmlPreviewServiceInterface::class              => EmlPreviewService::class,
		EntityConsolidationServiceInterface::class     => EntityConsolidationService::class,
		EntitySearchServiceInterface::class            => EntitySearchService::class,
		ExternalMountValidatorInterface::class         => ExternalMountValidator::class,
		FileListingServiceInterface::class             => FileListingService::class,
		FinalDocumentCorrectionServiceInterface::class => FinalDocumentCorrectionService::class,
		FinalDocumentFailureMapperInterface::class     => FinalDocumentFailureMapper::class,
		FinalDocumentServiceInterface::class           => FinalDocumentService::class,
		FinalDocumentUnfreezeServiceInterface::class   => FinalDocumentUnfreezeService::class,
		FinancialExtractionServiceInterface::class     => FinancialExtractionService::class,
		FlatFileListServiceInterface::class            => FlatFileListService::class,
		FolderBatchServiceInterface::class             => FolderBatchService::class,
		FormatMatrixServiceInterface::class            => FormatMatrixService::class,
		GenerationRequestServiceInterface::class       => DocumentGenerationRequestService::class,
		GlAccountSuggestionServiceInterface::class     => GlAccountSuggestionService::class,
		InboundClassificationServiceInterface::class   => InboundClassificationService::class,
		IntakeDetachmentServiceInterface::class        => IntakeDetachmentService::class,
		IntakeNotificationReachInterface::class        => IntakeNotificationReach::class,
		IntakeReadingProgressInterface::class          => IntakeReadingProgress::class,
		IntakeRepositoryInterface::class               => IntakeRepository::class,
		IntakeRoutingServiceInterface::class           => IntakeRoutingService::class,
		IntakeServiceInterface::class                  => IntakeService::class,
		LegalBasesSummaryServiceInterface::class       => LegalBasesSummaryService::class,
		LegalHoldCaseServiceInterface::class           => LegalHoldCaseService::class,
		LegalHoldNotificationsInterface::class         => LegalHoldNotifications::class,
		LibreSignCompletionInterface::class            => LibreSignCompletion::class,
		MergeJobRepositoryInterface::class             => MergeJobRepository::class,
		MetadataServiceInterface::class                => MetadataService::class,
		MultiFormatOutputProducerInterface::class      => MultiFormatOutputProducer::class,
		OcrResultRepositoryInterface::class            => OcrResultRepository::class,
		OcrRunServiceInterface::class                  => OcrRunService::class,
		OcrServiceInterface::class                     => OcrService::class,
		OfficeSuiteCapabilityServiceInterface::class   => OfficeSuiteCapabilityService::class,
		OpenCatalogiPlatformInterface::class           => OpenCatalogiPlatform::class,
		OpenRegisterResolverInterface::class           => OpenRegisterResolver::class,
		OutputLayoutMoverInterface::class              => OutputLayoutMover::class,
		OutputLayoutResolverInterface::class           => OutputLayoutResolver::class,
		PageLayoutServiceInterface::class              => PageLayoutService::class,
		PartySuggestionServiceInterface::class         => PartySuggestionService::class,
		Pdfa3ConversionServiceInterface::class         => Pdfa3ConversionService::class,
		PdfServiceInterface::class                     => PdfService::class,
		PeriodicDocumentServiceInterface::class        => PeriodicDocumentService::class,
		PolicyCrudServiceInterface::class              => PolicyCrudService::class,
		PolicyRetroactiveServiceInterface::class       => PolicyRetroactiveService::class,
		PortalSigningDocumentResolverInterface::class  => PortalSigningDocumentResolver::class,
		PostRegisterReaderInterface::class             => PostRegisterReader::class,
		PrintJobFileStoreInterface::class              => PrintJobFileStore::class,
		PrintJobRepositoryInterface::class             => PrintJobRepository::class,
		PrintJobServiceInterface::class                => PrintJobService::class,
		PseudonymMapServiceInterface::class            => PseudonymMapService::class,
		PseudonymRestoreServiceInterface::class        => PseudonymRestoreService::class,
		PublicationAccessInterface::class              => PublicationAccess::class,
		PublicationListComposerInterface::class        => PublicationListComposer::class,
		PublicationPipelineServiceInterface::class     => PublicationPipelineService::class,
		PublicationStoreInterface::class               => PublicationStore::class,
		RedactionOutputGuardInterface::class           => RedactionOutputGuard::class,
		ReportRenderServiceInterface::class            => ReportRenderService::class,
		SanitizationStatusInterface::class             => SanitizationStatus::class,
		ScanBatchRepositoryInterface::class            => ScanBatchRepository::class,
		ScanBatchServiceInterface::class               => ScanBatchService::class,
		ScanProfileServiceInterface::class             => ScanProfileService::class,
		SeparatorSheetServiceInterface::class          => SeparatorSheetService::class,
		SettingsServiceInterface::class                => SettingsService::class,
		SignerAuthSettingsInterface::class             => SignerAuthSettings::class,
		SignerStepUpServiceInterface::class            => SignerStepUpService::class,
		SigningAuditServiceInterface::class            => SigningAuditService::class,
		SigningEnvelopeServiceInterface::class         => SigningEnvelopeService::class,
		SigningFolderServiceInterface::class           => SigningFolderService::class,
		SigningMandateServiceInterface::class          => SigningMandateService::class,
		SigningServiceInterface::class                 => SigningService::class,
		SigningVerificationServiceInterface::class     => SigningVerificationService::class,
		SubjectErasureRunInterface::class              => SubjectErasureRun::class,
		SubjectErasureServiceInterface::class          => SubjectErasureService::class,
		TemplatePreviewServiceInterface::class         => TemplatePreviewService::class,
		TemplateServiceInterface::class                => TemplateService::class,
		TemplateVersionServiceInterface::class         => TemplateVersionService::class,
		UploadFragmentReaperInterface::class           => UploadFragmentReaper::class,
		UploadPolicyServiceInterface::class            => UploadPolicyService::class,
		WizardServiceInterface::class                  => WizardService::class,
		WooProfileServiceInterface::class              => WooProfileService::class,
	];

	/**
	 * Alias every contract to its implementation.
	 *
	 * @param IRegistrationContext $context The registration context.
	 *
	 * @return void
	 */
	public function register(IRegistrationContext $context): void {
		foreach (self::BINDINGS as $contract => $implementation) {
			$context->registerServiceAlias($contract, $implementation);
		}

	}//end register()
}//end class
