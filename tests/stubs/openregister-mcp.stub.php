<?php

/**
 * PHPStan scan stub for OpenRegister's MCP contracts (ADR-063 chain 3/3).
 *
 * Analysis-only — referenced from phpstan.neon `scanFiles` and NEVER loaded at
 * runtime (the runtime classes live in the openregister sibling app, which is
 * co-installed on the Nextcloud instance but is not a composer dependency of
 * this app and is therefore absent from the static-analysis path).
 *
 * Why a stub rather than a suppression: PHPStan refuses to let
 * "implements unknown interface" be silenced through `ignoreErrors` and points
 * at `excludePaths` instead — which would stop analysing
 * `FilinqScannableServices` altogether. Declaring the real contracts here is
 * the truthful fix, and it additionally makes PHPStan check that the named
 * arguments every `#[McpTool]` declaration passes actually match the
 * attribute's constructor. A suppression would not.
 *
 * The signatures below mirror `openregister/lib/Mcp/IMcpScannableServices.php`
 * and `openregister/lib/Mcp/Attribute/McpTool.php` verbatim; keep them in sync
 * when the contract changes. An attribute is only ever resolved by reflection,
 * so a drift here is invisible until something reflects it.
 *
 * The parallel declarations in `tests/stubs/OpenRegisterStubs.php` serve the
 * PHPUnit runtime and are guarded by existence checks; this file is separate
 * precisely so it is never autoloaded alongside them.
 *
 * @category Test
 * @package  OCA\OpenRegister\Mcp
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\OpenRegister\Mcp;

/**
 * Per-app opt-in list of `#[McpTool]`-scannable classes.
 *
 * @category Test
 * @package  OCA\OpenRegister\Mcp
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
interface IMcpScannableServices {
	/**
	 * The app's own service classes eligible for `#[McpTool]` reflection.
	 *
	 * @return list<class-string> Fully-qualified service class names owned by this app.
	 */
	public function getScannableServiceClasses(): array;
}//end interface

namespace OCA\OpenRegister\Mcp\Attribute;

/**
 * Marks a public service method for MCP discovery.
 *
 * @category Test
 * @package  OCA\OpenRegister\Mcp\Attribute
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class McpTool {
	/**
	 * Constructor.
	 *
	 * @param string|null $name Local tool name; defaults to the method name when null.
	 * @param string|null $description LLM-facing description; defaults to the docblock summary when null.
	 * @param bool|null $readOnlyHint Optional MCP 2025-11-25 annotation hint.
	 * @param bool|null $destructiveHint Optional MCP 2025-11-25 annotation hint.
	 * @param bool|null $idempotentHint Optional MCP 2025-11-25 annotation hint.
	 * @param string|null $scope Optional advisory scope.
	 * @param string|null $subject The thing the tool acts on (grant-matrix taxonomy).
	 * @param string|null $action The verb it performs on that subject.
	 */
	public function __construct(
		public readonly ?string $name = null,
		public readonly ?string $description = null,
		public readonly ?bool $readOnlyHint = null,
		public readonly ?bool $destructiveHint = null,
		public readonly ?bool $idempotentHint = null,
		public readonly ?string $scope = null,
		public readonly ?string $subject = null,
		public readonly ?string $action = null,
	) {
	}//end __construct()
}//end class
