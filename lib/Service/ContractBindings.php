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
use OCA\Filinq\Kernel\Contracts\BatchStateServiceInterface;
use OCA\Filinq\Kernel\Contracts\BulkSigningRunnerInterface;
use OCA\Filinq\Kernel\Contracts\CorrespondenceServiceInterface;
use OCA\Filinq\Kernel\Contracts\DocumentMergeServiceInterface;
use OCA\Filinq\Kernel\Contracts\DocumentServiceInterface;
use OCA\Filinq\Kernel\Contracts\DomainFolderReconcilerInterface;
use OCA\Filinq\Kernel\Contracts\EmailIngestionServiceInterface;
use OCA\Filinq\Kernel\Contracts\EmailIngestionSettingsInterface;
use OCA\Filinq\Kernel\Contracts\IntakeReadingProgressInterface;
use OCA\Filinq\Kernel\Contracts\IntakeRepositoryInterface;
use OCA\Filinq\Kernel\Contracts\LibreSignCompletionInterface;
use OCA\Filinq\Kernel\Contracts\MergeJobRepositoryInterface;
use OCA\Filinq\Kernel\Contracts\OcrServiceInterface;
use OCA\Filinq\Kernel\Contracts\OutputLayoutMoverInterface;
use OCA\Filinq\Kernel\Contracts\OutputLayoutResolverInterface;
use OCA\Filinq\Kernel\Contracts\PeriodicDocumentServiceInterface;
use OCA\Filinq\Kernel\Contracts\PrintJobServiceInterface;
use OCA\Filinq\Kernel\Contracts\SettingsServiceInterface;
use OCA\Filinq\Kernel\Contracts\SigningAuditServiceInterface;
use OCA\Filinq\Kernel\Contracts\SigningServiceInterface;
use OCA\Filinq\Kernel\Contracts\UploadFragmentReaperInterface;
use OCA\Filinq\Service\BulkSigning\BulkSigningRunner;
use OCA\Filinq\Service\Conversion\OutputLayoutMover;
use OCA\Filinq\Service\Conversion\OutputLayoutResolver;
use OCA\Filinq\Service\EmailIngestion\EmailIngestionService;
use OCA\Filinq\Service\EmailIngestion\EmailIngestionSettings;
use OCA\Filinq\Service\Intake\IntakeReadingProgress;
use OCA\Filinq\Service\Signing\LibreSignCompletion;
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
		AnonymizationServiceInterface::class    => AnonymizationService::class,
		BatchStateServiceInterface::class       => BatchStateService::class,
		BulkSigningRunnerInterface::class       => BulkSigningRunner::class,
		CorrespondenceServiceInterface::class   => CorrespondenceService::class,
		DocumentMergeServiceInterface::class    => DocumentMergeService::class,
		DocumentServiceInterface::class         => DocumentService::class,
		DomainFolderReconcilerInterface::class  => DomainFolderReconciler::class,
		EmailIngestionServiceInterface::class   => EmailIngestionService::class,
		EmailIngestionSettingsInterface::class  => EmailIngestionSettings::class,
		IntakeReadingProgressInterface::class   => IntakeReadingProgress::class,
		IntakeRepositoryInterface::class        => IntakeRepository::class,
		LibreSignCompletionInterface::class     => LibreSignCompletion::class,
		MergeJobRepositoryInterface::class      => MergeJobRepository::class,
		OcrServiceInterface::class              => OcrService::class,
		OutputLayoutMoverInterface::class       => OutputLayoutMover::class,
		OutputLayoutResolverInterface::class    => OutputLayoutResolver::class,
		PeriodicDocumentServiceInterface::class => PeriodicDocumentService::class,
		PrintJobServiceInterface::class         => PrintJobService::class,
		SettingsServiceInterface::class         => SettingsService::class,
		SigningAuditServiceInterface::class     => SigningAuditService::class,
		SigningServiceInterface::class          => SigningService::class,
		UploadFragmentReaperInterface::class    => UploadFragmentReaper::class,
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
