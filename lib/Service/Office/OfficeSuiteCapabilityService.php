<?php

/**
 * Filinq OfficeSuiteCapabilityService
 *
 * Determines whether a WOPI host is actually usable on this instance, by asking
 * it rather than by asking whether an app is installed.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @category Service
 * @package  OCA\Filinq\Service\Office
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Office;

use OCP\Http\Client\IClientService;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Probes WOPI availability with a real CheckFileInfo.
 *
 * ADR-087 §3: availability is capability-probed per instance, never assumed, and
 * "the app is installed" is explicitly NOT the probe.
 *
 * That is not a theoretical caution. Measured against onlyoffice/documentserver on
 * 2026-08-16, with WOPI left at its shipped default:
 *
 *   container health .......... healthy
 *   GET /healthcheck .......... 200, body `true`
 *   GET / ..................... 302 (serving)
 *   GET /hosting/discovery .... 404
 *   GET /hosting/wopi/discovery 404
 *   GET /hosting/capabilities . 404
 *   default.json .............. "wopi": { "enable": false }
 *
 * Every check a person reaches for by instinct — is it up, does the port answer,
 * is the admin page green — returns YES in that state, and WOPI serves nothing.
 * Only asking WOPI a WOPI question separates the two.
 *
 * The probe therefore fails CLOSED. A wrong "absent" hides a feature that would
 * have worked; a wrong "available" ships a control that fails in a user's hands,
 * and the second is much the more expensive mistake.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Office
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://filinq.app
 *
 * @spec openspec/specs/office-suite-portability/spec.md
 */
class OfficeSuiteCapabilityService {

	/**
	 * Fields a CheckFileInfo response must carry to be usable.
	 *
	 * WOPI defines both as required. A 2xx body lacking either cannot support a
	 * session, so treating the status alone as success would report available for
	 * a host that cannot serve one.
	 *
	 * @var string[]
	 */
	private const REQUIRED_FIELDS = ['BaseFileName', 'Size'];

	/**
	 * Probe timeout in seconds.
	 *
	 * Bounded because the probe can run inside a user-facing request. An
	 * unreachable host must cost a bounded wait and then resolve absent, never
	 * hang the response.
	 *
	 * @var int
	 */
	private const TIMEOUT_SECONDS = 5;

	/**
	 * Constructor.
	 *
	 * @param IClientService  $clientService The HTTP client factory.
	 * @param LoggerInterface $logger        The logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IClientService $clientService,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * Probe a WOPI host's DISCOVERY endpoint.
	 *
	 * ADR-087 §3 says "a successful `CheckFileInfo` is the probe". That is not
	 * achievable at capability-resolution time and the ADR was wrong to require it:
	 * `CheckFileInfo` is a PER-FILE call authenticated by a short-lived access token
	 * the host mints when a user opens a specific document. There is no file and no
	 * token when a capability is being resolved.
	 *
	 * WOPI **discovery** is the endpoint that answers "is there a usable WOPI host
	 * here" without a file: it returns XML listing the actions the host supports. A
	 * suite with WOPI switched off does not serve it — measured on ONLYOFFICE
	 * 2026-08-16, `/hosting/discovery` returned 404 until `WOPI_ENABLED=true`, so
	 * discovery separates "installed" from "usable" exactly as intended.
	 *
	 * This method was originally written to validate a `CheckFileInfo` JSON body and
	 * was then pointed at a discovery URL, so it reported every working suite as
	 * absent for "missing the required field BaseFileName" — a probe failing on the
	 * shape of a response it was never given.
	 *
	 * @param string $discoveryUrl Absolute WOPI discovery URL to probe.
	 *
	 * @return array{available:bool, reason:string, suite:string|null} The verdict.
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md
	 */
	public function probeDiscovery(string $discoveryUrl): array {
		if (trim($discoveryUrl) === '') {
			return $this->absent(reason: 'no WOPI endpoint configured');
		}

		try {
			$response = $this->clientService->newClient()->get(
				$discoveryUrl,
				[
					'timeout'         => self::TIMEOUT_SECONDS,
					'connect_timeout' => self::TIMEOUT_SECONDS,
					'allow_redirects' => false,
					'http_errors'     => false,
				]
			);
		} catch (Throwable $e) {
			return $this->absent(reason: 'probe could not complete: ' . $e->getMessage());
		}

		$status = $response->getStatusCode();
		if ($status < 200 || $status >= 300) {
			return $this->absent(reason: sprintf('discovery returned %d', $status));
		}

		$body = (string)$response->getBody();

		// A WOPI discovery document declares `wopi-discovery` and at least one
		// `action`. A 200 carrying an error page or a login form has neither, and
		// accepting the status alone would report those hosts as usable.
		if (str_contains($body, 'wopi-discovery') === false) {
			return $this->absent(reason: 'response is not a WOPI discovery document');
		}

		if (str_contains($body, '<action') === false) {
			return $this->absent(reason: 'WOPI discovery declares no actions');
		}

		return [
			'available' => true,
			'reason'    => 'WOPI discovery served',
			'suite'     => $this->identifyFromDiscovery(body: $body),
		];
	}//end probeDiscovery()

