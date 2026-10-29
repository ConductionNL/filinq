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
use OCA\Filinq\Kernel\Contracts\Pdfa3ConversionServiceInterface;
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
	 * @param Pdfa3ConversionServiceInterface $service The PDF/A-3 conversion service.
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
		private readonly Pdfa3ConversionServiceInterface $service,
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
			return $this->errorResponse(message: $this->l10n->t('Not authenticated'), status: Http::STATUS_UNAUTHORIZED);
		}

		$fileId = (int)$this->request->getParam('fileId', 0);
		if ($fileId <= 0) {
			return $this->errorResponse(message: $this->l10n->t('fileId is required'), status: Http::STATUS_BAD_REQUEST);
		}

		$source = $this->resolveReadableFile(userId: $user->getUID(), fileId: $fileId);
		if ($source === null) {
			return $this->errorResponse(message: $this->l10n->t('File not found'), status: Http::STATUS_NOT_FOUND);
		}

		$filename = (string)$this->request->getParam('filename', 'document-pdfa3.pdf');
		try {
			$result = $this->service->convertExistingPdf(
				source: $source,
				metadata: $this->arrayParam(name: 'metadata'),
				attachments: $this->arrayParam(name: 'attachments')
			);

			return $this->downloadResponse(result: $result, filename: $filename);
		} catch (Pdfa3ConversionException $e) {
			return $this->conversionErrorResponse(exception: $e, fileId: $fileId);
		} catch (Throwable $e) {
			$this->logger->error('PDF/A-3 conversion failed unexpectedly', ['fileId' => $fileId, 'exception' => $e]);

			return $this->errorResponse(message: $this->l10n->t('PDF/A-3 conversion failed'), status: Http::STATUS_INTERNAL_SERVER_ERROR);
		}//end try

	}//end convert()

	/**
	 * A JSON error response with one message.
	 *
	 * @param string $message The error message.
	 * @param int $status The HTTP status.
	 *
	 * @return JSONResponse The response.
	 *
	 * @psalm-suppress InvalidArgument $status is always one of the Http::STATUS_* constants.
	 *
	 * @spec openspec/specs/pdfa3-conversion/spec.md
	 */
	private function errorResponse(string $message, int $status): JSONResponse {
		return new JSONResponse(data: ['error' => $message], statusCode: $status);

	}//end errorResponse()

	/**
	 * Resolve a file id through the user's own folder. A file the user cannot
	 * read resolves to null, so the caller answers 404 without disclosure.
	 *
	 * @param string $userId The requesting user.
	 * @param int $fileId The requested file id.
	 *
	 * @return File|null The file, or null when it is not a readable file.
	 *
	 * @spec openspec/specs/pdfa3-conversion/spec.md
	 */
	private function resolveReadableFile(string $userId, int $fileId): ?File {
		$nodes = $this->rootFolder->getUserFolder($userId)->getById($fileId);
		if (empty($nodes) === true || ($nodes[0] instanceof File) === false) {
			return null;
		}

		return $nodes[0];

	}//end resolveReadableFile()

	/**
	 * A request parameter that must be an array, or an empty array otherwise.
	 *
	 * @param string $name The parameter name.
	 *
	 * @return array<array-key, mixed> The parameter value.
	 *
	 * @spec openspec/specs/pdfa3-conversion/spec.md
	 */
	private function arrayParam(string $name): array {
		$value = $this->request->getParam($name, []);
		if (is_array($value) === false) {
			return [];
		}

		return $value;

	}//end arrayParam()

	/**
	 * The PDF/A-3 download with its composition headers.
	 *
	 * Callers such as dossiq's archival pipeline read the composition
	 * metadata ({checksumSha256, paginas}) off these headers without a second
	 * round trip. THE `X-Docudesk-*` HEADER NAMES STAY ACROSS THE FILINQ RENAME:
	 * they are a cross-app response contract, and a renamed header is not an
	 * error to a consumer but an absent header, so the integration degrades
	 * silently. Rename them only together with the consumers; the same applies
	 * to the `X-Docudesk-File-*` headers in DocumentController.
	 *
	 * @param array<string, mixed> $result The conversion result.
	 * @param string $filename The download file name.
	 *
	 * @return DataDownloadResponse The download.
	 *
	 * @spec openspec/specs/pdfa3-conversion/spec.md
	 */
	private function downloadResponse(array $result, string $filename): DataDownloadResponse {
		$response = new DataDownloadResponse(
			data: $result['content'],
			filename: $filename,
			contentType: 'application/pdf'
		);
		$response->addHeader('X-Docudesk-Pdfa3-Checksum-Sha256', $result['checksumSha256']);
		$response->addHeader('X-Docudesk-Pdfa3-Pages', (string)$result['pages']);
		$response->addHeader('X-Docudesk-Pdfa3-Conformance', $result['conformance']);
		$response->addHeader('X-Docudesk-Pdfa3-Verified', (string) ($result['verified'] ?? 'skipped'));

		return $response;

	}//end downloadResponse()

	/**
	 * The typed error response for a conversion the service refused.
	 *
	 * @param Pdfa3ConversionException $exception The refusal.
	 * @param int $fileId The source file id, for the log.
	 *
	 * @return JSONResponse The error with its reason and admin hint.
	 *
	 * @spec openspec/specs/pdfa3-conversion/spec.md
	 */
	private function conversionErrorResponse(Pdfa3ConversionException $exception, int $fileId): JSONResponse {
		$this->logger->warning(
			'PDF/A-3 conversion failed',
			[
				'fileId' => $fileId,
				'reason' => $exception->getReason(),
				'exception' => $exception->getMessage(),
			]
		);

		return new JSONResponse(
			data: [
				'error' => $exception->getMessage(),
				'reason' => $exception->getReason(),
				'adminHint' => $exception->getAdminHint(),
			],
			statusCode: $this->clampStatusCode(code: $exception->getCode())
		);

	}//end conversionErrorResponse()

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
