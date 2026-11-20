<?php

/**
 * A throwaway organisation certificate generated at test runtime.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Waarmerk
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Waarmerk;

/**
 * No PEM or key fixture is committed: every test run makes its own pair.
 */
final class WaarmerkKeyPair {

	/**
	 * Pairs cached per organisation for the process, so the suite stays fast.
	 *
	 * @var array<string, array{cert: string, key: string}>
	 */
	private static array $cache = [];

	/**
	 * A self-signed certificate and its key.
	 *
	 * @param string $organisation The O of the subject.
	 *
	 * @return array{cert: string, key: string}
	 */
	public static function make(string $organisation = 'Gemeente Demostad'): array {
		if (isset(self::$cache[$organisation]) === true) {
			return self::$cache[$organisation];
		}

		$key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
		$csr = openssl_csr_new(['organizationName' => $organisation, 'countryName' => 'NL', 'commonName' => $organisation . ' waarmerk'], $key);
		$cert = openssl_csr_sign($csr, null, $key, 365);
		openssl_x509_export($cert, $certPem);
		openssl_pkey_export($key, $keyPem);
		while (openssl_error_string() !== false) {
			continue;
		}

		return self::$cache[$organisation] = ['cert' => $certPem, 'key' => $keyPem];

	}//end make()
}//end class
