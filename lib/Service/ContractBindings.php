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

use OCA\Filinq\Kernel\Contracts\AnonymizationServiceInterface;
use OCA\Filinq\Kernel\Contracts\AnonymizeRequestServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchAnonymizeServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchRequestServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchStateServiceInterface;
use OCA\Filinq\Kernel\Contracts\BulkSigningRunnerInterface;
use OCA\Filinq\Kernel\Contracts\CaseArchiveServiceInterface;
use OCA\Filinq\Kernel\Contracts\ConsentCrudServiceInterface;
use OCA\Filinq\Kernel\Contracts\ConsentServiceInterface;
use OCA\Filinq\Kernel\Contracts\CorrespondenceServiceInterface;
use OCA\Filinq\Kernel\Contracts\CustomDictionaryServiceInterface;
use OCA\Filinq\Kernel\Contracts\DemoDataServiceInterface;
use OCA\Filinq\Kernel\Contracts\DocumentComparisonServiceInterface;
use OCA\Filinq\Kernel\Contracts\DocumentFinalityRuleServiceInterface;
use OCA\Filinq\Kernel\Contracts\DocumentMergeServiceInterface;
use OCA\Filinq\Kernel\Contracts\DocumentReviewServiceInterface;
use OCA\Filinq\Kernel\Contracts\DocumentSanitizationServiceInterface;
use OCA\Filinq\Kernel\Contracts\DocumentServiceInterface;
use OCA\Filinq\Kernel\Contracts\DocumentValidationServiceInterface;
use OCA\Filinq\Kernel\Contracts\DocumentVersionServiceInterface;
use OCA\Filinq\Kernel\Contracts\DomainFolderReconcilerInterface;
use OCA\Filinq\Kernel\Contracts\DomainFolderServiceInterface;
use OCA\Filinq\Kernel\Contracts\DownloadAgreementGateInterface;
use OCA\Filinq\Kernel\Contracts\EmailIngestionServiceInterface;
use OCA\Filinq\Kernel\Contracts\EmailIngestionSettingsInterface;
use OCA\Filinq\Kernel\Contracts\EntityConsolidationServiceInterface;
use OCA\Filinq\Kernel\Contracts\FinalDocumentServiceInterface;
use OCA\Filinq\Kernel\Contracts\FinancialExtractionServiceInterface;
use OCA\Filinq\Kernel\Contracts\FolderBatchServiceInterface;
use OCA\Filinq\Kernel\Contracts\GlAccountSuggestionServiceInterface;
use OCA\Filinq\Kernel\Contracts\InboundClassificationServiceInterface;
use OCA\Filinq\Kernel\Contracts\IntakeReadingProgressInterface;
use OCA\Filinq\Kernel\Contracts\IntakeRepositoryInterface;
use OCA\Filinq\Kernel\Contracts\IntakeServiceInterface;
use OCA\Filinq\Kernel\Contracts\LegalBasesSummaryServiceInterface;
use OCA\Filinq\Kernel\Contracts\LibreSignCompletionInterface;
use OCA\Filinq\Kernel\Contracts\MergeJobRepositoryInterface;
use OCA\Filinq\Kernel\Contracts\MetadataServiceInterface;
use OCA\Filinq\Kernel\Contracts\MultiFormatOutputProducerInterface;
use OCA\Filinq\Kernel\Contracts\OcrServiceInterface;
use OCA\Filinq\Kernel\Contracts\OutputLayoutMoverInterface;
use OCA\Filinq\Kernel\Contracts\OutputLayoutResolverInterface;
use OCA\Filinq\Kernel\Contracts\PageLayoutServiceInterface;
use OCA\Filinq\Kernel\Contracts\PartySuggestionServiceInterface;
use OCA\Filinq\Kernel\Contracts\PdfServiceInterface;
use OCA\Filinq\Kernel\Contracts\PeriodicDocumentServiceInterface;
use OCA\Filinq\Kernel\Contracts\PolicyCrudServiceInterface;
use OCA\Filinq\Kernel\Contracts\PolicyRetroactiveServiceInterface;
use OCA\Filinq\Kernel\Contracts\PostRegisterReaderInterface;
use OCA\Filinq\Kernel\Contracts\PrintJobServiceInterface;
use OCA\Filinq\Kernel\Contracts\PseudonymRestoreServiceInterface;
use OCA\Filinq\Kernel\Contracts\PublicationStoreInterface;
use OCA\Filinq\Kernel\Contracts\ReportRenderServiceInterface;
use OCA\Filinq\Kernel\Contracts\ScanBatchServiceInterface;
use OCA\Filinq\Kernel\Contracts\SettingsServiceInterface;
use OCA\Filinq\Kernel\Contracts\SignerAuthSettingsInterface;
use OCA\Filinq\Kernel\Contracts\SigningAuditServiceInterface;
use OCA\Filinq\Kernel\Contracts\SigningEnvelopeServiceInterface;
use OCA\Filinq\Kernel\Contracts\SigningFolderServiceInterface;
use OCA\Filinq\Kernel\Contracts\SigningServiceInterface;
use OCA\Filinq\Kernel\Contracts\SigningVerificationServiceInterface;
use OCA\Filinq\Kernel\Contracts\SubjectErasureServiceInterface;
use OCA\Filinq\Kernel\Contracts\TemplateServiceInterface;
use OCA\Filinq\Kernel\Contracts\TemplateVersionServiceInterface;
use OCA\Filinq\Kernel\Contracts\UploadFragmentReaperInterface;
use OCA\Filinq\Kernel\Contracts\UploadPolicyServiceInterface;
use OCA\Filinq\Service\BulkSigning\BulkSigningRunner;
use OCA\Filinq\Service\Conversion\OutputLayoutMover;
use OCA\Filinq\Service\Conversion\OutputLayoutResolver;
use OCA\Filinq\Service\EmailIngestion\EmailIngestionService;
use OCA\Filinq\Service\EmailIngestion\EmailIngestionSettings;
use OCA\Filinq\Service\Intake\IntakeReadingProgress;
use OCA\Filinq\Service\Pseudonymisation\PseudonymRestoreService;
use OCA\Filinq\Service\Publication\PublicationStore;
use OCA\Filinq\Service\Redaction\DownloadAgreementGate;
use OCA\Filinq\Service\Sanitization\DocumentSanitizationService;
use OCA\Filinq\Service\SignerAuth\SignerAuthSettings;
use OCA\Filinq\Service\Signing\LibreSignCompletion;
use OCA\Filinq\Service\SigningEnvelope\SigningEnvelopeService;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureService;
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
		AnonymizationServiceInterface::class         => AnonymizationService::class,
		AnonymizeRequestServiceInterface::class      => AnonymizeRequestService::class,
		BatchAnonymizeServiceInterface::class        => BatchAnonymizeService::class,
		BatchRequestServiceInterface::class          => BatchRequestService::class,
		BatchStateServiceInterface::class            => BatchStateService::class,
		BulkSigningRunnerInterface::class            => BulkSigningRunner::class,
		CaseArchiveServiceInterface::class           => CaseArchiveService::class,
		ConsentCrudServiceInterface::class           => ConsentCrudService::class,
		ConsentServiceInterface::class               => ConsentService::class,
		CorrespondenceServiceInterface::class        => CorrespondenceService::class,
		CustomDictionaryServiceInterface::class      => CustomDictionaryService::class,
		DemoDataServiceInterface::class              => DemoDataService::class,
		DocumentComparisonServiceInterface::class    => DocumentComparisonService::class,
		DocumentFinalityRuleServiceInterface::class  => DocumentFinalityRuleService::class,
		DocumentMergeServiceInterface::class         => DocumentMergeService::class,
		DocumentReviewServiceInterface::class        => DocumentReviewService::class,
		DocumentSanitizationServiceInterface::class  => DocumentSanitizationService::class,
		DocumentServiceInterface::class              => DocumentService::class,
		DocumentValidationServiceInterface::class    => DocumentValidationService::class,
		DocumentVersionServiceInterface::class       => DocumentVersionService::class,
		DomainFolderReconcilerInterface::class       => DomainFolderReconciler::class,
		DomainFolderServiceInterface::class          => DomainFolderService::class,
		DownloadAgreementGateInterface::class        => DownloadAgreementGate::class,
		EmailIngestionServiceInterface::class        => EmailIngestionService::class,
		EmailIngestionSettingsInterface::class       => EmailIngestionSettings::class,
		EntityConsolidationServiceInterface::class   => EntityConsolidationService::class,
		FinalDocumentServiceInterface::class         => FinalDocumentService::class,
		FinancialExtractionServiceInterface::class   => FinancialExtractionService::class,
		FolderBatchServiceInterface::class           => FolderBatchService::class,
		GlAccountSuggestionServiceInterface::class   => GlAccountSuggestionService::class,
		InboundClassificationServiceInterface::class => InboundClassificationService::class,
		IntakeReadingProgressInterface::class        => IntakeReadingProgress::class,
		IntakeRepositoryInterface::class             => IntakeRepository::class,
		IntakeServiceInterface::class                => IntakeService::class,
		LegalBasesSummaryServiceInterface::class     => LegalBasesSummaryService::class,
		LibreSignCompletionInterface::class          => LibreSignCompletion::class,
		MergeJobRepositoryInterface::class           => MergeJobRepository::class,
		MetadataServiceInterface::class              => MetadataService::class,
		MultiFormatOutputProducerInterface::class    => MultiFormatOutputProducer::class,
		OcrServiceInterface::class                   => OcrService::class,
		OutputLayoutMoverInterface::class            => OutputLayoutMover::class,
		OutputLayoutResolverInterface::class         => OutputLayoutResolver::class,
		PageLayoutServiceInterface::class            => PageLayoutService::class,
		PartySuggestionServiceInterface::class       => PartySuggestionService::class,
		PdfServiceInterface::class                   => PdfService::class,
		PeriodicDocumentServiceInterface::class      => PeriodicDocumentService::class,
		PolicyCrudServiceInterface::class            => PolicyCrudService::class,
		PolicyRetroactiveServiceInterface::class     => PolicyRetroactiveService::class,
		PostRegisterReaderInterface::class           => PostRegisterReader::class,
		PrintJobServiceInterface::class              => PrintJobService::class,
		PseudonymRestoreServiceInterface::class      => PseudonymRestoreService::class,
		PublicationStoreInterface::class             => PublicationStore::class,
		ReportRenderServiceInterface::class          => ReportRenderService::class,
		ScanBatchServiceInterface::class             => ScanBatchService::class,
		SettingsServiceInterface::class              => SettingsService::class,
		SignerAuthSettingsInterface::class           => SignerAuthSettings::class,
		SigningAuditServiceInterface::class          => SigningAuditService::class,
		SigningEnvelopeServiceInterface::class       => SigningEnvelopeService::class,
		SigningFolderServiceInterface::class         => SigningFolderService::class,
		SigningServiceInterface::class               => SigningService::class,
		SigningVerificationServiceInterface::class   => SigningVerificationService::class,
		SubjectErasureServiceInterface::class        => SubjectErasureService::class,
		TemplateServiceInterface::class              => TemplateService::class,
		TemplateVersionServiceInterface::class       => TemplateVersionService::class,
		UploadFragmentReaperInterface::class         => UploadFragmentReaper::class,
		UploadPolicyServiceInterface::class          => UploadPolicyService::class,
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
