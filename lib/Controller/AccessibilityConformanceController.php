<?php

/**
 * POST api/validation/accessibility/{fileId}: validate a file against PDF/UA-1.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 * @spec     openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-persisted-accessibility-conformance-report-is-stored-per-document-and-re-runnable-req-ddpum-002
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Exception\AccessibleRegenerationRefused;
use OCA\Filinq\Exception\VeraPdfException;
use OCA\Filinq\Kernel\AppIdentity;
use OCA\Filinq\Service\Validation\AccessibilityChecks;
use OCA\Filinq\Service\VeraPdf\AccessibilityConformanceService;
use OCA\Filinq\Service\VeraPdf\AccessibilityConformanceState;
use OCA\Filinq\Service\VeraPdf\AccessibleRegenerationService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Runs the PDF/UA-1 check on a file in the caller's own folder and stores the report.
 */
class AccessibilityConformanceController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param IRequest                        $request       The request.
	 * @param AccessibilityConformanceService $accessibility The PDF/UA check.
	 * @param IRootFolder                     $rootFolder    The file tree.
	 * @param IUserSession                    $userSession   The session.
	 * @param IL10N                           $l10n          Translations.
	 * @param LoggerInterface                 $logger        The log.
	 * @param AccessibilityConformanceState   $states        Which accessibility signal is surfaced.
	 * @param AccessibleRegenerationService|null $regeneration Regenerates a generated PDF as PDF/UA.
	 *
	 * @return void
	 */
	public function __construct(
		IRequest $request,
		private readonly AccessibilityConformanceService $accessibility,
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $userSession,
		private readonly IL10N $l10n,
		private readonly LoggerInterface $logger,
		private readonly AccessibilityConformanceState $states = new AccessibilityConformanceState(),
		private readonly ?AccessibleRegenerationService $regeneration = null,
	) {
		parent::__construct(appName: AppIdentity::APP_ID, request: $request);

	}//end __construct()

	/**
	 * Validate a file against PDF/UA-1 and store the report.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return JSONResponse {report}; 400 for a file that is not a PDF, 404 for
	 *                      one the caller cannot open, 503 with validated:false
	 *                      when veraPDF gives no verdict, 500 when the report
	 *                      cannot be stored.
	 *
	 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-persisted-accessibility-conformance-report-is-stored-per-document-and-re-runnable-req-ddpum-002
	 */
	#[NoAdminRequired]
	public function check(int $fileId): JSONResponse {
		$file = $this->ownFile(fileId: $fileId);
		if ($file === null) {
			return new JSONResponse(['error' => $this->l10n->t('File not found')], Http::STATUS_NOT_FOUND);
		}

		if ($file->getMimetype() !== 'application/pdf') {
			return new JSONResponse(['error' => $this->l10n->t('Only a PDF can be checked against the PDF/UA standard.')], Http::STATUS_BAD_REQUEST);
		}

		try {
			$report = $this->accessibility->checkFile(file: $file, trigger: AccessibilityConformanceService::TRIGGER_MANUAL);
		} catch (VeraPdfException $e) {
			$this->logger->warning(
				message: '[AccessibilityConformanceController] no verdict',
				context: ['fileId' => $fileId, 'reason' => $e->getReason(), 'error' => $e->getMessage()]
			);
			return new JSONResponse(
				['validated' => false, 'reason' => $e->getReason(), 'error' => $this->unavailableMessage(reason: $e->getReason())],
				Http::STATUS_SERVICE_UNAVAILABLE
			);
		} catch (Throwable $e) {
			$this->logger->error(
				message: '[AccessibilityConformanceController] the report could not be stored',
				context: ['fileId' => $fileId, 'error' => $e->getMessage()]
			);
			return new JSONResponse(
				['error' => $this->l10n->t('The check ran, but its report could not be stored. Try again later.')],
				Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}

		return new JSONResponse(['validated' => true, 'report' => $report]);

	}//end check()

	/**
	 * The accessibility state of a file: its stored PDF/UA-1 report, the
	 * heuristic findings, and which of the two is surfaced. The validator's
	 * verdict wins when there is one; without it the heuristics are labelled
	 * as heuristic. Runs no validation.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return JSONResponse {report, conformance, heuristicFindings}; 400 for a
	 *                      file that is not a PDF, 404 for one the caller
	 *                      cannot open, 500 when the report cannot be read.
	 *
	 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-validator-verdict-is-the-authoritative-conformance-heuristics-remain-the-floor-req-ddpum-004
	 */
	#[NoAdminRequired]
	public function show(int $fileId): JSONResponse {
		$file = $this->ownFile(fileId: $fileId);
		if ($file === null) {
			return new JSONResponse(['error' => $this->l10n->t('File not found')], Http::STATUS_NOT_FOUND);
		}

		if ($file->getMimetype() !== 'application/pdf') {
			return new JSONResponse(['error' => $this->l10n->t('Only a PDF can be checked against the PDF/UA standard.')], Http::STATUS_BAD_REQUEST);
		}

		try {
			$report = $this->accessibility->reportFor(fileId: $fileId);
		} catch (Throwable $e) {
			$this->logger->error(
				message: '[AccessibilityConformanceController] the report could not be read',
				context: ['fileId' => $fileId, 'error' => $e->getMessage()]
			);
			return new JSONResponse(
				['error' => $this->l10n->t('The accessibility report could not be read. Try again later.')],
				Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}

		$heuristic = $this->heuristicFindings(file: $file);

		return new JSONResponse(
			[
				'report' => $report,
				'conformance' => $this->states->resolve(
					report: $report,
					heuristicFindings: $heuristic,
					validatorAvailable: $this->accessibility->isAvailable()
				),
				'heuristicFindings' => $heuristic,
				'regenerable' => ($this->regeneration !== null && $this->regeneration->provenanceOf(fileId: $fileId) !== null),
			]
		);

	}//end show()

	/**
	 * Regenerate a PDF Filinq generated as PDF/UA: a new version is stored
	 * only when the new copy passes the check, otherwise the original stays.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return JSONResponse {replaced, report}; 400 for a file that is not a PDF,
	 *                      403 for one the caller cannot change, 404 for one
	 *                      they cannot open, 409 with not-generated-by-filinq
	 *                      for a PDF Filinq did not generate, 503 when veraPDF
	 *                      gives no verdict, 500 when the copy cannot be made.
	 *
	 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-document-filinq-generated-can-be-regenerated-as-pdfua-an-imported-one-cannot-req-ddpum-008
	 */
	#[NoAdminRequired]
	public function regenerate(int $fileId): JSONResponse {
		$file = $this->ownFile(fileId: $fileId);
		if ($file === null) {
			return new JSONResponse(['error' => $this->l10n->t('File not found')], Http::STATUS_NOT_FOUND);
		}

		if ($file->getMimetype() !== 'application/pdf') {
			return new JSONResponse(['error' => $this->l10n->t('Only a PDF can be checked against the PDF/UA standard.')], Http::STATUS_BAD_REQUEST);
		}

		if ($file->isUpdateable() === false) {
			return new JSONResponse(['error' => $this->l10n->t('You cannot change this file.')], Http::STATUS_FORBIDDEN);
		}

		if ($this->regeneration === null) {
			return new JSONResponse(
				['error' => $this->l10n->t('The document could not be made accessible. Try again later.')],
				Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}

		$user = $this->userSession->getUser();
		try {
			$result = $this->regeneration->regenerate(file: $file, userId: (string) $user?->getUID());
		} catch (AccessibleRegenerationRefused $e) {
			return new JSONResponse(
				[
					'reason' => $e->getReason(),
					'error' => $this->l10n->t(
						'Filinq did not generate this PDF and does not retag imported pages, so it cannot make it accessible. Re-author it from an accessible source.'
					),
				],
				Http::STATUS_CONFLICT
			);
		} catch (VeraPdfException $e) {
			return new JSONResponse(
				['validated' => false, 'reason' => $e->getReason(), 'error' => $this->unavailableMessage(reason: $e->getReason())],
				Http::STATUS_SERVICE_UNAVAILABLE
			);
		} catch (Throwable $e) {
			$this->logger->error(
				message: '[AccessibilityConformanceController] the accessible copy could not be made',
				context: ['fileId' => $fileId, 'error' => $e->getMessage()]
			);
			return new JSONResponse(
				['error' => $this->l10n->t('The document could not be made accessible. Try again later.')],
				Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}//end try

		return new JSONResponse($result);

	}//end regenerate()

	/**
	 * The heuristic accessibility findings of a file at default severities.
	 *
	 * @param File $file The PDF.
	 *
	 * @return array<int, array<string, mixed>> The findings; none when the file cannot be read.
	 */
	private function heuristicFindings(File $file): array {
		try {
			$content = $file->getContent();
		} catch (Throwable) {
			return [];
		}

		return (new AccessibilityChecks())->findings(profile: [], mime: 'application/pdf', content: $content);

	}//end heuristicFindings()

	/**
	 * The file with this id in the caller's own folder.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return File|null The file, or null when there is no caller or no such file for them.
	 */
	private function ownFile(int $fileId): ?File {
		$user = $this->userSession->getUser();
		if ($user === null || $fileId <= 0) {
			return null;
		}

		try {
			$nodes = $this->rootFolder->getUserFolder($user->getUID())->getById($fileId);
		} catch (Throwable) {
			return null;
		}

		$node = ($nodes[0] ?? null);
		if ($node instanceof File) {
			return $node;
		}

		return null;

	}//end ownFile()

	/**
	 * Why no verdict came, for the person who asked. Never a verdict itself.
	 *
	 * @param string $reason A VeraPdfException reason.
	 *
	 * @return string The sentence.
	 */
	private function unavailableMessage(string $reason): string {
		return match ($reason) {
			VeraPdfException::REASON_UNAVAILABLE => $this->l10n->t(
				'The PDF/UA validator is not installed on the server, so this document was not checked for PDF/UA conformance.'
			),
			VeraPdfException::REASON_TIMEOUT => $this->l10n->t(
				'The PDF/UA validator took too long and was stopped, so this document was not checked for PDF/UA conformance.'
			),
			default => $this->l10n->t('The PDF/UA validator could not read this document, so it was not checked for PDF/UA conformance.'),
		};

	}//end unavailableMessage()
}//end class
