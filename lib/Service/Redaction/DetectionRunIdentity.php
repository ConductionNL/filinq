<?php

/**
 * What "this detection run" means, as a value anything can recompute.
 *
 * 🔴 THE REVIEW GATE COMPARES A MARK AGAINST A RUN, SO THE RUN NEEDS A NAME
 * THAT CANNOT DRIFT. If the name were a counter, a timestamp or a row id, two
 * things follow that both end the same way: a mark could be written against a
 * name nothing else can reproduce, and a re-detection could land on the same
 * name. Either one lets a copy be written over findings nobody looked at.
 *
 * 🔴 SO THE NAME IS A FINGERPRINT OF WHAT WAS ACTUALLY FOUND. The file, its
 * current bytes, and every entity the detector proposed, in a stable order.
 * Re-run detection and get the same findings on the same file: the same name,
 * and the check still holds, which is correct because nothing changed. Re-run
 * it and find one entity more, or run it on an edited file: a different name,
 * and the earlier check no longer covers this one. Nothing has to remember to
 * clear anything.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Redaction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Redaction;

/**
 * Names one detection run by what it found.
 */
final class DetectionRunIdentity {

	/**
	 * The name of the run that found these entities on this file.
	 *
	 * @param int                              $fileId   The Nextcloud file id.
	 * @param array<int, array<string, mixed>> $entities The entities the detector proposed.
	 * @param string                           $etag     The file's etag or revision marker, when known.
	 *
	 * @return string The run name, or an empty string when there is no file to name.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function of(int $fileId, array $entities, string $etag = ''): string {
		if ($fileId <= 0) {
			return '';
		}

		$fingerprints = [];
		foreach ($entities as $entity) {
			if (is_array($entity) === false) {
				continue;
			}

			$fingerprints[] = $this->fingerprintOf(entity: $entity);
		}

		// Sorted, so the order the detector happened to return them in is not
		// part of the name: a re-run that finds the same things in a different
		// order is the same run, and refusing it would train an operator to
		// click through the refusal.
		sort($fingerprints);

		$material = $fileId.'|'.trim($etag).'|'.implode(',', $fingerprints);

		return substr(hash(algo: 'sha256', data: $material), offset: 0, length: 40);

	}//end of()

	/**
	 * One entity, reduced to what makes it a different finding.
	 *
	 * @param array<string, mixed> $entity The entity.
	 *
	 * @return string The fingerprint.
	 *
	 * @spec exclude Reduction helper behind of().
	 */
	private function fingerprintOf(array $entity): string {
		$parts = [
			(string)($entity['entityType'] ?? ($entity['type'] ?? '')),
			(string)($entity['text'] ?? ($entity['value'] ?? '')),
			(string)($entity['start'] ?? ($entity['offset'] ?? '')),
			(string)($entity['end'] ?? ($entity['length'] ?? '')),
		];

		return implode(':', $parts);

	}//end fingerprintOf()
}//end class
