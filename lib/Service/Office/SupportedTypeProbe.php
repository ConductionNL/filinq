<?php

/**
 * Filinq SupportedTypeProbe
 *
 * Answers, per document type, whether the INSTALLED office suite actually
 * edits it here — measured, never assumed.
 *
 * ⚠️ The rule this exists to enforce: **an unprobed type is UNSUPPORTED.** Not
 * "probably fine because LibreOffice can open it". The suites differ
 * (Collabora is LibreOffice lineage, Euro-Office is ONLYOFFICE lineage), they
 * differ again between versions, and any table written into the source would
 * be wrong by the next release. So the answer is derived from the suite's own
 * WOPI discovery document and published with the suite name, version and the
 * date it was measured — which is what makes it visibly stale later.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Office
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#0-phase-0--probe-what-the-installed-suite-actually-edits-hard-gate
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Office;

/**
 * Parses a WOPI discovery document into a per-type support declaration.
 */
class SupportedTypeProbe {

	/**
	 * Every type the editing tools could plausibly be asked to handle.
	 *
	 * A type NOT on this list is never reported as supported, however the suite
	 * advertises it: the list is the set of types this app has decided how to
	 * treat, and discovering an unexpected one is a reason to think, not to
	 * enable it silently.
	 *
	 * @var array<int, string>
	 */
	public const CANDIDATE_TYPES = [
		'odt', 'docx', 'doc',
		'ods', 'xlsx', 'xls',
		'odp', 'pptx', 'ppt',
		'odg',
		'csv',
		'pdf',
	];

	/**
	 * Types refused regardless of what the suite advertises.
	 *
	 * 🔴 Macro-bearing formats are a code-execution vector in document
	 * clothing, and `.odb` is a database with no "edit a cell" semantics. The
	 * refusal lives HERE, in front of the probe, so a suite that happily edits
	 * them cannot talk the system into offering them.
	 *
	 * @var array<int, string>
	 */
	public const REFUSED_TYPES = ['docm', 'xlsm', 'pptm', 'odb'];

	/**
	 * Why each refused type is refused.
	 *
	 * Two different reasons live in one list and they are not interchangeable:
	 * three are refused because editing them is a code-execution vector, and one
	 * because "edit a cell" means nothing in it. An operator asking to have a
	 * refusal lifted needs to know which argument they are up against.
	 *
	 * @var array<string, string>
	 */
	private const REFUSAL_GROUNDS = [
		'docm' => 'macro-bearing format — editing it is a code-execution vector',
		'xlsm' => 'macro-bearing format — editing it is a code-execution vector',
		'pptm' => 'macro-bearing format — editing it is a code-execution vector',
		'odb'  => 'a database, with no document block or cell to edit',
	];

	/**
	 * Build the support declaration from a WOPI discovery document.
	 *
	 * @param string      $discoveryXml The suite's WOPI discovery document.
	 * @param string|null $suite        The identified suite name, when known.
	 * @param string      $probedAt     ISO-8601 timestamp of this measurement.
	 * @param string      $endpoint     The endpoint measured, for provenance.
	 *
	 * @return array<string, mixed> The declaration.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-0.2
	 */
	public function declare(string $discoveryXml, ?string $suite, string $probedAt, string $endpoint): array {
		$editable = $this->extensionsFor(discoveryXml: $discoveryXml, action: 'edit');
		$viewable = $this->extensionsFor(discoveryXml: $discoveryXml, action: 'view');

		// 🔴 The refused types are declared HERE, not omitted. Iterating only
		// CANDIDATE_TYPES made the refusal below dead code — none of `docm`,
		// `xlsm`, `pptm` or `odb` is a candidate, so `$refused` was false on
		// every pass and the guard had never once fired. phpstan said so
		// outright: `in_array()` "will always evaluate to false".
		//
		// Absence was still SAFE — an undeclared type is unsupported — but it
		// was safe by accident, and it made the refusal a comment rather than a
		// rule. Worse, it is a rule that looks live: adding `docm` to the
		// candidate list later, expecting the refusal to catch it, is a
		// perfectly reasonable thing to do. Declaring them explicitly makes the
		// guard real, testable, and visible to an operator as a REASON rather
		// than a type that simply is not mentioned anywhere.
		$types = [];
		foreach ([...self::CANDIDATE_TYPES, ...self::REFUSED_TYPES] as $type) {
			$refused = in_array($type, self::REFUSED_TYPES, true);
			$canEdit = ($refused === false && isset($editable[$type]) === true);

			$types[$type] = [
				'edit' => $canEdit,
				'view' => (isset($viewable[$type]) === true),
				// Why, not just whether. A capability that is absent because the
				// suite never offered it and one absent because this app refuses
				// it are different facts, and an operator debugging a missing
				// tool needs to know which.
				'reason' => $this->reasonFor(type: $type, refused: $refused, advertised: isset($editable[$type])),
			];
		}

		return [
			'suite' => $suite,
			'suiteVersion' => $this->versionFrom(discoveryXml: $discoveryXml),
			'probedAt' => $probedAt,
			'endpoint' => $endpoint,
			'types' => $types,
			// Anything the suite edits that this app has no opinion about. Not
			// enabled — surfaced, so the gap is visible rather than silent.
			'advertisedButUnhandled' => array_values(
				array_diff(array_keys($editable), self::CANDIDATE_TYPES, self::REFUSED_TYPES)
			),
		];
	}//end declare()

