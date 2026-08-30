<?php

/**
 * Filinq Migrate App Config Keys Repair Step
 *
 * Repair step that carries this app's stored `IAppConfig` values across the
 * `docudesk` -> `filinq` app-id rename.
 *
 * Nextcloud namespaces `IAppConfig` by app id at the storage layer
 * (`oc_appconfig.appid`), so renaming `<id>` does not rename the rows — it
 * makes every previously stored value unreachable, because the app now asks
 * for them under a different app id. There is no in-place app-id upgrade in
 * Nextcloud: the new id is simply a different app. This step therefore copies
 * each value from the old namespace to the new one.
 *
 * WHY EVERY KEY RATHER THAN A FIXED LIST. `SettingsService` reads a large but
 * not exhaustive set of admin keys, and that is not the whole stored set —
 * `SettingsInitializer` records the imported register/schema ids and the
 * `configuration_version` it gated the import on, and past releases have
 * written keys this app no longer reads. Enumerating `IAppConfig::getKeys()`
 * is exhaustive by construction and cannot drift out of date the way a
 * hardcoded list does.
 *
 * WHY THE KEY NAME IS REWRITTEN, UNLIKE THE PLANNINQ PILOT. This app prefixes
 * roughly fifty of its own keys with its app id (`docudesk.pdfa3.enabled`,
 * `docudesk_batch_max_files`, …), so the rename touches the key NAMES as well
 * as the namespace they live in. A straight key-for-key copy would land the
 * old names in the new namespace and every one of those reads would still miss.
 * `newKeyFor()` therefore rewrites the leading `docudesk.` / `docudesk_` to
 * `filinq.` / `filinq_` while copying, and leaves every unprefixed key
 * (`ocr_enabled`, `signing_request_expiry_days`, `configuration_version`, …)
 * exactly as it is. The rewrite is a PREFIX rewrite, not a substring one: a
 * key that merely contains the old id somewhere in the middle is not a name
 * this app ever generated, and rewriting it would invent a key nothing reads.
 *
 * SAFETY. Idempotent and non-destructive:
 *   - a key is copied only when the old value is non-empty AND the new
 *     namespace does not already hold a value under the NEW key name, so an
 *     admin edit made after the rename is never clobbered and a second run is
 *     a no-op;
 *   - the old `docudesk` rows are never deleted, so a rollback to the previous
 *     app id still finds its configuration intact;
 *   - values round-trip as raw strings. `IAppConfig` stores every value as a
 *     string and the typed accessors only coerce on read, so a string
 *     round-trip cannot lose or corrupt a value written by a typed setter;
 *   - every failure is logged and the loop continues. A repair step that
 *     throws aborts the install, and a config value that could not be copied
 *     is not worth failing an install over — the app falls back to its
 *     defaults and the admin can re-enter the setting.
 *
 * Registered under BOTH `<install>` and `<post-migration>` in
 * `appinfo/info.xml` — see the ordering comment there.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category Repair
 * @package  OCA\Filinq\Repair
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://conduction.nl
 *
 * @spec exclude No canonical spec covers the docudesk -> filinq app-id rename;
 *  pointing this at an existing spec would report conformance to a requirement
 *  that says nothing about it.
 */

declare(strict_types=1);

namespace OCA\Filinq\Repair;

use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Psr\Log\LoggerInterface;

/**
 * Copy every stored IAppConfig value from the docudesk namespace to filinq.
 *
 * @spec exclude No canonical spec covers the docudesk -> filinq app-id rename.
 */
class MigrateAppConfigKeys implements IRepairStep {
	/**
	 * The app-config namespace this app used before the rename.
	 *
	 * Deliberately the OLD app id. This constant is one of the few places in
	 * the app that is supposed to still say `docudesk`.
	 *
	 * @var string
	 */
	private const OLD_APP_ID = 'docudesk';

	/**
	 * The app-config namespace this app uses after the rename.
	 *
	 * @var string
	 */
	private const NEW_APP_ID = 'filinq';

	/**
	 * Old key-name prefix => new key-name prefix.
	 *
	 * Both spellings this app has used are listed: dot-separated keys
	 * (`docudesk.pdfa3.enabled`) and underscore-separated ones
	 * (`docudesk_batch_max_files`). Omitting either would strand that half of
	 * the settings surface.
	 *
	 * @var array<string, string>
	 */
	private const KEY_PREFIX_MAP = [
		'docudesk.' => 'filinq.',
		'docudesk_' => 'filinq_',
	];

