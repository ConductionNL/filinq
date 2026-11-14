<?php

/**
 * Assurance Level
 *
 * The eIDAS levels of assurance a signer's identity can carry, and the floor
 * each signature level puts under a signing request.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\SignerAuth
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

namespace OCA\Filinq\Service\SignerAuth;

/**
 * The canonical assurance scale: `low`, `substantial`, `high`.
 *
 * Anything that is not one of the three degrades to `low`, never up: an
 * identity whose level nobody can name meets no gate above the weakest.
 * Stateless; instantiate it where needed.
 *
 * @category Service
 * @package  OCA\Filinq\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
final class AssuranceLevel {

	/**
	 * The levels, weakest first.
	 *
	 * @var list<string>
	 */
	public const LEVELS = ['low', 'substantial', 'high'];

	/**
	 * The assurance floor of each signature level (REQ-DDSIR-002).
	 *
	 * @var array<string, string>
	 */
	public const SIGNATURE_LEVEL_FLOORS = [
		'SES' => 'low',
		'AdES' => 'substantial',
		'QES' => 'high',
	];

	/**
	 * A level, where anything undeclared becomes `low`.
	 *
	 * @param mixed $value The candidate level.
	 *
	 * @return string `low`, `substantial` or `high`.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function normalise(mixed $value): string {
		if (is_string($value) === true && in_array($value, self::LEVELS, true) === true) {
			return $value;
		}

		return 'low';

	}//end normalise()

	/**
	 * Is a candidate level a declared one.
	 *
	 * @param mixed $value The candidate level.
	 *
	 * @return bool True for `low`, `substantial` or `high`.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function isLevel(mixed $value): bool {
		return is_string($value) === true && in_array($value, self::LEVELS, true) === true;

	}//end isLevel()

	/**
	 * Does one level meet another.
	 *
	 * @param string $held The level the identity carries.
	 * @param string $required The level asked for.
	 *
	 * @return bool True when `$held` is at least `$required`.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function meets(string $held, string $required): bool {
		return $this->rank(level: $held) >= $this->rank(level: $required);

	}//end meets()

	/**
	 * The strongest of the given levels.
	 *
	 * @param list<string> $levels The levels to compare; undeclared ones count as `low`.
	 *
	 * @return string The strongest, or `low` when none is given.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function strongest(array $levels): string {
		$best = 'low';
		foreach ($levels as $level) {
			if ($this->rank(level: (string)$level) > $this->rank(level: $best)) {
				$best = $this->normalise(value: $level);
			}
		}

		return $best;

	}//end strongest()

	/**
	 * The weakest of the given levels.
	 *
	 * @param list<string> $levels The levels to compare; undeclared ones count as `low`.
	 *
	 * @return string The weakest, or `low` when none is given.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function weakest(array $levels): string {
		if ($levels === []) {
			return 'low';
		}

		$worst = 'high';
		foreach ($levels as $level) {
			if ($this->rank(level: (string)$level) < $this->rank(level: $worst)) {
				$worst = $this->normalise(value: $level);
			}
		}

		return $worst;

	}//end weakest()

	/**
	 * The floor a signature level puts under a request.
	 *
	 * @param string $signatureLevel `SES`, `AdES` or `QES`; anything else is treated as `SES`.
	 *
	 * @return string The assurance floor.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function floorFor(string $signatureLevel): string {
		return self::SIGNATURE_LEVEL_FLOORS[$signatureLevel] ?? 'low';

	}//end floorFor()

	/**
	 * The assurance a request requires: its own value, never below its floor.
	 *
	 * An absent or undeclared `requiredAssurance` takes the floor, which is
	 * also how a request made before the rails existed is read (SES reads as
	 * `low`, so nothing changes for it).
	 *
	 * @param array<string, mixed> $request The request, or its creation payload.
	 *
	 * @return string The assurance that applies.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function requiredFor(array $request): string {
		$floor = $this->floorFor(signatureLevel: (string)($request['signatureLevel'] ?? 'SES'));
		$asked = $request['requiredAssurance'] ?? null;
		if ($this->isLevel(value: $asked) === false) {
			return $floor;
		}

		if ($this->meets(held: (string)$asked, required: $floor) === true) {
			return (string)$asked;
		}

		return $floor;

	}//end requiredFor()

	/**
	 * The position of a level on the scale.
	 *
	 * @param string $level The level.
	 *
	 * @return int 0 for `low` (and anything undeclared), 1 for `substantial`, 2 for `high`.
	 */
	private function rank(string $level): int {
		$index = array_search($level, self::LEVELS, true);
		if ($index === false) {
			return 0;
		}

		return (int)$index;

	}//end rank()
}//end class
