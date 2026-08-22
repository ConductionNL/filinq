<?php

/**
 * Filinq Migrate User Preferences Repair Step
 *
 * Repair step that carries this app's per-user preferences across the
 * `docudesk` -> `filinq` app-id rename.
 *
 * WHY THIS EXISTS SEPARATELY FROM MigrateAppConfigKeys. `IAppConfig` and
 * `IConfig`'s user values are different stores: the former is `oc_appconfig`,
 * the latter `oc_preferences`. Both are namespaced by app id, so both are cut
 * off by the rename, but copying one does nothing for the other.
 *
 * WHY IT MATTERS MORE THAN IT LOOKS. Every per-user key this app stores is a
 * DISMISSAL — a one-time nag the user has already told the app to stop showing.
 * `SettingsController::index()` reads `anonymiser_warning_dismissed` with a
 * default of `''` and treats anything that is not `'1'` as "not dismissed", and
 * nextcloud-vue's `useSupportDialog(appId, { persistence: 'server' })` reads
 * `pref_support-dialog-seen` the same way. After the rename the lookup finds
 * nothing, falls back to the default, and the banner and the support dialog
 * both come back for every user who had already dismissed them. The failure is
 * invisible — no error, no log, just a preference quietly reverting — which is
 * exactly why it needs a migration rather than a release note.
 *
 * WHY IT ENUMERATES BY VALUE RATHER THAN BY USER. `IConfig` offers no "list
 * every key this app stored for every user" call. It does offer
 * `getUsersForUserValue(app, key, value)`, so the step asks for each concrete
 * value a key can hold. That is exhaustive for these keys by construction: both
 * are written only ever as the literal `'1'`
 * (`AnonymiserWarningController::dismiss()` writes `'1'` and
 * `deleteUserValue()`s on undismiss; `useSupportDialog`'s `hasRealFlag` returns
 * early only when it reads exactly `'1'`), and a user with nothing stored needs
 * no migration because the default already applies.
 *
 * IF A FUTURE RELEASE ADDS ANOTHER PER-USER KEY it must be added to
 * `MIGRATED_KEYS`. That is a real hazard here: `PreferencesController` writes
 * an OPEN key space — `pref_` plus any caller-supplied `[a-z0-9-]` name — so
 * the generic endpoint can store a key this constant does not list. Only the
 * one key nextcloud-vue actually uses is in production today, and enumerating
 * an open key space is impossible through `IConfig`; a key missing here is a
 * key that silently resets.
 *
 * SAFETY. Idempotent and non-destructive, matching MigrateAppConfigKeys:
 *   - a value is copied only when the user has nothing stored under the new
 *     app id, so a preference changed after the rename is never clobbered and
 *     a second run is a no-op;
 *   - the old `docudesk` rows are never deleted, so a rollback still finds them;
 *   - every failure is logged and the loop continues, because one unreadable
 *     preference is not worth aborting an install over.
 *
 * The key NAMES are copied verbatim, unlike MigrateAppConfigKeys' prefix
 * rewrite: no per-user key in this app embeds the app id, so there is nothing
 * to rewrite.
 *
 * Registered under BOTH `<install>` and `<post-migration>` in
 * `appinfo/info.xml` alongside MigrateAppConfigKeys — see the ordering comment
 * there. This step has no ordering relationship with settings initialisation,
 * which never writes user values.
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

use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Psr\Log\LoggerInterface;

/**
 * Copy per-user preferences from the docudesk app id to filinq.
 *
 * @spec exclude No canonical spec covers the docudesk -> filinq app-id rename.
 */
class MigrateUserPreferences implements IRepairStep {
	/**
	 * The `oc_preferences` namespace this app used before the rename.
	 *
	 * Deliberately the OLD app id.
	 *
	 * @var string
	 */
	private const OLD_APP_ID = 'docudesk';

	/**
	 * The `oc_preferences` namespace this app uses after the rename.
	 *
	 * @var string
	 */
	private const NEW_APP_ID = 'filinq';

	/**
	 * Every per-user key this app has ever written, with the values it can
	 * hold. Add to this list when a new per-user preference is introduced;
	 * a key missing here is a key that silently resets on the next rename.
	 *
	 * @var array<string, array<int, string>>
	 */
	private const MIGRATED_KEYS = [
		'anonymiser_warning_dismissed' => ['1'],
		'pref_support-dialog-seen' => ['1'],
	];

	/**
	 * Constructor.
	 *
	 * @param IConfig $config The user-value store to read and write.
	 * @param LoggerInterface $logger Logger for preferences that fail to copy.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IConfig $config,
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
		return 'Copy filinq per-user preferences from the docudesk app id';
	}//end getName()

	/**
	 * Copy every known per-user preference from the old app id to the new one.
	 *
	 * @param IOutput $output Repair output channel.
	 *
	 * @return void
	 *
	 * @spec exclude No canonical spec covers the docudesk -> filinq app-id rename.
	 */
	public function run(IOutput $output): void {
		$migrated = 0;
		$alreadyPresent = 0;

		foreach (self::MIGRATED_KEYS as $key => $values) {
			foreach ($values as $value) {
				/* Guarded like the per-user work below it. This enumeration
				   used to sit outside any try, so an unreadable preference
				   store propagated out of run() and aborted `occ upgrade` —
				   the one thing this class's docblock promises it will not
				   do. One unreadable key/value pair costs those users their
				   stored preference; it should not cost everyone the
				   upgrade. */
				try {
					$userIds = $this->config->getUsersForUserValue(self::OLD_APP_ID, $key, $value);
				} catch (\Throwable $e) {
					$this->logger->warning(
						'MigrateUserPreferences: could not enumerate users for one preference; leaving it under the old app id.',
						['key' => $key, 'value' => $value, 'exception' => $e->getMessage()]
					);
					continue;
				}//end try

				foreach ($userIds as $userId) {
					try {
						$existing = $this->config->getUserValue($userId, self::NEW_APP_ID, $key, '');
						if ($existing !== '') {
							$alreadyPresent++;
							continue;
						}

						$this->config->setUserValue($userId, self::NEW_APP_ID, $key, $value);
						$migrated++;
					} catch (\Throwable $e) {
						$this->logger->warning(
							'MigrateUserPreferences: could not migrate one preference; leaving it under the old app id.',
							['exception' => $e->getMessage(), 'key' => $key, 'app' => self::NEW_APP_ID]
						);
					}//end try
				}//end foreach
			}//end foreach
		}//end foreach

		if ($migrated === 0 && $alreadyPresent === 0) {
			$output->info(
				'MigrateUserPreferences: no stored docudesk user preferences on this install; nothing to do.'
			);
			return;
		}

		$output->info(
			sprintf(
				'MigrateUserPreferences: migrated %d preference(s); %d already set under filinq.',
				$migrated,
				$alreadyPresent
			)
		);
	}//end run()
}//end class
