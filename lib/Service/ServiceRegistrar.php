<?php

/**
 * Filinq Service Registrar
 *
 * The Service component's own container wiring: the Kernel contract
 * bindings, the PDF-conversion cascade and the seams that bind a Service
 * interface to its Nextcloud implementation. The AppInfo bootstrap calls this
 * one class instead of naming each service.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
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

namespace OCA\Filinq\Service;

use OCA\Filinq\Service\Conversion\PdfConversionRegistrar;
use OCA\Filinq\Service\Signing\LibreSignClient;
use OCA\Filinq\Service\Signing\OcsLibreSignClient;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

/**
 * Registers every Service-side binding.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Container wiring, no functional requirement.
 */
class ServiceRegistrar {
	/**
	 * Register the Service component's bindings.
	 *
	 * @param IRegistrationContext $context The registration context.
	 *
	 * @return void
	 *
	 * @spec exclude Container wiring, no functional requirement.
	 */
	public function register(IRegistrationContext $context): void {
		(new ContractBindings())->register(context: $context);
		(new PdfConversionRegistrar())->register(context: $context);

		// REQ-CDF-06: the one probe that talks to a real mount. Nextcloud
		// autowires concrete classes but not interfaces, so without this binding
		// anything asking for ExternalMountValidator fails to resolve -- and the
		// validator is read from the setup wizard, so that would be a 500 on the
		// first screen an administrator opens rather than a quiet gap.
		$context->registerServiceAlias(
			MountCapabilityProbe::class,
			NextcloudMountCapabilityProbe::class
		);

		// REQ-CDF-02: the nightly DomainFolderReconciliationJob needs this seam;
		// without the binding its constructor cannot be resolved and the
		// instance's whole cron pass dies on it.
		$context->registerServiceAlias(
			DomainFolderGateway::class,
			NextcloudDomainFolderGateway::class
		);

		// The LibreSign provider talks to LibreSign through this seam; the
		// OCS client loads no LibreSign class, so the binding is safe on an
		// instance without LibreSign (libresign-signing-provider task 1.3).
		$context->registerServiceAlias(LibreSignClient::class, OcsLibreSignClient::class);

	}//end register()
}//end class
