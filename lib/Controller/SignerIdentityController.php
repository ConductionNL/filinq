<?php

/**
 * Signer Identity Controller
 *
 * The step-up endpoints of the signer identity rails.
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
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\AppInfo\Application;
use OCA\Filinq\Service\SignerAuth\SignerStepUpService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Start a step-up for a signing act, and receive the broker's callback.
 *
 * `start` answers with a generic 404 for a request or signer the user has
 * nothing to do with, so it is no existence oracle. `callback` is reached by
 * a top-level redirect from the broker, which is why it carries no CSRF
 * token: the single-use OIDC `state`, bound to this user's session, is the
 * CSRF protection. It always lands the signer back on the request page.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
class SignerIdentityController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param IRequest $request The request.
	 * @param SignerStepUpService $stepUp The step-up flow.
	 * @param IURLGenerator $urlGenerator Builds the return URL.
	 * @param LoggerInterface $logger Logs why a step-up failed.
	 *
	 * @return void
	 */
	public function __construct(
		IRequest $request,
		private readonly SignerStepUpService $stepUp,
		private readonly IURLGenerator $urlGenerator,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: Application::APP_ID, request: $request);

	}//end __construct()

	/**
	 * POST /api/signing/requests/{id}/identity
	 *
	 * The signer must own the signer record (checked in the service, the
	 * same ownership check `sign()` runs).
	 *
	 * @param string $id The signing request id.
	 *
	 * @return JSONResponse The challenge and the assurance needed, or a generic 404.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	#[NoAdminRequired]
	public function start(string $id): JSONResponse {
		try {
			$challenge = $this->stepUp->start(requestId: $id, signerId: (string)$this->request->getParam('signerId', ''));
		} catch (Throwable $e) {
			$this->logger->info('Filinq: signer step-up could not start: ' . $e->getMessage());
			return new JSONResponse(['error' => 'Step-up not available'], Http::STATUS_NOT_FOUND);
		}

		return new JSONResponse($challenge);

	}//end start()

	/**
	 * GET /api/signing/identity/callback
	 *
	 * The session user must be the one who started the step-up (checked in
	 * the provider against the state's bound act).
	 *
	 * @return RedirectResponse Back to the request page, with the outcome in the query.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function callback(): RedirectResponse {
		$base = $this->urlGenerator->linkToRoute('filinq.dashboard.page');
		try {
			$act = $this->stepUp->authorizeCallback(
				code: (string)$this->request->getParam('code', ''),
				state: (string)$this->request->getParam('state', '')
			);
		} catch (Throwable $e) {
			$this->logger->info('Filinq: signer step-up failed: ' . $e->getMessage());
			return new RedirectResponse(rtrim($base, '/') . '/signing-folder?stepUp=failed');
		}

		return new RedirectResponse(
			rtrim($base, '/') . '/signing/' . rawurlencode($act['requestId'])
			. '?stepUp=done&signerId=' . rawurlencode($act['signerId'])
		);

	}//end callback()
}//end class
