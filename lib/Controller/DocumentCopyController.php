<?php

/**
 * Document Copy Controller
 *
 * Copies a document from one record to another record the user may write.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/specify-capability-rows/tasks.md#task-6.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Exception\DomainLinkRefusedException;
use OCA\Filinq\Service\DocumentCopyService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use Throwable;

/**
 * The copy route; the service does the read and write checks.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/specify-capability-rows/tasks.md#task-6.1
 */
class DocumentCopyController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param string              $appName     The app name.
	 * @param IRequest            $request     The request.
	 * @param DocumentCopyService $copies      Copies the document.
	 * @param IUserSession        $userSession The current session.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly DocumentCopyService $copies,
		private readonly IUserSession $userSession,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Copy one file of a record to another record.
	 *
	 * The body names the file (`fileId`), the source record (`sourceRegister`,
	 * `sourceSchema`, `sourceId`) and the target record (`register`, `schema`, `id`).
	 *
	 * @return JSONResponse The copy; 403 when a check refuses, and nothing is written then.
	 *
	 * @spec openspec/changes/specify-capability-rows/tasks.md#task-6.1
	 */
	#[NoAdminRequired]
	public function copy(): JSONResponse {
		if ($this->userSession->getUser() === null) {
			return new JSONResponse(data: ['error' => 'You must be logged in to copy documents.'], statusCode: Http::STATUS_UNAUTHORIZED);
		}

		try {
			$copy = $this->copies->copy(
				fileId: (int) $this->request->getParam('fileId', 0),
				source: $this->record(prefix: 'source'),
				target: $this->record(prefix: '')
			);
		} catch (DomainLinkRefusedException $e) {
			return new JSONResponse(data: ['error' => $e->getMessage()], statusCode: Http::STATUS_FORBIDDEN);
		} catch (Throwable $e) {
			return new JSONResponse(data: ['error' => $e->getMessage()], statusCode: Http::STATUS_BAD_REQUEST);
		}

		return new JSONResponse(data: $copy, statusCode: Http::STATUS_OK);

	}//end copy()

	/**
	 * One record reference from the request body, as register, schema and id.
	 *
	 * @param string $prefix 'source' for the source record, '' for the target.
	 *
	 * @return array{register: string, schema: string, id: string}
	 *
	 * @spec openspec/changes/specify-capability-rows/tasks.md#task-6.1
	 */
	private function record(string $prefix): array {
		$reference = [];
		foreach (['register', 'schema', 'id'] as $field) {
			$name = $field;
			if ($prefix !== '') {
				$name = $prefix . ucfirst($field);
			}

			$reference[$field] = (string) $this->request->getParam($name, '');
		}

		return $reference;

	}//end record()
}//end class
