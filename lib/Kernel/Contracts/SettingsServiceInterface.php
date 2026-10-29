<?php

/**
 * Contract of SettingsService
 *
 * The public surface of the SettingsService service, so callers in other
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

use OCA\OpenRegister\Service\ObjectService;
use RuntimeException;

/**
 * Contract of SettingsService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface SettingsServiceInterface {
	/**
	 * Attempts to retrieve the OpenRegister service from the container
	 *
	 * @return \OCA\OpenRegister\Service\ObjectService|null The OpenRegister service
	 *
	 * @throws \RuntimeException If the service is not available
	 *
	 * @spec openspec/specs/admin-settings/spec.md
	 */
	public function getObjectService(): ?ObjectService;

	/**
	 * Initializes the app with all required components
	 *
	 * @return array<string, mixed> The initialization results
	 *
	 * @throws \RuntimeException If initialization fails
	 *
	 * @spec openspec/specs/admin-settings/spec.md
	 */
	public function initialize(): array;

	/**
	 * The feature toggles alone, without the rest of the settings payload.
	 *
	 * {@see getAllSettings()} assembles what the ADMIN SETTINGS PAGE needs:
	 * every available register with its schemas, the object-type
	 * configuration, OCR status and the grondslag selector data. That is
	 * correct for a settings screen and catastrophic on a write path — the
	 * register/schema discovery alone issued 1,471 `SchemaMapper::find()`
	 * calls (one per schema on the instance) when it was reached from
	 * {@see \OCA\Filinq\EventListener\EnrichmentRunner}, which runs inside
	 * an unrelated app's object save. Measured 2026-07-29: 96% of ALL schema
	 * reads during an OpenRegister object create originated there, and the
	 * create took 9-17s.
	 *
	 * Anything that only needs a toggle MUST call this instead. These are
	 * plain IAppConfig reads and touch no register or schema.
	 *
	 * @return array<string, mixed> Feature toggle settings.
	 *
	 * @spec openspec/specs/admin-settings/spec.md
	 */
	public function getFeatureToggles(): array;

	/**
	 * Get Tesseract OCR availability status
	 *
	 * @return array{tesseractAvailable: bool, tesseractVersion: string|null} OCR status
	 *
	 * @spec openspec/changes/ocr-document-scanning/tasks.md#task-4.2
	 */
	public function getOcrStatus(): array;

	/**
	 * Retrieve all settings
	 *
	 * @return array<string, mixed> The current settings configuration
	 *
	 * @throws RuntimeException If settings retrieval fails
	 *
	 * @spec openspec/specs/admin-settings/spec.md
	 * @spec openspec/changes/ocr-document-scanning/tasks.md#task-4.3
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-3.2
	 */
	public function getAllSettings(): array;

	/**
	 * Update the settings configuration
	 *
	 * Only keys present in WRITABLE_KEYS may be written; all other keys are
	 * silently skipped.  This prevents escalation via security-sensitive keys
	 * such as signing_verification_secret (wave-3 C1).
	 *
	 * @param array<string, mixed> $data The settings data to update
	 *
	 * @return array<string, mixed> The updated settings configuration
	 *
	 * @throws \RuntimeException If settings update fails
	 * @throws RuntimeException With code 400 when a value is refused; nothing is written then.
	 *
	 * @spec openspec/specs/admin-settings/spec.md
	 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-2
	 */
	public function updateSettings(array $data): array;

	/**
	 * Resolve the signingRequest register/schema binding, or null when unset.
	 *
	 * These bindings live here, next to the settings surface that writes them,
	 * rather than in each consumer: every call site used to read them inline
	 * with an empty-string default and pass the result straight into
	 * saveObject()/find(). Unconfigured, that wrote signing requests — the
	 * audit trail behind an eIDAS-level signature — into register '' and schema
	 * '', silently. Mirrors OpenRegisterResolver::getRegisterAndSchema(), which
	 * already does exactly this for templates.
	 *
	 * @return array{register: string, schema: string}|null The binding, or null when unset.
	 *
	 * @spec openspec/specs/document-signing/spec.md
	 */
	public function resolveSigningRequestBinding(): ?array;

	/**
	 * Resolve the signerRecord register/schema binding, or null when unset.
	 *
	 * A signer record carries the identity a signature is attributed to, so an
	 * unconfigured binding loses exactly the evidence a signature exists to
	 * provide.
	 *
	 * @return array{register: string, schema: string}|null The binding, or null when unset.
	 *
	 * @spec openspec/specs/document-signing/spec.md
	 */
	public function resolveSignerRecordBinding(): ?array;

	/**
	 * Resolve the financialExtraction register/schema binding, or null when unset.
	 *
	 * @return array{register: string, schema: string}|null The binding, or null when unset.
	 *
	 * @spec openspec/specs/financial-document-field-extraction/spec.md
	 */
	public function resolveFinancialExtractionBinding(): ?array;

	/**
	 * Resolve the glAccountBooking register/schema binding, or null when unset.
	 *
	 * @return array{register: string, schema: string}|null The binding, or null when unset.
	 *
	 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
	 */
	public function resolveGlAccountBookingBinding(): ?array;

	/**
	 * Resolve the glAccountMappingRule register/schema binding, or null when unset.
	 *
	 * @return array{register: string, schema: string}|null The binding, or null when unset.
	 *
	 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
	 */
	public function resolveGlAccountMappingRuleBinding(): ?array;
}//end interface