	/**
	 * An empty declaration, for when the suite could not be reached.
	 *
	 * ⚠️ Every type reports UNSUPPORTED rather than unknown. A probe that could
	 * not run has not established that anything works, and treating "we could
	 * not ask" as "probably yes" is the exact failure this class exists to
	 * prevent.
	 *
	 * @param string $reason   Why the probe could not run.
	 * @param string $probedAt ISO-8601 timestamp of the attempt.
	 *
	 * @return array<string, mixed> The declaration.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-0.2
	 */
	public function unavailable(string $reason, string $probedAt): array {
		$types = [];
		foreach (self::CANDIDATE_TYPES as $type) {
			$types[$type] = ['edit' => false, 'view' => false, 'reason' => 'not probed: ' . $reason];
		}

		// A refused type is refused whether or not the probe ran — the refusal
		// sits in FRONT of the probe, which is the whole premise. Reporting
		// these as "not probed" would say the suite might yet be asked, and
		// that is not true of a macro-bearing format.
		foreach (self::REFUSED_TYPES as $type) {
			$types[$type] = [
				'edit' => false,
				'view' => false,
				'reason' => $this->reasonFor(type: $type, refused: true, advertised: false),
			];
		}

		return [
			'suite' => null,
			'suiteVersion' => null,
			'probedAt' => $probedAt,
			'endpoint' => null,
			'types' => $types,
			'advertisedButUnhandled' => [],
		];
	}//end unavailable()

	/**
	 * The extensions the discovery document declares for one action.
	 *
	 * @param string $discoveryXml The discovery document.
	 * @param string $action       The WOPI action name (`edit`, `view`).
	 *
	 * @return array<string, true> Extension set, as a lookup.
	 */
	private function extensionsFor(string $discoveryXml, string $action): array {
		$found = [];
		$pattern = '/<action[^>]*\bname="' . preg_quote($action, '/') . '"[^>]*\bext="([a-z0-9]+)"/i';

		if (preg_match_all($pattern, $discoveryXml, $matches) > 0) {
			foreach ($matches[1] as $ext) {
				$found[strtolower($ext)] = true;
			}
		}

		// The attribute order is not fixed, so look for the mirror form too
		// rather than assume `name` precedes `ext`. A discovery document that
		// happens to order them the other way would otherwise report every
		// type unsupported, which reads exactly like a suite that edits nothing.
		$mirror = '/<action[^>]*\bext="([a-z0-9]+)"[^>]*\bname="' . preg_quote($action, '/') . '"/i';
		if (preg_match_all($mirror, $discoveryXml, $matches) > 0) {
			foreach ($matches[1] as $ext) {
				$found[strtolower($ext)] = true;
			}
		}

		return $found;
	}//end extensionsFor()

	/**
	 * The suite version, when the discovery document carries one.
	 *
	 * @param string $discoveryXml The discovery document.
	 *
	 * @return string|null The version, or null.
	 */
	private function versionFrom(string $discoveryXml): ?string {
		if (preg_match('/\bproduct-version="([^"]+)"/i', $discoveryXml, $m) === 1) {
			return $m[1];
		}

		if (preg_match('/\bversion="([^"]+)"/i', $discoveryXml, $m) === 1) {
			return $m[1];
		}

		return null;
	}//end versionFrom()

	/**
	 * Say why a type is or is not editable.
	 *
	 * @param string $type       The candidate type.
	 * @param bool   $refused    Whether this app refuses it outright.
	 * @param bool   $advertised Whether the suite advertises editing it.
	 *
	 * @return string The reason.
	 */
	private function reasonFor(string $type, bool $refused, bool $advertised): string {
		if ($refused === true) {
			// The GROUND, not just the verdict. "refused by policy" tells an
			// operator the answer without telling them whether it is the right
			// answer for their instance; the two refusals here have entirely
			// different reasoning and only one of them is about safety.
			return 'refused by policy: ' . (self::REFUSAL_GROUNDS[$type] ?? 'not offered for editing');
		}

		if ($advertised === false) {
			return 'suite does not advertise editing this type';
		}

		return 'suite advertises editing this type';
	}//end reasonFor()
}//end class
