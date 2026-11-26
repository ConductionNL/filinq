<?php

/**
 * App config flag
 *
 * Reads an on/off app setting whatever type it was stored with.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md#requirement-optionally-suggest-batchfolder-analysis-priority-req-ddfcl-003
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Support;

use OCP\Exceptions\AppConfigTypeConflictException;
use OCP\IAppConfig;

/**
 * An on/off app setting, read whatever type it was stored with.
 *
 * The admin settings save every value as a string ("1", "0", "true"), while
 * Nextcloud's typed reader refuses a string asked for as a boolean and
 * throws. Reading such a flag with getValueBool() therefore broke every read
 * after the first save of the settings page. This reads the string first and
 * falls back to the typed boolean when the value was stored as one.
 */
final class AppConfigFlag {

	/**
	 * The strings that switch a flag on.
	 */
	private const ON = ['1', 'true', 'yes', 'on'];

	/**
	 * Constructor.
	 *
	 * @param IAppConfig $config The app config.
	 */
	public function __construct(
		private readonly IAppConfig $config,
	) {
	}//end __construct()

	/**
	 * Whether the flag is on; an unset flag is off.
	 *
	 * @param string $app The app id.
	 * @param string $key The key.
	 *
	 * @return bool Whether the flag is on.
	 *
	 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md#requirement-optionally-suggest-batchfolder-analysis-priority-req-ddfcl-003
	 */
	public function isOn(string $app, string $key): bool {
		try {
			$stored = trim($this->config->getValueString($app, $key, ''));
		} catch (AppConfigTypeConflictException) {
			return $this->config->getValueBool($app, $key, false);
		}

		if ($stored === '') {
			return $this->config->getValueBool($app, $key, false);
		}

		return in_array(strtolower($stored), self::ON, true);
	}//end isOn()
}//end class