	/**
	 * Name the suite from its discovery document, when it says.
	 *
	 * Reporting only. Nothing branches on which suite answered — a capability that
	 * behaved differently per suite would be the per-suite driver set ADR-087 §5
	 * bans.
	 *
	 * @param string $body The discovery XML.
	 *
	 * @return string|null The suite name, or null.
	 */
	private function identifyFromDiscovery(string $body): ?string {
		if (preg_match('/<app\s+name="([^"]+)"/', $body, $m) === 1) {
			return $m[1];
		}

		return null;
	}//end identifyFromDiscovery()

	/**
	 * Probe a WOPI host's CheckFileInfo for a specific file.
	 *
	 * Only usable where a real file and access token exist — i.e. inside a session,
	 * not at capability-resolution time. See {@see probeDiscovery()}.
	 *
	 * @param string $checkFileInfoUrl Absolute CheckFileInfo URL to probe.
	 *
	 * @return array{available:bool, reason:string, suite:string|null} The verdict.
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md
	 */
	public function probe(string $checkFileInfoUrl): array {
		if (trim($checkFileInfoUrl) === '') {
			return $this->absent(reason: 'no WOPI endpoint configured');
		}

		try {
			$response = $this->clientService->newClient()->get(
				$checkFileInfoUrl,
				[
					'timeout'         => self::TIMEOUT_SECONDS,
					'connect_timeout' => self::TIMEOUT_SECONDS,
					// A redirect to a login page is not a CheckFileInfo response.
					'allow_redirects' => false,
					// Handle the status ourselves so a 404 is a verdict, not an
					// exception indistinguishable from a network failure.
					'http_errors'     => false,
				]
			);
		} catch (Throwable $e) {
			// Refused, DNS failure, TLS failure, timeout. All absent.
			return $this->absent(reason: 'probe could not complete: ' . $e->getMessage());
		}

		$status = $response->getStatusCode();
		if ($status < 200 || $status >= 300) {
			// The measured Euro-Office default lands exactly here, with 404.
			return $this->absent(reason: sprintf('CheckFileInfo returned %d', $status));
		}

		$decoded = json_decode((string)$response->getBody(), true);
		if (is_array($decoded) === false) {
			// A 200 can be an error page or a login redirect body.
			return $this->absent(reason: 'CheckFileInfo returned a non-JSON body');
		}

		foreach (self::REQUIRED_FIELDS as $field) {
			if (array_key_exists($field, $decoded) === false) {
				return $this->absent(
					reason: sprintf('CheckFileInfo response is missing the required field "%s"', $field)
				);
			}
		}

		return [
			'available' => true,
			'reason'    => 'CheckFileInfo succeeded',
			'suite'     => $this->identifySuite(payload: $decoded),
		];
	}//end probe()

	/**
	 * Build an absent verdict and record why.
	 *
	 * The reason is retained rather than collapsed to a boolean because "no suite
	 * installed" and "suite installed with WOPI disabled" need different actions
	 * from an operator, and the second is invisible from every other angle.
	 *
	 * @param string $reason Why the probe resolved absent.
	 *
	 * @return array{available:bool, reason:string, suite:string|null} The verdict.
	 */
	private function absent(string $reason): array {
		$this->logger->debug(
			'[Filinq] WOPI capability resolved absent: ' . $reason,
			['app' => 'filinq']
		);

		return [
			'available' => false,
			'reason'    => $reason,
			'suite'     => null,
		];
	}//end absent()

	/**
	 * Name the responding suite when it identifies itself.
	 *
	 * Reporting only. Nothing in Filinq branches on which suite answered — a
	 * capability that behaved differently per suite would be the per-suite driver
	 * set ADR-087 §5 bans.
	 *
	 * @param array $payload The decoded CheckFileInfo response.
	 *
	 * @return string|null The suite name, or null when it did not say.
	 */
	private function identifySuite(array $payload): ?string {
		$claimed = ($payload['BreadcrumbBrandName'] ?? ($payload['HostEditUrl'] ?? null));
		if (is_string($claimed) === false || $claimed === '') {
			return null;
		}

		return $claimed;
	}//end identifySuite()
}//end class
