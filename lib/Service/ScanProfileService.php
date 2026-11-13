<?php

/**
 * Scan Profile Service
 *
 * The scan profiles an administrator declared: one watched folder per profile,
 * how its batches are cut, and where the segments go.
 *
 * A profile is configuration rather than a register object because it names
 * PATHS on this instance. Those do not travel between installations, and a
 * configuration set that carried them would point a new instance's scanner at a
 * folder that means something else there.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Kernel\Contracts\ScanProfileServiceInterface;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Reads and writes the declared scan profiles.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 */
class ScanProfileService implements ScanProfileServiceInterface {

	/**
	 * The app id these profiles belong to.
	 *
	 * @var string
	 */
	private const APP_ID = 'filinq';

	/**
	 * The resolution a profile scans at when it declares none, in dpi.
	 *
	 * @var int
	 */
	private const DEFAULT_RESOLUTION = 300;

	/**
	 * The colour modes a profile may declare; the first is the default.
	 *
	 * @var array<int, string>
	 */
	public const COLOUR_MODES = ['colour', 'greyscale', 'blackAndWhite'];

	/**
	 * Constructor.
	 *
	 * @param IAppConfig $config App configuration.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IAppConfig $config,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Every declared profile.
	 *
	 * @return array<int, array<string, mixed>> The profiles.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function all(): array {
		$raw = $this->config->getValueString(self::APP_ID, self::CONFIG_KEY, '');
		if (trim($raw) === '') {
			return [];
		}

		try {
			$decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
		} catch (Throwable $e) {
			$this->logger->error(
				message: '[ScanProfileService] the declared scan profiles are not valid JSON; no folder is watched',
				context: ['file' => __FILE__, 'line' => __LINE__, 'error' => $e->getMessage()]
			);

			return [];
		}

		if (is_array($decoded) === false) {
			return [];
		}

		$profiles = [];
		foreach ($decoded as $profile) {
			if (is_array($profile) === true && trim((string)($profile['id'] ?? '')) !== '') {
				$profiles[] = $profile;
			}
		}

		return $profiles;

	}//end all()

	/**
	 * One profile by its id.
	 *
	 * @param string $id The profile id.
	 *
	 * @return array<string, mixed>|null The profile, or null when nobody declared it.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function find(string $id): ?array {
		foreach ($this->all() as $profile) {
			if ((string)$profile['id'] === $id) {
				return $profile;
			}
		}

		return null;

	}//end find()

	/**
	 * The profile whose watched folder a file landed in.
	 *
	 * The match is on the folder PATH, and the longest matching folder wins, so
	 * a profile for `Scans/bezwaar` is not shadowed by one for `Scans`.
	 *
	 * @param string $path The path the file landed at.
	 *
	 * @return array<string, mixed>|null The profile, or null when the path is nobody's.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function forPath(string $path): ?array {
		$path = '/' . trim($path, '/');
		$match = null;
		$matched = -1;

		foreach ($this->all() as $profile) {
			$folder = '/' . trim((string)($profile['watchedFolder'] ?? ''), '/');
			if ($folder === '/' ) {
				continue;
			}

			if (str_starts_with($path, ($folder . '/')) === false && $path !== $folder) {
				continue;
			}

			if (strlen($folder) > $matched) {
				$matched = strlen($folder);
				$match = $profile;
			}
		}

		return $match;

	}//end forPath()

	/**
	 * Declare the profiles.
	 *
	 * @param array<int, array<string, mixed>> $profiles The profiles.
	 *
	 * @return array<int, array<string, mixed>> What was stored.
	 *
	 * @throws RuntimeException When a profile names no id or no folder.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function declare(array $profiles): array {
		$stored = [];
		foreach ($profiles as $profile) {
			$id = trim((string)($profile['id'] ?? ''));
			$folder = trim((string)($profile['watchedFolder'] ?? ''));
			if ($id === '' || $folder === '') {
				throw new RuntimeException(
					message: 'A scan profile names an id and the folder its scanner writes to.'
				);
			}

			$mode = (string)($profile['separatorMode'] ?? ScanBatchService::MODE_QR);
			if (in_array($mode, [ScanBatchService::MODE_QR, ScanBatchService::MODE_BLANK, ScanBatchService::MODE_NONE], true) === false) {
				throw new RuntimeException(
					message: 'A scan profile cuts on qr, on blankPage or not at all (none), not on "' . $mode . '".'
				);
			}

			$stored[] = [
				'id' => $id,
				'label' => (string)($profile['label'] ?? $id),
				'watchedFolder' => $folder,
				'separatorMode' => $mode,
				'resolution' => $this->resolutionOf(profile: $profile),
				'colourMode' => $this->colourModeOf(profile: $profile),
				'inkThreshold' => (int)($profile['inkThreshold'] ?? 4096),
				'targetFolder' => (string)($profile['targetFolder'] ?? $folder),
			];
		}//end foreach

		$this->config->setValueString(self::APP_ID, self::CONFIG_KEY, json_encode($stored));

		return $stored;

	}//end declare()

	/**
	 * The scan resolution in dpi a profile declares; 300 when it names none.
	 *
	 * @param array<string, mixed> $profile The declared profile.
	 *
	 * @return int The resolution.
	 *
	 * @throws RuntimeException When the value is not a whole number from 75 to 1200.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md#requirement-a-scanner-profile-is-saved-per-source-and-reused-req-sci-05
	 */
	private function resolutionOf(array $profile): int {
		$value = ($profile['resolution'] ?? self::DEFAULT_RESOLUTION);
		$resolution = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 75, 'max_range' => 1200]]);
		if ($resolution === false) {
			$shown = gettype($value);
			if (is_scalar($value) === true) {
				$shown = (string)$value;
			}

			throw new RuntimeException(
				message: 'A scan profile scans at a resolution from 75 to 1200 dpi, not at "' . $shown . '".'
			);
		}

		return $resolution;

	}//end resolutionOf()

	/**
	 * The colour mode a profile declares; colour when it names none.
	 *
	 * @param array<string, mixed> $profile The declared profile.
	 *
	 * @return string One of COLOUR_MODES.
	 *
	 * @throws RuntimeException When the mode is not one of COLOUR_MODES.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md#requirement-a-scanner-profile-is-saved-per-source-and-reused-req-sci-05
	 */
	private function colourModeOf(array $profile): string {
		$mode = (string)($profile['colourMode'] ?? self::COLOUR_MODES[0]);
		if (in_array($mode, self::COLOUR_MODES, true) === false) {
			throw new RuntimeException(
				message: 'A scan profile scans in ' . implode(', ', self::COLOUR_MODES) . ', not in "' . $mode . '".'
			);
		}

		return $mode;

	}//end colourModeOf()
}//end class
