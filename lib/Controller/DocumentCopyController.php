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
	 * @param int    $fileId         The file on the source record.
	 * @param string $sourceRegister The source record's register.
	 * @param string $sourceSchema   The source record's schema.
	 * @param string $sourceId       The source record's id.
	 * @param string $register       The target record's register.
	 * @param string $schema         The target record's schema.
	 * @param string $id             The target record's id.
	 *
	 * @return JSONResponse The copy; 403 when a check refuses, and nothing is written then.
	 *
	 * @spec openspec/changes/specify-capability-rows/tasks.md#task-6.1
	 */
	#[NoAdminRequired]
	public function copy(
		int $fileId = 0,
		string $sourceRegister = '',
		string $sourceSchema = '',
		string $sourceId = '',
		string $register = '',
		string $schema = '',
		string $id = ''
	): JSONResponse {
		if ($this->userSession->getUser() === null) {
			return new JSONResponse(data: ['error' => 'You must be logged in to copy documents.'], statusCode: Http::STATUS_UNAUTHORIZED);
		}

		try {
			$copy = $this->copies->copy(
				fileId: $fileId,
				source: ['register' => $sourceRegister, 'schema' => $sourceSchema, 'id' => $sourceId],
				target: ['register' => $register, 'schema' => $schema, 'id' => $id]
			);
		} catch (DomainLinkRefusedException $e) {
			return new JSONResponse(data: ['error' => $e->getMessage()], statusCode: Http::STATUS_FORBIDDEN);
		} catch (Throwable $e) {
			return new JSONResponse(data: ['error' => $e->getMessage()], statusCode: Http::STATUS_BAD_REQUEST);
		}

		return new JSONResponse(data: $copy, statusCode: Http::STATUS_OK);

	}//end copy()
}//end class
