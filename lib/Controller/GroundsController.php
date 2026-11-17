<?php

/**
 * Grounds Controller
 *
 * GET /api/grounds: the Woo refusal grounds the pickers offer, read through
 * the one resolver (dossiq, or the read-only snapshot). Read only.
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
 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-every-grounds-reader-in-filinq-goes-through-the-resolver-req-grd-002
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Kernel\AppIdentity;
use OCA\Filinq\Kernel\Contracts\RefusalGroundsResolverInterface;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Answers the Woo refusal grounds for the pickers.
 *
 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-every-grounds-reader-in-filinq-goes-through-the-resolver-req-grd-002
 */
class GroundsController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param IRequest               $request     The request.
	 * @param RefusalGroundsResolverInterface $grounds The one grounds reader.
	 * @param IUserSession           $userSession The signed-in user.
	 * @param LoggerInterface        $logger      Records a list that cannot be read.
	 */
	public function __construct(
		IRequest $request,
		private readonly RefusalGroundsResolverInterface $grounds,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: AppIdentity::APP_ID, request: $request);
	}//end __construct()

	/**
	 * The grounds, where they came from and the list version.
	 *
	 * The grounds are law, the same for every user, so any signed-in user may
	 * read them; nothing is written.
	 *
	 * @return JSONResponse 200 with source, version and grounds; 401; 503 when no list can be read.
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/tasks.md
	 */
	#[NoAdminRequired]
	public function index(): JSONResponse {
		if ($this->userSession->getUser() === null) {
			return new JSONResponse(data: ['error' => 'Not authenticated'], statusCode: Http::STATUS_UNAUTHORIZED);
		}

		try {
			return new JSONResponse(data: $this->grounds->list(), statusCode: Http::STATUS_OK);
		} catch (Throwable $e) {
			$this->logger->error('Filinq: the Woo refusal grounds cannot be read', ['exception' => $e]);
			return new JSONResponse(
				data: ['error' => 'The Woo refusal grounds cannot be read.'],
				statusCode: Http::STATUS_SERVICE_UNAVAILABLE
			);
		}
	}//end index()
}//end class
