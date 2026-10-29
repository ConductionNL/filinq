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
use OCA\Filinq\Kernel\Contracts\BatchStateServiceInterface;
use OCA\Filinq\Kernel\Contracts\BulkSigningRunnerInterface;
use OCA\Filinq\Kernel\Contracts\ConsentServiceInterface;
use OCA\Filinq\Kernel\Contracts\CorrespondenceServiceInterface;
use OCA\Filinq\Kernel\Contracts\CustomDictionaryServiceInterface;
use OCA\Filinq\Kernel\Contracts\DocumentMergeServiceInterface;
use OCA\Filinq\Kernel\Contracts\DocumentServiceInterface;
use OCA\Filinq\Kernel\Contracts\DocumentValidationServiceInterface;
use OCA\Filinq\Kernel\Contracts\DocumentVersionServiceInterface;
use OCA\Filinq\Kernel\Contracts\DomainFolderReconcilerInterface;
use OCA\Filinq\Kernel\Contracts\EmailIngestionServiceInterface;
use OCA\Filinq\Kernel\Contracts\EmailIngestionSettingsInterface;
use OCA\Filinq\Kernel\Contracts\FinalDocumentServiceInterface;
use OCA\Filinq\Kernel\Contracts\FinancialExtractionServiceInterface;
use OCA\Filinq\Kernel\Contracts\GlAccountSuggestionServiceInterface;
use OCA\Filinq\Kernel\Contracts\IntakeReadingProgressInterface;
use OCA\Filinq\Kernel\Contracts\IntakeRepositoryInterface;
use OCA\Filinq\Kernel\Contracts\IntakeServiceInterface;
use OCA\Filinq\Kernel\Contracts\LegalBasesSummaryServiceInterface;
use OCA\Filinq\Kernel\Contracts\LibreSignCompletionInterface;
use OCA\Filinq\Kernel\Contracts\MergeJobRepositoryInterface;
use OCA\Filinq\Kernel\Contracts\OcrServiceInterface;
use OCA\Filinq\Kernel\Contracts\OutputLayoutMoverInterface;
use OCA\Filinq\Kernel\Contracts\OutputLayoutResolverInterface;
use OCA\Filinq\Kernel\Contracts\PdfServiceInterface;
use OCA\Filinq\Kernel\Contracts\PeriodicDocumentServiceInterface;
use OCA\Filinq\Kernel\Contracts\PolicyCrudServiceInterface;
use OCA\Filinq\Kernel\Contracts\PolicyRetroactiveServiceInterface;
use OCA\Filinq\Kernel\Contracts\PrintJobServiceInterface;
use OCA\Filinq\Kernel\Contracts\PseudonymRestoreServiceInterface;
use OCA\Filinq\Kernel\Contracts\ScanBatchServiceInterface;
use OCA\Filinq\Kernel\Contracts\SettingsServiceInterface;
use OCA\Filinq\Kernel\Contracts\SigningAuditServiceInterface;
use OCA\Filinq\Kernel\Contracts\SigningEnvelopeServiceInterface;
use OCA\Filinq\Kernel\Contracts\SigningServiceInterface;
use OCA\Filinq\Kernel\Contracts\SigningVerificationServiceInterface;
use OCA\Filinq\Kernel\Contracts\TemplateServiceInterface;
use OCA\Filinq\Kernel\Contracts\UploadFragmentReaperInterface;
use OCA\Filinq\Service\BulkSigning\BulkSigningRunner;
use OCA\Filinq\Service\Conversion\OutputLayoutMover;
use OCA\Filinq\Service\Conversion\OutputLayoutResolver;
use OCA\Filinq\Service\EmailIngestion\EmailIngestionService;
use OCA\Filinq\Service\EmailIngestion\EmailIngestionSettings;
use OCA\Filinq\Service\Intake\IntakeReadingProgress;
use OCA\Filinq\Service\Pseudonymisation\PseudonymRestoreService;
use OCA\Filinq\Service\Signing\LibreSignCompletion;
use OCA\Filinq\Service\SigningEnvelope\SigningEnvelopeService;
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
		AnonymizationServiceInterface::class       => AnonymizationService::class,
		AnonymizeRequestServiceInterface::class    => AnonymizeRequestService::class,
		BatchAnonymizeServiceInterface::class      => BatchAnonymizeService::class,
		BatchStateServiceInterface::class          => BatchStateService::class,
		BulkSigningRunnerInterface::class          => BulkSigningRunner::class,
		ConsentServiceInterface::class             => ConsentService::class,
		CorrespondenceServiceInterface::class      => CorrespondenceService::class,
		CustomDictionaryServiceInterface::class    => CustomDictionaryService::class,
		DocumentMergeServiceInterface::class       => DocumentMergeService::class,
		DocumentServiceInterface::class            => DocumentService::class,
		DocumentValidationServiceInterface::class  => DocumentValidationService::class,
		DocumentVersionServiceInterface::class     => DocumentVersionService::class,
		DomainFolderReconcilerInterface::class     => DomainFolderReconciler::class,
		EmailIngestionServiceInterface::class      => EmailIngestionService::class,
		EmailIngestionSettingsInterface::class     => EmailIngestionSettings::class,
		FinalDocumentServiceInterface::class       => FinalDocumentService::class,
		FinancialExtractionServiceInterface::class => FinancialExtractionService::class,
		GlAccountSuggestionServiceInterface::class => GlAccountSuggestionService::class,
		IntakeReadingProgressInterface::class      => IntakeReadingProgress::class,
		IntakeRepositoryInterface::class           => IntakeRepository::class,
		IntakeServiceInterface::class              => IntakeService::class,
		LegalBasesSummaryServiceInterface::class   => LegalBasesSummaryService::class,
		LibreSignCompletionInterface::class        => LibreSignCompletion::class,
		MergeJobRepositoryInterface::class         => MergeJobRepository::class,
		OcrServiceInterface::class                 => OcrService::class,
		OutputLayoutMoverInterface::class          => OutputLayoutMover::class,
		OutputLayoutResolverInterface::class       => OutputLayoutResolver::class,
		PdfServiceInterface::class                 => PdfService::class,
		PeriodicDocumentServiceInterface::class    => PeriodicDocumentService::class,
		PolicyCrudServiceInterface::class          => PolicyCrudService::class,
		PolicyRetroactiveServiceInterface::class   => PolicyRetroactiveService::class,
		PrintJobServiceInterface::class            => PrintJobService::class,
		PseudonymRestoreServiceInterface::class    => PseudonymRestoreService::class,
		ScanBatchServiceInterface::class           => ScanBatchService::class,
		SettingsServiceInterface::class            => SettingsService::class,
		SigningAuditServiceInterface::class        => SigningAuditService::class,
		SigningEnvelopeServiceInterface::class     => SigningEnvelopeService::class,
		SigningServiceInterface::class             => SigningService::class,
		SigningVerificationServiceInterface::class => SigningVerificationService::class,
		TemplateServiceInterface::class            => TemplateService::class,
		UploadFragmentReaperInterface::class       => UploadFragmentReaper::class,
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
