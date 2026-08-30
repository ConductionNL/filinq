<?php

/**
 * PDF/A-3 Conversion Controller
 *
 * REST endpoint exposing Pdfa3ConversionService so other apps (e.g.
 * procest's beschikking/archival pipeline, OpenRegister's TMLO/MDTO
 * e-depot SIP builder) and external integrators can convert a document
 * this Nextcloud user can already read into a PDF/A-3b compliant file
 * with MDTO/archival metadata and embedded attachments.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Exception\Pdfa3ConversionException;
use OCA\Filinq\Service\Pdfa3ConversionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Controller for the PDF/A-3 conversion endpoint.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 */
class Pdfa3ConversionController extends Controller {
	/**
	 * Constructor.
	 *
	 * @param string $appName The application name.
	 * @param IRequest $request The request object.
	 * @param LoggerInterface $logger Logger for error reporting.
	 * @param Pdfa3ConversionService $service The PDF/A-3 conversion service.
	 * @param IRootFolder $rootFolder Root folder, for IDOR-safe file resolution.
	 * @param IL10N $l10n The localization service.
	 * @param IUserSession $userSession User session for authentication.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly LoggerInterface $logger,
		private readonly Pdfa3ConversionService $service,
		private readonly IRootFolder $rootFolder,
		private readonly IL10N $l10n,
		private readonly IUserSession $userSession,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Convert an existing PDF (identified by fileId, resolved through
	 * the requesting user's folder — IDOR-safe per ADR-005) to PDF/A-3b.
	 *
	 * Body:
	 * - fileId (int, required): The source PDF's Nextcloud file id.
	 * - metadata (object, optional): MDTO/archival metadata (title, author,
	 *   subject, keywords, plus any archival fields such as identifier,
	 *   caseReference, archiefvormer, aggregatieniveau — folded into the
	 *   XMP packet and an embedded metadata.xml attachment).
	 * - attachments (array, optional): [{name, mime, content, description?,
	 *   AFRelationship?}, ...] — content is the raw attachment bytes.
	 * - filename (string, optional): Suggested download filename.
	 *
	 * @return DataDownloadResponse|JSONResponse PDF/A-3 download or a typed error response.
	 *
	 * @spec openspec/specs/pdfa3-conversion/spec.md
	 */
	#[NoAdminRequired]
	public function convert(): DataDownloadResponse|JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new JSONResponse(
				data: ['error' => $this->l10n->t('Not authenticated')],
				statusCode: Http::STATUS_UNAUTHORIZED
			);
		}

		$fileId = (int)$this->request->getParam('fileId', 0);
		if ($fileId <= 0) {
			return new JSONResponse(
				data: ['error' => $this->l10n->t('fileId is required')],
				statusCode: Http::STATUS_BAD_REQUEST
			);
		}

		// IDOR-safe resolution: a file the user cannot read is a 404, no disclosure.
		$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		$nodes = $userFolder->getById($fileId);
		if (empty($nodes) === true || ($nodes[0] instanceof File) === false) {
			return new JSONResponse(
				data: ['error' => $this->l10n->t('File not found')],
				statusCode: Http::STATUS_NOT_FOUND
			);
		}

		$metadata = $this->request->getParam('metadata', []);
		if (is_array($metadata) === false) {
			$metadata = [];
		}

		$attachments = $this->request->getParam('attachments', []);
		if (is_array($attachments) === false) {
			$attachments = [];
		}

		$filename = $this->request->getParam('filename', 'document-pdfa3.pdf');

		try {
			$result = $this->service->convertExistingPdf(
				source: $nodes[0],
				metadata: $metadata,
				attachments: $attachments
			);

			$response = new DataDownloadResponse(
				data: $result['content'],
				filename: (string)$filename,
				contentType: 'application/pdf'
			);
			// Surfaced so callers (e.g. procest's TemplateEngineAdapterInterface,
			// which expects {checksumSha256, paginas}) can read the composition
			// metadata without a second round trip.
			// ⚠️ THE `X-Docudesk-*` HEADER NAMES STAY ACROSS THE FILINQ RENAME.
			// They are a CROSS-APP response contract: dossiq's (procest's)
			// beschikking/archival pipeline reads them off this endpoint. A
			// renamed header is not an error to a consumer — it is an ABSENT
			// header, so the integration degrades silently rather than failing.
			// Rename them only together with the consumers, in a coordinated
			// change; the same applies to the `X-Docudesk-File-*` headers in
			// DocumentController.
			$response->addHeader('X-Docudesk-Pdfa3-Checksum-Sha256', $result['checksumSha256']);
			$response->addHeader('X-Docudesk-Pdfa3-Pages', (string)$result['pages']);
			$response->addHeader('X-Docudesk-Pdfa3-Conformance', $result['conformance']);

			return $response;
		} catch (Pdfa3ConversionException $e) {
			$this->logger->warning(
				'PDF/A-3 conversion failed',
				[
					'fileId' => $fileId,
					'reason' => $e->getReason(),
					'exception' => $e->getMessage(),
				]
			);

			return new JSONResponse(
				data: [
					'error' => $e->getMessage(),
					'reason' => $e->getReason(),
					'adminHint' => $e->getAdminHint(),
				],
				statusCode: $this->clampStatusCode(code: $e->getCode())
			);
		} catch (Throwable $e) {
			$this->logger->error(
				'PDF/A-3 conversion failed unexpectedly',
				['fileId' => $fileId, 'exception' => $e]
			);

			return new JSONResponse(
				data: ['error' => $this->l10n->t('PDF/A-3 conversion failed')],
				statusCode: Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}//end try

	}//end convert()

	/**
	 * Clamp an exception code to a valid HTTP status range, defaulting
	 * to 500 for anything outside [400, 599].
	 *
	 * @param int $code Candidate status code.
	 *
	 * @return int Valid HTTP status code.
	 *
	 * @psalm-suppress InvalidArgument $statusCode is clamped to int<400, 599>; Psalm wants the literal HTTP status union.
	 */
	private function clampStatusCode(int $code): int {
		if ($code >= 400 && $code < 600) {
			return $code;
		}

		return Http::STATUS_INTERNAL_SERVER_ERROR;
	}//end clampStatusCode()
}//end class
