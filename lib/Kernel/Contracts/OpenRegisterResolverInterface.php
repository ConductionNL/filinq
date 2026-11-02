<?php

/**
 * Contract of OpenRegisterResolver
 *
 * The public surface of the OpenRegisterResolver service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
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

namespace OCA\Filinq\Kernel\Contracts;

use Exception;
use OCA\Filinq\Exception\RegisterNotConfiguredException;

/**
 * Contract of OpenRegisterResolver.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface OpenRegisterResolverInterface {
	/**
	 * Get the template register and schema IDs from settings
	 *
	 * @return array{register: string, schema: string} Register and schema IDs
	 *
	 * @throws RegisterNotConfiguredException If template register/schema is not configured
	 *
	 * @spec openspec/specs/openregister-bridge/spec.md
	 */
	public function getRegisterAndSchema(): array;

	/**
	 * Get the template version register and schema IDs from settings
	 *
	 * @return array{register: string, schema: string} Register and schema IDs
	 *
	 * @throws RegisterNotConfiguredException If template version register/schema is not configured
	 *
	 * @spec openspec/specs/openregister-bridge/spec.md
	 */
	public function getVersionRegisterAndSchema(): array;

	/**
	 * Validate that a namespace string is a valid Nextcloud app ID
	 *
	 * @param string $namespace The namespace to validate
	 *
	 * @return bool True if valid
	 *
	 * @throws Exception If the namespace is invalid
	 *
	 * @spec openspec/specs/openregister-bridge/spec.md
	 */
	public function validateNamespace(string $namespace): bool;

	/**
	 * Resolve the financialExtraction register/schema binding, failing closed.
	 *
	 * SettingsService owns the READ and returns null when either half is unset;
	 * this turns that null into the same RegisterNotConfiguredException the
	 * template accessors above already raise. The split exists so the two
	 * concerns sit where they belong — the settings surface reads config, this
	 * resolver decides that an unset binding is an error — and it keeps the
	 * exception type out of SettingsService, whose object coupling is at its
	 * PHPMD ceiling.
	 *
	 * @return array{register: string, schema: string} The resolved binding.
	 *
	 * @throws RegisterNotConfiguredException If the binding is not configured.
	 *
	 * @spec openspec/specs/financial-document-field-extraction/spec.md
	 */
	public function getFinancialExtractionRegisterAndSchema(): array;

	/**
	 * Resolve the glAccountBooking register/schema binding, failing closed.
	 *
	 * @return array{register: string, schema: string} The resolved binding.
	 *
	 * @throws RegisterNotConfiguredException If the binding is not configured.
	 *
	 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
	 */
	public function getGlAccountBookingRegisterAndSchema(): array;

	/**
	 * Resolve the glAccountMappingRule register/schema binding, failing closed.
	 *
	 * @return array{register: string, schema: string} The resolved binding.
	 *
	 * @throws RegisterNotConfiguredException If the binding is not configured.
	 *
	 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
	 */
	public function getGlAccountMappingRuleRegisterAndSchema(): array;

	/**
	 * Resolve the signerRecord register/schema binding, failing closed.
	 *
	 * @return array{register: string, schema: string} The resolved binding.
	 *
	 * @throws RegisterNotConfiguredException If the binding is not configured.
	 *
	 * @spec openspec/specs/document-signing/spec.md
	 */
	public function getSignerRecordRegisterAndSchema(): array;
}//end interface
