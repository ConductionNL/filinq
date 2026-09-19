<?php

/**
 * A download that waits for the terms to be accepted, and records it.
 *
 * 🔴 AN UNRECORDED ACCEPTANCE IS THE SAME AS NONE, SO A WRITE THAT FAILS STOPS
 * THE DOWNLOAD. The convenient order is to serve the file and record the
 * acceptance afterwards, best effort, because the reader is standing there and
 * the store is usually up. What that produces is a file out of the building
 * with nothing saying anybody agreed to anything, which is precisely the
 * evidence the agreement exists to create. So the acceptance is written first
 * and the file is served only if the write returned.
 *
 * 🔴 A NEW VERSION ASKS AGAIN. Acceptance of an older text is acceptance of a
 * different text. Comparing on the agreement's identity rather than its version
 * would let the terms be rewritten under everybody who already agreed.
 *
 * 🔴 "NO TERMS WERE DECLARED" AND "I COULD NOT READ THE TERMS" ARE DIFFERENT
 * ANSWERS, AND CONFLATING THEM IS HOW THIS GATE FAILED OPEN. Measured on a live
 * instance on 2026-09-19: a document with a declared agreement nobody had
 * accepted and a document nobody had ever declared anything about both answered
 * `{"mayDownload":true,"gated":false}`, because the store swallowed its read
 * failure into an empty list and the empty list means ungated. The rule below
 * was right the whole time; it was never asked about a real agreement. The
 * three answers are now distinct: no terms is `gated:false`, terms not accepted
 * is `gated:true` with `not_accepted`, and a store that cannot be read is
 * `gated:true` with `not_known`. Only the first serves the file.
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

use OCA\Filinq\Exception\AgreementStoreUnreadableException;
use OCP\IL10N;
use RuntimeException;

/**
 * Decides whether a gated file may be served, and records the acceptance.
 */
class DownloadAgreementGate {

	/**
	 * Nobody has accepted anything for this file.
	 *
	 * @var string
	 */
	public const NOT_ACCEPTED = 'not_accepted';

	/**
	 * An older version was accepted, and the terms have changed since.
	 *
	 * @var string
	 */
	public const VERSION_MOVED_ON = 'version_moved_on';

	/**
	 * The acceptance could not be written, so nothing is served.
	 *
	 * @var string
	 */
	public const NOT_RECORDED = 'not_recorded';

	/**
	 * The conditions could not be read, so it is not known whether any apply.
	 *
	 * @var string
	 */
	public const NOT_KNOWN = 'not_known';

