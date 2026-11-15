<?php

/**
 * OpenRegister's AppHost Bootstrap::aliasStoreController and LeafAdoption, as
 * they stand after openregister #9078 (the call takes one LeafAdoption).
 *
 * Loaded by StoreControllerAliasTest only. The stub records every alias call
 * so a test can see the store binding happened: with the old signature the
 * call throws inside the registrar's catch and nothing is recorded.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\OpenRegister\AppHost
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\OpenRegister\AppHost;

use OCP\AppFramework\Bootstrap\IRegistrationContext;

/**
 * One leaf app's adoption request (mirrors lib/AppHost/LeafAdoption.php).
 */
final class LeafAdoption {
	/**
	 * Hold the app id and the overrides.
	 *
	 * @param string               $appId   Leaf app id.
	 * @param array<string, mixed> $options The overrides.
	 */
	public function __construct(
		public readonly string $appId,
		private readonly array $options = [],
	) {
	}//end __construct()

	/**
	 * A leaf namespace: its override, or the base namespace plus the suffix.
	 *
	 * @param string $option The override key.
	 * @param string $suffix The segment under the base namespace.
	 *
	 * @return string The namespace.
	 */
	public function namespace(string $option, string $suffix): string {
		$base = rtrim((string) ($this->options['namespace'] ?? ('OCA\\' . ucfirst($this->appId))), '\\');
		return (string) ($this->options[$option] ?? ($base . '\\' . $suffix));
	}//end namespace()
}//end class

/**
 * The AppHost one-call bootstrap (store alias only).
 */
class Bootstrap {
	/** @var array<int, LeafAdoption> Every adoption aliasStoreController() received. */
	public static array $aliased = [];

	/**
	 * Alias the generic store controller under the leaf's controller namespace.
	 *
	 * @param IRegistrationContext $context  Leaf registration context.
	 * @param LeafAdoption         $adoption Leaf app id and namespace.
	 *
	 * @return void
	 */
	public static function aliasStoreController(IRegistrationContext $context, LeafAdoption $adoption): void {
		self::$aliased[] = $adoption;
	}//end aliasStoreController()
}//end class
