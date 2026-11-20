<?php

/**
 * DocumentAuditController
 *
 * Serves the audit trail of one document (specify-capability-rows REQ-SCR-007).
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Kernel\Contracts\ReadableFileResolverInterface;
use OCA\Filinq\Service\Audit\DocumentActionAudit;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Serves `GET api/documents/{fileId}/audit` and `POST api/documents/{fileId}/opened`.
 *
 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
 */
class DocumentAuditController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param string                        $appName App name.
	 * @param IRequest                      $request Request object.
	 * @param DocumentActionAudit           $audit   The document trail.
	 * @param ReadableFileResolverInterface $files   The file behind an id, as the reader may read it.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly DocumentActionAudit $audit,
		private readonly ReadableFileResolverInterface $files,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * The trail of a document the caller may read, oldest first, each entry
	 * with its action, actor and moment. A file the caller may not read
	 * answers 404, exactly like one that does not exist.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return JSONResponse `{results, total}`, or 404.
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
	 */
	#[NoAdminRequired]
	public function index(int $fileId): JSONResponse {
		if ($this->mayReadFile(fileId: $fileId) === false) {
			return new JSONResponse(data: ['error' => 'Document not found'], statusCode: Http::STATUS_NOT_FOUND);
		}

		$entries = $this->audit->trail(fileId: $fileId);

		return new JSONResponse(data: ['results' => $entries, 'total' => count($entries)], statusCode: Http::STATUS_OK);

	}//end index()

	/**
	 * Write `opened` on a document the caller may read, when filinq's viewer
	 * shows it. Opening is the one action that happens in the browser, so the
	 * viewer reports it; the server still checks that the caller may read the
	 * file, and writes nothing for one they may not. A file the caller may not
	 * read answers 404, exactly like one that does not exist.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return JSONResponse `{recorded}`, or 404.
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
	 */
	#[NoAdminRequired]
	public function opened(int $fileId): JSONResponse {
		if ($this->mayReadFile(fileId: $fileId) === false) {
			return new JSONResponse(data: ['error' => 'Document not found'], statusCode: Http::STATUS_NOT_FOUND);
		}

		$recorded = $this->audit->record(fileId: $fileId, action: 'opened', context: ['route' => 'ui']);

		return new JSONResponse(data: ['recorded' => $recorded], statusCode: Http::STATUS_OK);

	}//end opened()

	/**
	 * Whether the caller may read the file: the resolver opens it only through
	 * the caller's own access, so a file they may not read and one that does
	 * not exist answer alike.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return bool True when the caller may read the file.
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
	 */
	private function mayReadFile(int $fileId): bool {
		return $this->files->node(fileId: $fileId) !== null;

	}//end mayReadFile()
}//end class
