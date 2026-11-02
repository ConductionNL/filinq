<?php

/**
 * Default signer acr mapping
 *
 * The documented default mapping from an identity broker's acr value to the
 * means and assurance level it stands for, as a dependency-free constant so
 * the settings screen and the OIDC broker provider read the same table.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel
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

namespace OCA\Filinq\Kernel;

/**
 * The default acr mapping (design D2).
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural constant, no functional requirement.
 */
final class SignerAcrMapping {
	/**
	 * The documented default acr mapping.
	 *
	 * DigiD's levels are the AuthnContextClassRefs Logius publishes (Basis,
	 * Midden, Substantieel, Hoog); eHerkenning's are the eToegang assurance
	 * classes. iDIN has no standard acr: the key below is a placeholder, and
	 * an admin maps their broker's own iDIN value in the settings.
	 *
	 * @var array<string, array{means: string, assurance: string}>
	 */
	public const DEFAULT = [
		'urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport' => ['means' => 'digid', 'assurance' => 'low'],
		'urn:oasis:names:tc:SAML:2.0:ac:classes:MobileTwoFactorContract' => ['means' => 'digid', 'assurance' => 'substantial'],
		'urn:oasis:names:tc:SAML:2.0:ac:classes:Smartcard' => ['means' => 'digid', 'assurance' => 'substantial'],
		'urn:oasis:names:tc:SAML:2.0:ac:classes:SmartcardPKI' => ['means' => 'digid', 'assurance' => 'high'],
		'urn:etoegang:core:assurance-class:loa2plus' => ['means' => 'eherkenning', 'assurance' => 'low'],
		'urn:etoegang:core:assurance-class:loa3' => ['means' => 'eherkenning', 'assurance' => 'substantial'],
		'urn:etoegang:core:assurance-class:loa4' => ['means' => 'eherkenning', 'assurance' => 'high'],
		'urn:nl:idin:bank-verified' => ['means' => 'idin', 'assurance' => 'substantial'],
	];
}//end class
