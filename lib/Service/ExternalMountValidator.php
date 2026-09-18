<?php

/**
 * Checking a store before a document is put on it.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 *
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Throwable;

/**
 * Names what a mount cannot do, before a domain is put on it.
 *
 * 🔴 IT NAMES THE REQUIREMENT, NOT THE CAPABILITY. REQ-CDF-06 asks setup to
 * "name the reconciliation requirement it cannot meet", and a report reading
 * "supportsPerGroupPermissions: false" tells the administrator a property name,
 * not a consequence. So every finding carries the sentence somebody has to act
 * on: which promise filinq will stop keeping on this mount.
 *
 * 🔴 "COULD NOT BE FOUND OUT" IS ITS OWN FINDING, LOUDER THAN "NO". A mount
 * that answers no is one filinq knows how to describe; a mount that answers
 * nothing is one where the first sign of a problem is a group that still has
 * access to a folder it was removed from. Folding null into either true or
 * false loses exactly that, so `unknown` is reported apart from `cannot`.
 *
 * 🔑 IT REFUSES NOTHING AND CONFIGURES NOTHING. A validator that blocked the
 * mount would be a policy decision an administrator may legitimately overrule
 * for a store whose permissions are managed outside Nextcloud. What it must not
 * do is let that decision be made without the consequence in front of them, so
 * it always returns the findings and lets setup decide.
 *
 * 🔑 THE UPLOAD POLICY IS READ, NOT ASSUMED. A mount that bypasses filinq on
 * write matters only when there IS a policy to bypass; on an instance with no
 * policy declared, reporting an unenforceable one would be a warning about a
 * rule nobody wrote.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction Development Team <dev@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */
class ExternalMountValidator {

	/**
	 * A requirement the mount cannot meet.
	 *
	 * @var string
	 */
	public const CANNOT = 'cannot';

	/**
	 * A requirement nobody could find out about.
	 *
	 * @var string
	 */
	public const UNKNOWN = 'unknown';

	/**
	 * Collaborators.
	 *
	 * @param MountCapabilityProbe $probe        Asks the mount what it can do.
	 * @param UploadPolicyService  $uploadPolicy The policy whose enforcement is at stake.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly MountCapabilityProbe $probe,
		private readonly UploadPolicyService $uploadPolicy,
	) {

	}//end __construct()

	/**
	 * Validate the store a domain would live on.
	 *
	 * @param string $path The folder the domain would live in.
	 *
	 * @return array{path: string, ok: bool, findings: array<int, array{requirement: string, verdict: string, message: string}>} What it can and cannot keep.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function validate(string $path): array {
		$findings = [];

		$this->consider(
			findings: $findings,
			answer: $this->probe->isWritable(path: $path),
			requirement: 'store a document at all',
			cannot: 'This store is read-only, so no document can be filed in this domain.',
			unknown: 'Whether this store accepts a write could not be found out, so a document may fail to file with no warning.'
		);

		$this->consider(
			findings: $findings,
			answer: $this->probe->supportsPerGroupPermissions(path: $path),
			requirement: 'reconcile the folder to the domain',
			cannot: 'This store cannot hold a permission per group, so the nightly reconciliation will report a refusal every night and a group removed from the domain keeps its access.',
			unknown: 'Whether this store holds a permission per group could not be found out, so reconciliation may silently do nothing.'
		);

		$this->consider(
			findings: $findings,
			answer: $this->probe->supportsVersions(path: $path),
			requirement: 'keep a version history',
			cannot: 'This store keeps no version history, so a document corrected here loses what it said before.',
			unknown: 'Whether this store keeps a version history could not be found out.'
		);

		if ($this->hasUploadPolicy() === true) {
			$this->consider(
				findings: $findings,
				answer: $this->probe->writesPassThroughFilinq(path: $path),
				requirement: 'enforce the upload policy',
				cannot: 'Files can reach this store without passing filinq, so the upload policy cannot be enforced on everything that lands here.',
				unknown: 'Whether every write to this store passes filinq could not be found out, so the upload policy may be enforced on only some of them.'
			);
		}

		return ['path' => $path, 'ok' => ($findings === []), 'findings' => $findings];

	}//end validate()

	/**
	 * Whether a policy is declared at all.
	 *
	 * @return bool True when there is a policy whose enforcement can be at stake.
	 *
	 * @spec exclude Reads UploadPolicyService; the behaviour under test is validate().
	 */
	private function hasUploadPolicy(): bool {
		try {
			return $this->uploadPolicy->activePolicy() !== null;
		} catch (Throwable $e) {
			unset($e);

			// A policy that could not be read is treated as present. Treating
			// it as absent would drop the enforcement finding entirely, which
			// is the one silence this whole check exists to break.
			return true;
		}

	}//end hasUploadPolicy()

	/**
	 * Turn one three-valued answer into nothing, a cannot, or an unknown.
	 *
	 * @param array<int, array{requirement: string, verdict: string, message: string}> $findings    The findings so far, added to in place.
	 * @param bool|null                                                                $answer      What the probe said.
	 * @param string                                                                   $requirement The promise at stake.
	 * @param string                                                                   $cannot      What to say when the mount cannot.
	 * @param string                                                                   $unknown     What to say when nobody could find out.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	private function consider(
		array &$findings,
		?bool $answer,
		string $requirement,
		string $cannot,
		string $unknown,
	): void {
		if ($answer === true) {
			return;
		}

		if ($answer === null) {
			$findings[] = [
				'requirement' => $requirement,
				'verdict' => self::UNKNOWN,
				'message' => $unknown,
			];

			return;
		}

		$findings[] = [
			'requirement' => $requirement,
			'verdict' => self::CANNOT,
			'message' => $cannot,
		];

	}//end consider()
}//end class
