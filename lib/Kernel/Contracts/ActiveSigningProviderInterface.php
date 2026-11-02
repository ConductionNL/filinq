<?php

/**
 * Contract of the active signing provider lookup
 *
 * Names the signing provider an administrator configured, so callers in other
 * components need not depend on SigningProviderFactory.
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
 * Contract of the active signing provider lookup.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface ActiveSigningProviderInterface {
	/**
	 * Identifier of the configured signing provider.
	 *
	 * @return string The provider identifier, e.g. `native`.
	 *
	 * @throws \RuntimeException When LibreSign is configured but not enabled.
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#2-4
	 */
	public function getActiveProviderIdentifier(): string;
}//end interface
