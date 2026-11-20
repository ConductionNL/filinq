<?php

/**
 * Waarmerk Key Resolver
 *
 * Resolves the organisation private key at seal time, from the credential
 * broker or the interim local custody, and fails closed.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Waarmerk
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

namespace OCA\Filinq\Service\Waarmerk;

use OCA\Filinq\Exception\WaarmerkRefusedException;
use OCA\Filinq\Service\SignerAuth\BrokerCredentialResolver;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * One resolver interface over both custody modes (design.md D3).
 *
 * The key is returned for immediate, in-memory use and is never logged:
 * failures log the custody mode and the exception class only.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Waarmerk
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-organisation-certificate-is-managed-in-admin-settings-with-credentialref-key-custody-req-ddwmk-001
 */
class WaarmerkKeyResolver {

	/**
	 * Constructor.
	 *
	 * @param WaarmerkSettings $settings Where the key is held.
	 * @param BrokerCredentialResolver $broker OpenRegister's credential broker.
	 * @param LoggerInterface $logger Logs which step failed, never the key.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly WaarmerkSettings $settings,
		private readonly BrokerCredentialResolver $broker,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * The private key that belongs to the certificate.
	 *
	 * @param WaarmerkCertificate $certificate The certificate the key must match.
	 *
	 * @return string The PEM key, for immediate use only.
	 *
	 * @throws WaarmerkRefusedException 503 when the key cannot be resolved or does not match.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-organisation-certificate-is-managed-in-admin-settings-with-credentialref-key-custody-req-ddwmk-001
	 */
	public function resolve(WaarmerkCertificate $certificate): string {
		$custody = $this->settings->custody();
		try {
			$key = $this->fetch(custody: $custody);
		} catch (Throwable $e) {
			$this->logger->warning(
				'Filinq: the waarmerk key could not be resolved; sealing is refused',
				['custody' => $custody, 'reason' => get_class($e), 'fingerprint' => $certificate->fingerprint]
			);
			$key = '';
		}

		if ($key === '' || @openssl_x509_check_private_key($certificate->pem, $key) !== true) {
			throw new WaarmerkRefusedException(
				message: 'Key resolution failed: the waarmerk key is not available, so nothing was sealed',
				status: 503
			);
		}

		return $key;

	}//end resolve()

	/**
	 * Read the key from the custody mode's store.
	 *
	 * @param string $custody The custody mode.
	 *
	 * @return string
	 */
	private function fetch(string $custody): string {
		if ($custody === WaarmerkSettings::CUSTODY_LOCAL) {
			return $this->settings->localKey();
		}

		return $this->broker->resolve(credentialRef: $this->settings->credentialRef());

	}//end fetch()
}//end class
