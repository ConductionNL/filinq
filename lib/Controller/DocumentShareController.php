<?php

/**
 * Share a document by link, and take a link back.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 *
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/share-document-links/specs/document-share-links/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use InvalidArgumentException;
use OCA\Filinq\Service\Share\DocumentShareService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * `POST api/shares` and `DELETE api/shares/{id}`. The session user is the
 * sharer; whether a user may take a link back is decided by the service
 * (the sharer or an admin).
 *
 * @spec openspec/changes/share-document-links/tasks.md#task-2.3
 */
class DocumentShareController extends Controller {

	/**
	 * The status codes a refusal may carry.
	 *
	 * @var array<int, int>
	 */
	private const REFUSALS = [400, 401, 403, 404, 409, 503];

	/**
	 * Constructor.
	 *
	 * @param string               $appName The app id.
	 * @param IRequest             $request The request.
	 * @param DocumentShareService $shares  Makes and takes back links.
	 * @param LoggerInterface      $logger  Logs what went wrong unexpectedly.
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly DocumentShareService $shares,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Share one document by link, as the session user.
	 *
	 * @return JSONResponse The record, the link and the expiry used; or the refusal with its reason.
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.3
	 */
	#[NoAdminRequired]
	public function create(): JSONResponse {
		$password = $this->request->getParam('password');
		try {
			$result = $this->shares->shareDocument(
				fileId: (int) $this->request->getParam('fileId', 0),
				expiresOn: (string) $this->request->getParam('expiresOn', ''),
				password: is_string($password) === true && $password !== '' ? $password : null,
				note: (string) $this->request->getParam('note', ''),
				labelConfirmed: filter_var($this->request->getParam('labelConfirmed', false), FILTER_VALIDATE_BOOLEAN)
			);
		} catch (Throwable $e) {
			return $this->refusal(exception: $e);
		}

		return new JSONResponse(data: $result, statusCode: Http::STATUS_CREATED);

	}//end create()

	/**
	 * Take a link back. Only its sharer or an admin may.
	 *
	 * @param string $id The share record's uuid.
	 *
	 * @return JSONResponse The revoked record, or the refusal with its reason.
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.3
	 */
	#[NoAdminRequired]
	public function revoke(string $id): JSONResponse {
		try {
			return new JSONResponse(data: $this->shares->revoke(recordUuid: $id), statusCode: Http::STATUS_OK);
		} catch (Throwable $e) {
			return $this->refusal(exception: $e);
		}

	}//end revoke()

	/**
	 * A refusal as a response: its own status and reason, or 500 without detail.
	 *
	 * @param Throwable $exception What went wrong.
	 *
	 * @return JSONResponse The response.
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.3
	 */
	private function refusal(Throwable $exception): JSONResponse {
		$known = ($exception instanceof InvalidArgumentException || $exception instanceof RuntimeException);
		if ($known === true && in_array($exception->getCode(), self::REFUSALS, true) === true) {
			return new JSONResponse(data: ['error' => $exception->getMessage()], statusCode: $exception->getCode());
		}

		$this->logger->error('Sharing by link failed', ['exception' => $exception->getMessage()]);

		return new JSONResponse(data: ['error' => 'The link could not be made or taken back'], statusCode: Http::STATUS_INTERNAL_SERVER_ERROR);

	}//end refusal()
}//end class