	/**
	 * Config keys Nextcloud owns for every app. These MUST NOT be copied.
	 *
	 * `AppManager::enableApp()` writes `enabled` through the deprecated
	 * `IAppConfig::setValue()`, which stores type MIXED. Copying it here with
	 * `setValueString()` stores type STRING, and the next `app:enable` then
	 * fails with an `AppConfigTypeConflictException` — permanently, because the
	 * conflict is hit before the app can run anything that would repair it.
	 * `installed_version` and `types` are Nextcloud's own bookkeeping for the
	 * app and copying the old app's values would misreport the new one.
	 *
	 * @var string[]
	 */
	private const RESERVED_KEYS = [
		'enabled',
		'installed_version',
		'types',
	];

	/**
	 * Constructor for MigrateAppConfigKeys.
	 *
	 * @param IAppConfig $appConfig The app config interface.
	 * @param LoggerInterface $logger The logger interface.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * Get the name of this repair step.
	 *
	 * @return string
	 *
	 * @spec exclude No canonical spec covers the docudesk -> filinq app-id rename.
	 */
	public function getName(): string {
		return 'Copy Filinq app configuration from the docudesk namespace to filinq';
	}//end getName()

	/**
	 * Run the repair step to migrate the stored app configuration.
	 *
	 * @param IOutput $output The output interface for progress reporting.
	 *
	 * @return void
	 *
	 * @spec exclude No canonical spec covers the docudesk -> filinq app-id rename.
	 */
	public function run(IOutput $output): void {
		$keys = $this->oldKeys();
		if ($keys === []) {
			$output->info(
				'MigrateAppConfigKeys: no stored docudesk configuration on this install; nothing to do.'
			);
			return;
		}

		$migrated = 0;
		$alreadyPresent = 0;
		$emptySource = 0;
		$skippedReserved = 0;

		foreach ($keys as $key) {
			if (in_array($key, self::RESERVED_KEYS, strict: true) === true) {
				$skippedReserved++;
				continue;
			}

			/* The two READS belong inside the try as much as the write does.
			   They used to sit outside it, so a read that threw propagated
			   out of run() and aborted `occ upgrade` — the exact outcome the
			   class docblock promises cannot happen ("every failure is
			   logged and the loop continues"). One unreadable key is not
			   worth failing an install over; the app falls back to its
			   defaults and the admin re-enters that setting. */
			/* Pre-set so the catch below can always name it: the first read
			   can now throw, before newKeyFor() has run. */
			$newKey = $key;

			try {
				$old = $this->appConfig->getValueString(self::OLD_APP_ID, $key, '');
				if ($old === '') {
					$emptySource++;
					continue;
				}

				$newKey = $this->newKeyFor(oldKey: $key);

				$existing = $this->appConfig->getValueString(self::NEW_APP_ID, $newKey, '');
				if ($existing !== '') {
					$alreadyPresent++;
					continue;
				}

				$this->appConfig->setValueString(self::NEW_APP_ID, $newKey, $old);
				$migrated++;
			} catch (\Throwable $e) {
				$this->logger->warning(
					'Filinq: could not migrate one app config key; leaving it under the old namespace',
					['key' => $key, 'newKey' => $newKey, 'exception' => $e->getMessage()]
				);
			}//end try
		}//end foreach

		$output->info(
			'MigrateAppConfigKeys: ' . $migrated . ' key(s) migrated, ' . $alreadyPresent
			. ' already present, ' . $emptySource . ' had no value to migrate, '
			. $skippedReserved . ' skipped as Nextcloud-reserved.'
		);
	}//end run()

	/**
	 * Translate one stored key name into the name the renamed app reads.
	 *
	 * Only a LEADING old-app-id prefix is rewritten; every other key is
	 * returned unchanged.
	 *
	 * @param string $oldKey The key as stored under the old app id.
	 *
	 * @return string The key name to write under the new app id.
	 */
	private function newKeyFor(string $oldKey): string {
		foreach (self::KEY_PREFIX_MAP as $from => $to) {
			if (str_starts_with($oldKey, $from) === true) {
				return $to . substr($oldKey, strlen($from));
			}
		}

		return $oldKey;
	}//end newKeyFor()

	/**
	 * Every key currently stored under the old app-config namespace.
	 *
	 * @return array<int, string> The stored key names, empty when unreadable.
	 */
	private function oldKeys(): array {
		try {
			return $this->appConfig->getKeys(self::OLD_APP_ID);
		} catch (\Throwable $e) {
			$this->logger->warning(
				'Filinq: could not enumerate docudesk app config keys; skipping the migration',
				['exception' => $e->getMessage()]
			);
			return [];
		}//end try
	}//end oldKeys()
}//end class
