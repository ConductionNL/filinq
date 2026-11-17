<?php

/**
 * Document Release Controller
 *
 * Releases a document on a record to the person who requested it, and
 * withdraws a release.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/specify-capability-rows/tasks.md#task-5.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Exception\DomainLinkRefusedException;
use OCA\Filinq\Kernel\Contracts\DocumentReleaseServiceInterface;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use Throwable;

/**
 * The release and withdraw routes; the service decides who may.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/specify-capability-rows/tasks.md#task-5.1
 */
class DocumentReleaseController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param string                 $appName     The app name.
	 * @param IRequest               $request     The request.
	 * @param DocumentReleaseServiceInterface $releases    Writes the releases.
	 * @param IUserSession           $userSession The current session.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly DocumentReleaseServiceInterface $releases,
		private readonly IUserSession $userSession,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Release one document on a record to the person who requested it.
	 *
	 * The body names the record (`register`, `schema`, `id`), the requester's
	 * contact reference (`contactRef`) and the kind, `decision` or `document`
	 * (`kind`, default `document`).
	 *
	 * @param string $uuid The document record.
	 *
	 * @return JSONResponse The release; 403 when the caller may not write the record.
	 *
	 * @spec openspec/changes/specify-capability-rows/tasks.md#task-5.1
	 */
	#[NoAdminRequired]
	public function release(string $uuid): JSONResponse {
		return $this->answer(
			handler: fn (): array => $this->releases->release(
				uuid: $uuid,
				record: [
					'register' => (string) $this->request->getParam('register', ''),
					'schema' => (string) $this->request->getParam('schema', ''),
					'id' => (string) $this->request->getParam('id', ''),
				],
				contactRef: (string) $this->request->getParam('contactRef', ''),
				kind: (string) $this->request->getParam('kind', 'document')
			)
		);

	}//end release()

	/**
	 * Withdraw a release.
	 *
	 * @param string $releaseId The release.
	 *
	 * @return JSONResponse The withdrawn release; 403 when the caller may not write its record.
	 *
	 * @spec openspec/changes/specify-capability-rows/tasks.md#task-5.1
	 */
	#[NoAdminRequired]
	public function withdraw(string $releaseId): JSONResponse {
		return $this->answer(handler: fn (): array => $this->releases->withdraw(releaseId: $releaseId));

	}//end withdraw()

	/**
	 * Run one call and shape its answer.
	 *
	 * @param callable $handler The call.
	 *
	 * @return JSONResponse 200 with the release, 401, 403 or 400.
	 */
	private function answer(callable $handler): JSONResponse {
		if ($this->userSession->getUser() === null) {
			return new JSONResponse(data: ['error' => 'You must be logged in to release documents.'], statusCode: Http::STATUS_UNAUTHORIZED);
		}

		try {
			$release = $handler();
		} catch (DomainLinkRefusedException $e) {
			return new JSONResponse(data: ['error' => $e->getMessage()], statusCode: Http::STATUS_FORBIDDEN);
		} catch (Throwable $e) {
			return new JSONResponse(data: ['error' => $e->getMessage()], statusCode: Http::STATUS_BAD_REQUEST);
		}

		return new JSONResponse(data: $release, statusCode: Http::STATUS_OK);

	}//end answer()
}//end class
