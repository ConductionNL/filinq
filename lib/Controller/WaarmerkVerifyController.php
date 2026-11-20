<?php

/**
 * Waarmerk Verify Controller
 *
 * The public, account-free waarmerk verification page and endpoint.
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

use OCA\Filinq\Kernel\Contracts\WaarmerkVerifierInterface;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IRequest;

/**
 * Public verification (REQ-DDWMK-004).
 *
 * Neither surface serves document content or a file: the page shows status,
 * organisation, time and document name; the endpoint takes the verifier's
 * own hash, computed in their browser. Every non-valid answer is throttled
 * by the brute-force protection and has the same shape as the others.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-waarmerk-is-publicly-verifiable-fail-closed-req-ddwmk-004
 */
class WaarmerkVerifyController extends Controller {

	public const THROTTLE_ACTION = 'filinq_waarmerk_verify';

	/**
	 * Constructor.
	 *
	 * @param string $appName The app id.
	 * @param IRequest $request The request.
	 * @param WaarmerkVerifierInterface $verifier Verifies.
	 * @param IInitialState $initialState Hands the page its facts.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly WaarmerkVerifierInterface $verifier,
		private readonly IInitialState $initialState,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * The human verification page for a code (the QR target).
	 *
	 * @param string $code The verification code.
	 *
	 * @return TemplateResponse
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-waarmerk-is-publicly-verifiable-fail-closed-req-ddwmk-004
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[AnonRateLimit(limit: 30, period: 60)]
	#[BruteForceProtection(action: self::THROTTLE_ACTION)]
	public function verifyPage(string $code): TemplateResponse {
		$page = $this->verifier->page(code: $code);
		$this->initialState->provideInitialState('waarmerk', array_merge($page, ['code' => $code]));

		$response = new TemplateResponse($this->appName, 'waarmerk-verify', [], TemplateResponse::RENDER_AS_GUEST);
		if ($page['status'] === WaarmerkVerifierInterface::UNKNOWN) {
			$response->throttle(['action' => self::THROTTLE_ACTION]);
		}

		return $response;

	}//end verifyPage()

	/**
	 * Verify `{code, sha256}`.
	 *
	 * @return JSONResponse
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-waarmerk-is-publicly-verifiable-fail-closed-req-ddwmk-004
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[AnonRateLimit(limit: 30, period: 60)]
	#[BruteForceProtection(action: self::THROTTLE_ACTION)]
	public function verify(): JSONResponse {
		$answer = $this->verifier->verify(
			code: (string) $this->request->getParam('code', ''),
			sha256: (string) $this->request->getParam('sha256', '')
		);

		// 200 for every outcome: the status code is no oracle either.
		$response = new JSONResponse($answer);
		if ($answer['status'] !== WaarmerkVerifierInterface::VALID) {
			$response->throttle(['action' => self::THROTTLE_ACTION]);
		}

		return $response;

	}//end verify()
}//end class
