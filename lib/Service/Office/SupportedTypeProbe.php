<?php

/**
 * Docudesk SupportedTypeProbe
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
 * @package  OCA\DocuDesk\Service\Office
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

namespace OCA\DocuDesk\Service\Office;

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
	 * Build the support declaration from a WOPI discovery document.
	 *
	 * @param string      $discoveryXml The suite's WOPI discovery document.
	 * @param string|null $suite        The identified suite name, when known.
	 * @param string      $probedAt     ISO-8601 timestamp of this measurement.
	 * @param string      $endpoint     The endpoint measured, for provenance.
	 *
	 * @return array<string, mixed> The declaration.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#02
	 */
	public function declare(string $discoveryXml, ?string $suite, string $probedAt, string $endpoint): array {
		$editable = $this->extensionsFor(discoveryXml: $discoveryXml, action: 'edit');
		$viewable = $this->extensionsFor(discoveryXml: $discoveryXml, action: 'view');

		$types = [];
		foreach (self::CANDIDATE_TYPES as $type) {
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
	 */
	public function unavailable(string $reason, string $probedAt): array {
		$types = [];
		foreach (self::CANDIDATE_TYPES as $type) {
			$types[$type] = ['edit' => false, 'view' => false, 'reason' => 'not probed: ' . $reason];
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
			return 'refused by policy';
		}

		if ($advertised === false) {
			return 'suite does not advertise editing this type';
		}

		return 'suite advertises editing this type';
	}//end reasonFor()
}//end class
