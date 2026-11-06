<?php

/**
 * Contract of PolicyRetroactiveService
 *
 * The public surface of the PolicyRetroactiveService service, so callers in other
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

/**
 * Contract of PolicyRetroactiveService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface PolicyRetroactiveServiceInterface {
	/**
	 * Apply a prohibition mutation retroactively to in-flight workflow records.
	 *
	 * Call this after a `publicationProhibition` has been created OR updated to
	 * `active: true` OR had its `matchRules` widened OR had `validUntil` extended
	 * — the caller decides which updates qualify; this method is the worker.
	 *
	 * @param array<string, mixed> $prohibition The prohibition record's plain data.
	 *
	 * @return int Number of in-flight records that were force-resolved.
	 */
	public function applyProhibitionMutation(array $prohibition): int;

	/**
	 * Standing-consent mutations are intentionally NOT applied retroactively.
	 *
	 * Future detections benefit; past detections retain their already-decided
	 * outcomes (spec §5.2). The only effect of this method is cache invalidation.
	 *
	 * @return void
	 */
	public function applyStandingConsentMutation(): void;

	/**
	 * Rule deletions / deactivations / expiries do NOT modify past records.
	 *
	 * Past `publicationConsent` records keep their final state and their
	 * `policyMatch` reference (which becomes dangling). Spec §5.3. Cache is
	 * invalidated so future detections see the rule absence.
	 *
	 * @return void
	 */
	public function applyRuleRemoval(): void;
}//end interface