	/**
	 * Constructor.
	 *
	 * @param DownloadAgreementRepository $agreements Where the terms and the acceptances live.
	 * @param IL10N|null                  $l10n       The reader's language, when there is one. The
	 *                                                reader of a gated download is often a member
	 *                                                of the public rather than an operator, so
	 *                                                these strings are the ones that most need a
	 *                                                translation.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DownloadAgreementRepository $agreements,
		private readonly ?IL10N $l10n = null,
	) {

	}//end __construct()

	/**
	 * Whether this person may have this file yet.
	 *
	 * @param string $document The document being downloaded.
	 * @param string $person   Who is asking.
	 *
	 * @return array<string, mixed> `mayDownload`, and the agreement to show when not.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function check(string $document, string $person): array {
		try {
			$agreement = $this->agreements->forDocument(document: $document);
		} catch (AgreementStoreUnreadableException $e) {
			return $this->cannotTell(error: $e);
		}

		if ($agreement === null) {
			// Nothing gates this file. An ungated file downloads as it always
			// did: the gate applies where terms were declared, not everywhere.
			// This is reached only after a read that SUCCEEDED and found no
			// terms; a read that failed raises and is refused above.
			return ['mayDownload' => true, 'gated' => false];
		}

		$version = trim((string)($agreement['version'] ?? ''));
		try {
			$accepted = $this->agreements->acceptanceOf(document: $document, person: $person);
		} catch (AgreementStoreUnreadableException $e) {
			return $this->cannotTell(error: $e);
		}

		$acceptedVersion = '';
		if ($accepted !== null) {
			$acceptedVersion = trim((string)($accepted['acceptedVersion'] ?? ''));
		}

		if ($acceptedVersion === '') {
			return [
				'mayDownload' => false,
				'gated' => true,
				'reason' => self::NOT_ACCEPTED,
				'message' => $this->say(message: 'Read the conditions and accept them before this file is downloaded.'),
				'agreement' => $this->shownTerms(agreement: $agreement),
			];
		}

		if ($acceptedVersion !== $version) {
			return [
				'mayDownload' => false,
				'gated' => true,
				'reason' => self::VERSION_MOVED_ON,
				'message' => sprintf(
					$this->say(
						message: 'The conditions have changed since you accepted them. You accepted version '
							.'%s and these are version %s. Read them and accept again.'
					),
					$acceptedVersion,
					$version
				),
				'agreement' => $this->shownTerms(agreement: $agreement),
			];
		}

		return [
			'mayDownload' => true,
			'gated' => true,
			'acceptedVersion' => $acceptedVersion,
		];

	}//end check()

	/**
	 * Record an acceptance, and say whether the file may now be served.
	 *
	 * @param string $document The document.
	 * @param string $person   Who accepted.
	 * @param string $version  Which version they were shown.
	 *
	 * @return array<string, mixed> `mayDownload`, and why not when it is false.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function accept(string $document, string $person, string $version): array {
		try {
			$agreement = $this->agreements->forDocument(document: $document);
		} catch (AgreementStoreUnreadableException $e) {
			return $this->cannotTell(error: $e);
		}

		if ($agreement === null) {
			return ['mayDownload' => true, 'gated' => false];
		}

		$current = trim((string)($agreement['version'] ?? ''));
		if (trim($version) !== $current) {
			// They accepted a text that is no longer the one in force, which
			// happens when the terms change while somebody has the dialog open.
			return [
				'mayDownload' => false,
				'gated' => true,
				'reason' => self::VERSION_MOVED_ON,
				'message' => $this->say(
					message: 'The conditions changed while you were reading them. Read them again and accept.'
				),
				'agreement' => $this->shownTerms(agreement: $agreement),
			];
		}

		try {
			$this->agreements->recordAcceptance(
				document: $document,
				person: $person,
				version: $current,
				text: (string)($agreement['text'] ?? '')
			);
		} catch (RuntimeException $e) {
			return [
				'mayDownload' => false,
				'gated' => true,
				'reason' => self::NOT_RECORDED,
				'message' => $this->say(
					message: 'Your acceptance could not be recorded, so the file was not downloaded. '
						.'Try again in a moment.'
				),
				'error' => $e->getMessage(),
			];
		}

		return ['mayDownload' => true, 'gated' => true, 'acceptedVersion' => $current];

	}//end accept()

	/**
	 * The answer when the store could not be read at all.
	 *
	 * 🔴 NOT KNOWING IS NOT PERMISSION. The store used to answer a failed read
	 * with an empty result, which reads as "no terms were declared", which
	 * serves the file. The reader is told the conditions could not be read
	 * rather than being handed something nobody agreed to.
	 *
	 * @param AgreementStoreUnreadableException $error What went wrong.
	 *
	 * @return array<string, mixed> The refusal.
	 *
	 * @spec exclude Refusal helper behind check() and accept().
	 */
	private function cannotTell(AgreementStoreUnreadableException $error): array {
		return [
			'mayDownload' => false,
			'gated' => true,
			'reason' => self::NOT_KNOWN,
			'message' => $this->say(
				message: 'The conditions on this file could not be read, so it was not downloaded. '
					.'Try again in a moment.'
			),
			'error' => $error->getMessage(),
		];

	}//end cannotTell()

	/**
	 * One message in the reader's language, or in English when there is none.
	 *
	 * @param string $message The English source string.
	 *
	 * @return string The message.
	 *
	 * @spec exclude Localisation helper behind check() and accept().
	 */
	private function say(string $message): string {
		if ($this->l10n === null) {
			return $message;
		}

		return $this->l10n->t($message);

	}//end say()

	/**
	 * The agreement as a reader sees it.
	 *
	 * @param array<string, mixed> $agreement The stored agreement.
	 *
	 * @return array<string, mixed> The text, its version and its language.
	 *
	 * @spec exclude Presentation helper behind check() and accept().
	 */
	private function shownTerms(array $agreement): array {
		return [
			'version' => (string)($agreement['version'] ?? ''),
			'text' => (string)($agreement['text'] ?? ''),
			'locale' => (string)($agreement['locale'] ?? ''),
		];

	}//end shownTerms()
}//end class
