<?php

/**
 * Erasing a person from the record store, and what refuses.
 *
 * WHAT THIS SHARES WITH THE PLATFORM, AND WHERE IT MUST NOT.
 *
 * OpenRegister's `anonymising-as-an-archival-outcome` answers a different
 * question with a similar shape, and the differences are deliberate rather than
 * accidental. Both are recorded here so nobody later "aligns" them.
 *
 * REUSED. The refusal-rather-than-guess posture, the per-item refusal that lets
 * the rest of the run continue, the marker that says a run was interrupted and
 * can be resumed, and the rule that an unverifiable state never counts as a
 * clean one.
 *
 * 🔴 DIVERGENCE 1: THE PSEUDONYM IS THE OPPOSITE REQUIREMENT. The platform's
 * `pseudonym` treatment deliberately produces a STABLE, JOINABLE token, so a
 * municipality can still count how many cases one person had. Its own docblock
 * names the residual risk: a token that joins is a token that can be
 * correlated. For an archival outcome that trade is right.
 *
 * For a data subject's erasure it is wrong in both directions. A stable token
 * still links every document that mentioned them, which is the linkage the
 * request exists to break; and a REVERSIBLE mapping is a way back to the person
 * that survives the erasure entirely. So an erasure never applies `pseudonym`,
 * and it destroys any reversible mapping it finds.
 *
 * 🔴 DIVERGENCE 2: THE TRIGGER, AND THEREFORE THE REFUSALS. The platform acts
 * when a retention clock runs out, so a legal hold refusing the whole act is
 * proportionate. An erasure is a person exercising a right across documents
 * they did not choose, so one held document must not deny the rest of the
 * request. Every obligation refuses ITS OWN document, names itself, and names
 * who must decide — because a refusal a requester cannot escalate is a refusal
 * that reads as a silent failure.
 *
 * 🔴 DIVERGENCE 3: THE RECORDS STAY. The platform rewrites a record's own
 * properties. Here the documents remain and only the person comes out of them,
 * and where a version is final the content is superseded rather than edited, so
 * the version record still says what was final and when.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\SubjectErasure
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/erase-a-person-while-the-records-stay/specs/erase-a-person-while-the-records-stay/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\SubjectErasure;

/**
 * The obligations that refuse an erasure, and the certificate it produces.
 */
class SubjectErasureRules {

	/**
	 * A retention obligation: the record must be kept for a term.
	 *
	 * @var string
	 */
	public const RETENTION = 'retention';

	/**
	 * A publication prohibition standing over this document.
	 *
	 * @var string
	 */
	public const PROHIBITION = 'publication_prohibition';

	/**
	 * A legal hold: somebody may still need this exactly as it is.
	 *
	 * @var string
	 */
	public const LEGAL_HOLD = 'legal_hold';

	/**
	 * Every obligation that refuses, in the order they are reported.
	 *
	 * @var string[]
	 */
	public const OBLIGATIONS = [self::LEGAL_HOLD, self::PROHIBITION, self::RETENTION];

	/**
	 * Who decides, per obligation. A refusal nobody can escalate reads as a
	 * silent failure, and the requester has a statutory clock running.
	 *
	 * @var array<string, string>
	 */
	public const DECIDES = [
		self::LEGAL_HOLD => 'the legal department that placed the hold',
		self::PROHIBITION => 'a policy administrator (docudesk-policy-admins)',
		self::RETENTION => 'the archivist responsible for the selectielijst term',
	];

	/**
	 * Why this document may not be erased, if it may not.
	 *
	 * Returns EVERY obligation rather than the first: a requester told about a
	 * legal hold, who waits for it to lift, and then meets a retention term,
	 * has been answered twice and helped once.
	 *
	 * @param array<string, mixed> $document The document's obligations.
	 *
	 * @return array<int, array<string, mixed>> The refusals, empty when it may be erased.
	 */
	public function refusals(array $document): array {
		$refusals = [];

		foreach (self::OBLIGATIONS as $obligation) {
			$standing = ($document[$obligation] ?? null);
			if ($standing === null || $standing === false || $standing === []) {
				continue;
			}

			$refusals[] = [
				'obligation' => $obligation,
				'reason' => $this->reasonFor(obligation: $obligation, standing: $standing),
				'decidedBy' => self::DECIDES[$obligation],
			];
		}

		return $refusals;
	}//end refusals()

