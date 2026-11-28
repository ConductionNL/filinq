<?php

/**
 * Waarmerk Settings
 *
 * Admin configuration of the organisation certificate and the reference to
 * its private key.
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
use OCA\Filinq\Kernel\Contracts\WaarmerkSettingsInterface;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IAppConfig;
use OCP\Security\ICrypto;

/**
 * The organisation certificate (public) and where its key is held.
 *
 * ADR-064: the app config holds the public certificate and a `credentialRef`.
 * The interim local custody mode stores the key encrypted with Nextcloud's
 * ICrypto, as a sensitive value, and the panel labels it as local custody.
 * No read method of this class ever returns key material.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Waarmerk
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-organisation-certificate-is-managed-in-admin-settings-with-credentialref-key-custody-req-ddwmk-001
 */
class WaarmerkSettings implements WaarmerkSettingsInterface {

	public const APP = 'filinq';

	public const KEY_CERTIFICATE = 'waarmerk_certificate';

	public const KEY_CREDENTIAL_REF = 'waarmerk_credential_ref';

	public const KEY_CUSTODY = 'waarmerk_custody';

	public const KEY_LOCAL_KEY = 'waarmerk_local_key';

	public const CUSTODY_BROKER = 'broker';

	public const CUSTODY_LOCAL = 'local';

	/**
	 * Constructor.
	 *
	 * @param IAppConfig $config The app config.
	 * @param ICrypto $crypto Encrypts the interim local key at rest.
	 * @param ITimeFactory $time The clock.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IAppConfig $config,
		private readonly ICrypto $crypto,
		private readonly ITimeFactory $time,
	) {

	}//end __construct()

	/**
	 * The configured certificate, or null when none is set.
	 *
	 * @return WaarmerkCertificate|null
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess) WaarmerkCertificate is a stateless helper or named
	 * constructor; an instance would add nothing.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-organisation-certificate-is-managed-in-admin-settings-with-credentialref-key-custody-req-ddwmk-001
	 */
	public function certificate(): ?WaarmerkCertificate {
		$pem = $this->config->getValueString(self::APP, self::KEY_CERTIFICATE, '');
		if ($pem === '') {
			return null;
		}

		return WaarmerkCertificate::fromPem(pem: $pem);

	}//end certificate()

	/**
	 * The custody mode: broker or local.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
	 */
	public function custody(): string {
		if ($this->config->getValueString(self::APP, self::KEY_CUSTODY, self::CUSTODY_BROKER) === self::CUSTODY_LOCAL) {
			return self::CUSTODY_LOCAL;
		}

		return self::CUSTODY_BROKER;

	}//end custody()

	/**
	 * The broker credential reference of the private key.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
	 */
	public function credentialRef(): string {
		return $this->config->getValueString(self::APP, self::KEY_CREDENTIAL_REF, '');

	}//end credentialRef()

	/**
	 * The interim local key, decrypted, for the resolver only.
	 *
	 * @return string The PEM key, or '' when none is held locally.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
	 */
	public function localKey(): string {
		$stored = $this->config->getValueString(self::APP, self::KEY_LOCAL_KEY, '');
		if ($stored === '') {
			return '';
		}

		return $this->crypto->decrypt($stored);

	}//end localKey()

	/**
	 * What the admin panel shows: public facts and custody, never the key.
	 *
	 * @return array<string, mixed>
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-organisation-certificate-is-managed-in-admin-settings-with-credentialref-key-custody-req-ddwmk-001
	 */
	public function summary(): array {
		$certificate = null;
		$error = null;
		try {
			$certificate = $this->certificate();
		} catch (WaarmerkRefusedException $e) {
			$error = $e->getMessage();
		}

		$summary = null;
		if ($certificate !== null) {
			$summary = $certificate->summary(now: $this->time->getDateTime());
		}

		return [
			'configured' => $certificate !== null,
			'certificate' => $summary,
			'certificateError' => $error,
			'credentialRef' => $this->credentialRef(),
			'custody' => $this->custody(),
			'localKeyStored' => $this->config->getValueString(self::APP, self::KEY_LOCAL_KEY, '') !== '',
			'qualified' => false,
		];

	}//end summary()

	/**
	 * Save the certificate, the credential reference and the custody mode.
	 *
	 * A local key, when given, must belong to the certificate; it is stored
	 * encrypted and sensitive. Switching to broker custody removes it.
	 *
	 * @param array<string, mixed> $input certificate, credentialRef, custody, localKey.
	 *
	 * @return array<string, mixed> The new summary.
	 *
	 * @throws WaarmerkRefusedException When the input is invalid.
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess) WaarmerkCertificate is a stateless helper or named
	 * constructor; an instance would add nothing.
	 * @SuppressWarnings(PHPMD.ErrorControlOperator) OpenSSL reports failure through the return value,
	 * which is checked; the PHP warning it would also raise adds nothing, and a temp-file unlink is
	 * best-effort cleanup.
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity) Each branch is an independent check of the saved
	 * settings that refuses with a 400.
	 * @SuppressWarnings(PHPMD.NPathComplexity) Each branch is an independent check of the saved
	 * settings that refuses with a 400.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-organisation-certificate-is-managed-in-admin-settings-with-credentialref-key-custody-req-ddwmk-001
	 */
	public function save(array $input): array {
		$custody = (string) ($input['custody'] ?? self::CUSTODY_BROKER);
		if (in_array($custody, [self::CUSTODY_BROKER, self::CUSTODY_LOCAL], true) === false) {
			throw new WaarmerkRefusedException(message: 'Custody must be broker or local', status: 400);
		}

		$pem = trim((string) ($input['certificate'] ?? ''));
		$certificate = $this->certificate();
		if ($pem !== '') {
			$certificate = WaarmerkCertificate::fromPem(pem: $pem);
		}

		$credentialRef = trim((string) ($input['credentialRef'] ?? $this->credentialRef()));
		if (str_contains($credentialRef, 'PRIVATE KEY') === true) {
			throw new WaarmerkRefusedException(message: 'The credential reference must be a reference, not a key', status: 400);
		}

		$localKey = trim((string) ($input['localKey'] ?? ''));
		if ($localKey !== '') {
			if ($custody !== self::CUSTODY_LOCAL || $certificate === null) {
				throw new WaarmerkRefusedException(message: 'A local key needs local custody and a certificate', status: 400);
			}

			if (@openssl_x509_check_private_key($certificate->pem, $localKey) !== true) {
				throw new WaarmerkRefusedException(message: 'The key does not belong to the certificate', status: 400);
			}
		}

		if ($certificate !== null) {
			$this->config->setValueString(self::APP, self::KEY_CERTIFICATE, $certificate->pem);
		}

		$this->config->setValueString(self::APP, self::KEY_CREDENTIAL_REF, $credentialRef);
		$this->config->setValueString(self::APP, self::KEY_CUSTODY, $custody);
		if ($localKey !== '') {
			$this->config->setValueString(self::APP, self::KEY_LOCAL_KEY, $this->crypto->encrypt($localKey), sensitive: true);
		}

		if ($custody === self::CUSTODY_BROKER) {
			$this->config->deleteKey(self::APP, self::KEY_LOCAL_KEY);
		}

		return $this->summary();

	}//end save()
}//end class
