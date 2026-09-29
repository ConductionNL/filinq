<?php

/**
 * LibreSign over its OCS API
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Signing
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

namespace OCA\Filinq\Service\Signing;

use OCP\Http\Client\IClientService;
use OCP\IAppConfig;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Calls the LibreSign app on this Nextcloud over HTTP.
 *
 * The routes and payloads are LibreSign's, read from its `openapi.json`
 * (LibreSign/libresign, main at 16.0.0-dev, 29 Sep 2026): request-signature,
 * file/validate/uuid/{uuid}, sign/file_id/{fileId} (DELETE) and the page
 * route /p/pdf/{uuid}, which serves the signed copy once the file status is
 * signed. Nothing here loads a LibreSign class, so Filinq runs without
 * LibreSign installed.
 *
 * LibreSign acts for the user who calls it. Filinq calls it as a service
 * account: `libresign_service_uid` and `libresign_service_app_password` in
 * the app config (set the password with `occ config:app:set --sensitive`).
 * That account owns the requests LibreSign keeps; the signers are the
 * people named on the request.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/libresign-signing-provider/spec.md
 */
class OcsLibreSignClient implements LibreSignClient {

	/**
	 * LibreSign's OCS API root, version v1.
	 */
	private const OCS_ROOT = '/ocs/v2.php/apps/libresign/api/v1/';

	/**
	 * Seconds before a call to LibreSign gives up.
	 */
	private const TIMEOUT = 30;

	/**
	 * Constructor
	 *
	 * @param IClientService  $clientService The HTTP client factory
	 * @param IURLGenerator   $urlGenerator  This instance's own address
	 * @param IAppConfig      $config        The service account credentials
	 * @param LoggerInterface $logger        Logger
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IClientService $clientService,
		private readonly IURLGenerator $urlGenerator,
		private readonly IAppConfig $config,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * {@inheritDoc}
	 *
	 * @spec openspec/changes/archive/2026-09-29-libresign-signing-provider/tasks.md#task-1.3
	 */
	public function requestSignature(array $file, string $name, array $signers): array {
		// Status 1 (ABLE_TO_SIGN): LibreSign notifies the signers straight away.
		$body = ['file' => $file, 'name' => $name, 'signers' => $signers, 'status' => 1];

		return $this->ocs(verb: 'post', path: 'request-signature', body: $body);

	}//end requestSignature()

	/**
	 * {@inheritDoc}
	 *
	 * @spec openspec/changes/archive/2026-09-29-libresign-signing-provider/tasks.md#task-1.3
	 */
	public function validate(string $uuid): array {
		return $this->ocs(verb: 'get', path: 'file/validate/uuid/' . rawurlencode($uuid), body: null);

	}//end validate()

	/**
	 * {@inheritDoc}
	 *
	 * @spec openspec/changes/archive/2026-09-29-libresign-signing-provider/tasks.md#task-1.3
	 */
	public function downloadSigned(string $uuid): string {
		return $this->send(verb: 'get', url: '/index.php/apps/libresign/p/pdf/' . rawurlencode($uuid), body: null);

	}//end downloadSigned()

	/**
	 * {@inheritDoc}
	 *
	 * @spec openspec/changes/archive/2026-09-29-libresign-signing-provider/tasks.md#task-1.3
	 */
	public function deleteRequest(int $nodeId): void {
		$this->ocs(verb: 'delete', path: 'sign/file_id/' . $nodeId, body: null);

	}//end deleteRequest()

	/**
	 * One OCS call, unwrapped to `ocs.data`.
	 *
	 * @param string                    $verb get, post or delete
	 * @param string                    $path The route below the API root
	 * @param array<string, mixed>|null $body The JSON body, if any
	 *
	 * @return array<string, mixed> The `ocs.data` of the answer.
	 *
	 * @throws RuntimeException When the answer has no OCS data envelope.
	 */
	private function ocs(string $verb, string $path, ?array $body): array {
		$raw = $this->send(verb: $verb, url: self::OCS_ROOT . $path, body: $body);
		$decoded = json_decode($raw, true);
		if (is_array($decoded) === false || is_array($decoded['ocs']['data'] ?? null) === false) {
			throw new RuntimeException('LibreSign answered ' . $path . ' without OCS data');
		}

		return $decoded['ocs']['data'];

	}//end ocs()

	/**
	 * One HTTP call to this instance as the service account.
	 *
	 * @param string                    $verb get, post or delete
	 * @param string                    $url  The path on this instance
	 * @param array<string, mixed>|null $body The JSON body, if any
	 *
	 * @return string The response body.
	 *
	 * @throws RuntimeException When no service account is configured or the call fails.
	 */
	private function send(string $verb, string $url, ?array $body): string {
		$uid = $this->config->getValueString('filinq', 'libresign_service_uid', '');
		$password = $this->config->getValueString('filinq', 'libresign_service_app_password', '');
		if ($uid === '' || $password === '') {
			throw new RuntimeException(
				'LibreSign is selected but no service account is set: configure libresign_service_uid and libresign_service_app_password'
			);
		}

		$options = [
			'timeout' => self::TIMEOUT,
			'auth' => [$uid, $password],
			'headers' => ['OCS-APIRequest' => 'true', 'Accept' => 'application/json'],
			// LibreSign runs on this same instance, which is a local address.
			'nextcloud' => ['allow_local_address' => true],
		];
		if ($body !== null) {
			$options['json'] = $body;
		}

		$absolute = $this->urlGenerator->getAbsoluteURL($url);
		try {
			return (string) $this->clientService->newClient()->$verb($absolute, $options)->getBody();
		} catch (Throwable $e) {
			$this->logger->warning('LibreSign call failed: ' . $e->getMessage(), ['url' => $url]);
			throw new RuntimeException('LibreSign could not be reached: ' . $e->getMessage(), 0, $e);
		}

	}//end send()
}//end class
