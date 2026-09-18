<?php

/**
 * A machine-drafted plain rendition is a suggestion until somebody says otherwise.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/letter-correspondence-generation/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Exception\PlainRenditionRefusedException;

/**
 * Holds an unaccepted machine draft inside the building.
 *
 * 🔴 THE GATE IS ONE CHOKEPOINT, ASKED THE SAME QUESTION BY EVERY PATH. REQ-DIO-05
 * says an unaccepted suggestion must not leave through generation, correspondence
 * or the portal, and a check written into one of those three is a check the other
 * two do not have. So the decision lives here and every path calls it; a path
 * that forgets is a missing call somebody can grep for, rather than a subtly
 * different rule nobody can see.
 *
 * 🔴 ACCEPTANCE IS A PERSON AND A MOMENT, BOTH OR NEITHER. "Accepted: true" can
 * be written by anything, including the same machine that drafted the text, and
 * it is exactly the flag REQ-DIO-05 exists to refuse. A name with no moment
 * cannot be placed in time when somebody asks a year later whether the draft was
 * read before or after the correction, so half an acceptance is refused as an
 * acceptance, not accepted as half.
 *
 * 🔑 A RENDITION FROM THE COUNTERPART TEMPLATE NEEDS NO ACCEPTANCE. It is the
 * organisation's own text, reviewed when the template was written. Demanding an
 * acceptance there would make every ordinary besluit wait for a click nobody was
 * told to make, which is how a gate takes down the feature it guards.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/letter-correspondence-generation/spec.md
 */
class PlainRenditionAcceptanceGate {

	/**
	 * The plain text came from the counterpart template.
	 *
	 * @var string
	 */
	public const SOURCE_TEMPLATE = 'template';

	/**
	 * The plain text was drafted by a machine.
	 *
	 * @var string
	 */
	public const SOURCE_MACHINE = 'machine';

	/**
	 * Whether a rendition of this source needs a person to accept it.
	 *
	 * @param string $source Where the plain text came from.
	 *
	 * @return bool True when an acceptance is required.
	 *
	 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/letter-correspondence-generation/spec.md
	 */
	public function needsAcceptance(string $source): bool {
		return $source === self::SOURCE_MACHINE;

	}//end needsAcceptance()

	/**
	 * Read one acceptance, or refuse it.
	 *
	 * @param string               $source     Where the plain text came from.
	 * @param array<string, mixed> $acceptance The acceptance as the caller offers it: `acceptedBy` and `acceptedAt`.
	 *
	 * @return array{acceptedBy: string, acceptedAt: string} The acceptance, empty when none is needed.
	 *
	 * @throws PlainRenditionRefusedException When a machine draft has no complete acceptance.
	 *
	 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/letter-correspondence-generation/spec.md
	 */
	public function require(string $source, array $acceptance): array {
		if ($this->needsAcceptance(source: $source) === false) {
			return ['acceptedBy' => '', 'acceptedAt' => ''];
		}

		$by = trim((string)($acceptance['acceptedBy'] ?? ''));
		$at = trim((string)($acceptance['acceptedAt'] ?? ''));

		if ($by === '' && $at === '') {
			throw new PlainRenditionRefusedException(
				message: 'This plain-language text was drafted by a machine and nobody has accepted it yet, so it stays here. The draft is still waiting.',
				unresolved: ['acceptance']
			);
		}

		if ($by === '' || $at === '') {
			// Half an acceptance is refused AS an acceptance. Taking the half
			// that is there and filling in the rest would put a name on a
			// moment nobody recorded, or a moment against nobody.
			throw new PlainRenditionRefusedException(
				message: 'The acceptance of this plain-language text records only ' . ($by === '' ? 'a moment and no person' : 'a person and no moment') . ', so it cannot be read as an acceptance.',
				unresolved: ['acceptance']
			);
		}

		return ['acceptedBy' => $by, 'acceptedAt' => $at];

	}//end require()
}//end class
