<?php

/**
 * Public Verification Controller
 *
 * Answers an anonymous verifier holding a verification token with what the
 * signatures of that one document were found to guarantee.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/signature-verification-portal/tasks.md#task-2.3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Service\SignatureVerificationLinkService;
use OCA\Filinq\Service\SigningVerificationService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\Security\Bruteforce\IThrottler;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * The public, account-free verification endpoint.
 *
 * Every answer is 200 with the same shape: a token that names nothing, a
 * malformed token, a revoked record and a store that cannot be read all
 * answer `unknown`, so the endpoint never says whether a token exists. Every
 * `unknown` counts as a failed attempt for the brute-force throttler, which
 * the attribute then enforces per client.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signature-verification-portal/tasks.md#task-2.3
 */
class PublicVerificationController extends Controller {
	/**
	 * Brute-force throttler action for tokens that name nothing.
	 *
	 * @var string
	 */
	public const THROTTLE_ACTION = 'filinq-public-signature-verify';

	/**
	 * Constructor.
	 *
	 * @param string                           $appName  The app name.
	 * @param IRequest                         $request  The request.
	 * @param SignatureVerificationLinkService $links    Finds the record a token names.
	 * @param SigningVerificationService       $verifier Turns a record into the public verdict.
	 * @param IThrottler                       $throttler Counts tokens that name nothing.
	 * @param LoggerInterface                  $logger   Logger.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly SignatureVerificationLinkService $links,
		private readonly SigningVerificationService $verifier,
		private readonly IThrottler $throttler,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * GET /apps/filinq/api/verify/{token}
	 *
	 * The verdict for one verification token. Serves no document bytes, no
	 * path and no download: the file name, the content hash, each signature's
	 * status, level and signer as asserted, and whether content integrity and
	 * the signer are covered by the check.
	 *
	 * @param string $token The token from the verification link.
	 *
	 * @return JSONResponse Always 200, `status` `unknown` when nothing can be said.
	 *
	 * @spec openspec/changes/signature-verification-portal/tasks.md#task-2.3
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[AnonRateLimit(limit: 30, period: 60)]
	#[BruteForceProtection(action: self::THROTTLE_ACTION)]
	public function verify(string $token): JSONResponse {
		$verdict = SigningVerificationService::unknownVerdict();

		try {
			$record = $this->links->findByToken(token: $token);
			if ($record !== null) {
				$verdict = $this->verifier->verifyByRecord(record: $record);
			}
		} catch (Throwable $e) {
			// An unreadable store answers like an unknown token: the page must
			// not report anything valid, and must not differ from "no such
			// token" in a way a guesser could use.
			$this->logger->warning(
				message: '[PublicVerificationController] could not resolve a verification token',
				context: ['error' => $e->getMessage()]
			);
		}

		if ($verdict['status'] === 'unknown') {
			$this->registerUnknown(token: $token);
		}

		return new JSONResponse(data: $verdict);
	}//end verify()

	/**
	 * Count a token that named nothing against this client.
	 *
	 * @param string $token The token that was tried.
	 *
	 * @return void
	 */
	private function registerUnknown(string $token): void {
		try {
			$this->throttler->registerAttempt(
				action: self::THROTTLE_ACTION,
				ip: $this->request->getRemoteAddress(),
				metadata: ['token' => substr(hash('sha256', $token), 0, 16)]
			);
		} catch (Throwable $throttlerFailure) {
			$this->logger->warning(
				message: '[PublicVerificationController] registerAttempt failed',
				context: ['error' => $throttlerFailure->getMessage()]
			);
		}
	}//end registerUnknown()
}//end class