	/**
	 * Screen a whole request: what will be erased, and what will not and why.
	 *
	 * 🔴 ONE HELD DOCUMENT DOES NOT DENY THE REQUEST. The platform refuses the
	 * whole act under a hold, which is proportionate for a retention sweep it
	 * chose to run. A person exercising a right did not choose these documents,
	 * and refusing all of them because one is held answers a request that was
	 * mostly grantable with a flat no.
	 *
	 * @param array<int, array<string, mixed>> $documents Each with its obligations.
	 *
	 * @return array{erase: array<int, string>, refused: array<int, array<string, mixed>>}
	 */
	public function screen(array $documents): array {
		$erase = [];
		$refused = [];

		foreach ($documents as $document) {
			$id = (string)($document['id'] ?? '');
			$found = $this->refusals(document: $document);

			if ($found === []) {
				$erase[] = $id;
				continue;
			}

			$refused[] = ['document' => $id, 'obligations' => $found];
		}

		return ['erase' => $erase, 'refused' => $refused];
	}//end screen()

	/**
	 * Whether this treatment may be used to erase a person.
	 *
	 * 🔴 `pseudonym` MAY NOT. The platform's pseudonym is stable and joinable
	 * ON PURPOSE, so an archival copy keeps its statistics. Applied to a
	 * subject erasure it keeps the linkage the request exists to break: every
	 * document that mentioned the person still points at the same token.
	 *
	 * @param string $treatment The anonymisation treatment.
	 *
	 * @return bool True when it genuinely removes the person.
	 */
	public function treatmentErases(string $treatment): bool {
		return in_array($treatment, ['remove', 'fixed'], true);
	}//end treatmentErases()

	/**
	 * The certificate a completed request produces.
	 *
	 * Carries the refusals as prominently as the erasures. A certificate that
	 * lists only what was erased reads as a completed request, and the
	 * documents that were refused are exactly the ones the requester has to be
	 * told about.
	 *
	 * @param array<string, mixed> $request   The request being certified.
	 * @param array<int, mixed>    $erased    What was erased, with counts.
	 * @param array<int, mixed>    $refused   What was refused, with obligations.
	 * @param array<int, string>   $republish Anything already published that now differs.
	 *
	 * @return array<string, mixed> The certificate.
	 */
	public function certificate(array $request, array $erased, array $refused, array $republish = []): array {
		return [
			'subject' => (string)($request['subject'] ?? ''),
			'ground' => (string)($request['ground'] ?? ''),
			'actor' => (string)($request['actor'] ?? ''),
			'moment' => (string)($request['moment'] ?? ''),
			'erased' => $erased,
			'erasedCount' => count($erased),
			'refused' => $refused,
			'refusedCount' => count($refused),
			// 🔴 NAMED SEPARATELY BECAUSE IT IS THE REQUESTER'S NEXT PROBLEM. A
			// copy already published elsewhere still carries the person, and an
			// erasure that does not say so leaves them believing it is done.
			'needsRepublishing' => $republish,
			'complete' => ($refused === [] && $republish === []),
		];
	}//end certificate()

	/**
	 * One obligation as a sentence.
	 *
	 * @param string $obligation The obligation.
	 * @param mixed  $standing   What the document records about it.
	 *
	 * @return string The sentence.
	 */
	private function reasonFor(string $obligation, mixed $standing): string {
		$detail = '';
		if (is_string($standing) === true && trim($standing) !== '') {
			$detail = ' ('.trim($standing).')';
		}

		if ($obligation === self::LEGAL_HOLD) {
			return 'This document is under a legal hold'.$detail.', so it is kept exactly as it is. '
				.'A hold says somebody may still need it, and that includes the name.';
		}

		if ($obligation === self::PROHIBITION) {
			return 'A publication prohibition stands over this document'.$detail.'. Erasing it would '
				.'change a record somebody has been told will not change.';
		}

		return 'This document is inside its retention term'.$detail.'. The term is a legal obligation '
			.'to keep it, and it outranks the request until it expires or is set aside.';
	}//end reasonFor()
}//end class
