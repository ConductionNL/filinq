<?php

/**
 * Endpoint Locality
 *
 * Decides whether an endpoint stays inside the organisation's network.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Detection
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/llm-entity-detection-provider/specs/llm-entity-detection-provider/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Detection;

/**
 * Whether a URL's host is a loopback or private-network address.
 *
 * An IP literal is judged directly. A name is resolved, and is local only when
 * it resolves and every address it resolves to is loopback or private; a name
 * that does not resolve is not local. This is what keeps document text inside
 * the perimeter when `require_local` is on.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Detection
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/llm-entity-detection-provider/specs/llm-entity-detection-provider/spec.md
 */
class EndpointLocality {

	/**
	 * Resolve a host name to its IPv4 addresses. A seam for tests.
	 *
	 * @param string $host The host name.
	 *
	 * @return list<string> The addresses, empty when it does not resolve.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-2.1
	 */
	protected function resolve(string $host): array {
		$addresses = gethostbynamel($host);
		if ($addresses === false) {
			return [];
		}

		return $addresses;
	}//end resolve()

	/**
	 * Whether the URL's host is loopback or private.
	 *
	 * @param string $url The endpoint URL.
	 *
	 * @return bool True when every address of the host is loopback or private.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-2.1
	 */
	public function isLocal(string $url): bool {
		$host = strtolower(trim((string) parse_url($url, PHP_URL_HOST), '[]'));
		if ($host === '') {
			return false;
		}

		if ($host === 'localhost') {
			return true;
		}

		if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
			return $this->isLocalAddress(address: $host);
		}

		return $this->allLocal(addresses: $this->resolve(host: $host));
	}//end isLocal()

	/**
	 * Whether a host's addresses are all loopback or private; a host that resolves to none is not local.
	 *
	 * @param array<int, string> $addresses The resolved addresses.
	 *
	 * @return bool
	 */
	private function allLocal(array $addresses): bool {
		if ($addresses === []) {
			return false;
		}

		foreach ($addresses as $address) {
			if ($this->isLocalAddress(address: $address) === false) {
				return false;
			}
		}

		return true;
	}//end allLocal()

	/**
	 * Whether one IP address is loopback or private.
	 *
	 * @param string $address The address.
	 *
	 * @return bool True for a loopback or private-range address.
	 */
	private function isLocalAddress(string $address): bool {
		if (filter_var($address, FILTER_VALIDATE_IP) === false) {
			return false;
		}

		if ($address === '::1' || str_starts_with($address, '127.') === true) {
			return true;
		}

		// A valid address that fails the "not private" filter is private.
		return filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) === false;
	}//end isLocalAddress()
}//end class
