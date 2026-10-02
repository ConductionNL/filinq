<?php

/**
 * The folder behind a domain, and keeping it in step.
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

use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Creates a domain's folder and reconciles who may reach it.
 *
 * 🔴 THE HARD REQUIREMENT IS NOT THE CORRECTING, IT IS THE NOT CLAIMING.
 * REQ-CDF-02's third scenario is a mount that REFUSES a permission change: the
 * job must report the folder, the permission and the reason, and must not claim
 * success. A reconciler that swallows a refusal and reports "reconciled" is
 * worse than none, because somebody reads that line and stops looking, while a
 * group that should have lost access still has it.
 *
 * So every outcome here is one of three, never two: corrected, already right,
 * or REFUSED WITH A REASON. There is no fourth bucket for "tried something".
 *
 * 🔑 A PIN IS A DECISION SOMEBODY RECORDED, NOT A SKIP. A folder pinned out of
 * reconciliation is reported as pinned, with the reason, rather than quietly
 * omitted: the whole point of pinning is that the next person reads why.
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */
class DomainFolderService {

	/**
	 * Where a domain's folder lives.
	 */
	public const ROOT = 'Filinq';

	/**
	 * The key a domain carries to opt out of reconciliation.
	 */
	public const PIN_KEY = 'reconciliationPinnedReason';

	/**
	 * The folder matched what the domain declares.
	 */
	public const STATE_IN_STEP = 'inStep';

	/**
	 * The folder differed and was corrected.
	 */
	public const STATE_CORRECTED = 'corrected';

	/**
	 * The folder differed and could not be corrected.
	 */
	public const STATE_REFUSED = 'refused';

	/**
	 * The folder was left alone because somebody pinned it, with a reason.
	 */
	public const STATE_PINNED = 'pinned';

	/**
	 * Collaborators.
	 *
	 * @param IRootFolder         $rootFolder Where folders are made.
	 * @param DomainFolderGateway $gateway    Reads and writes the folder's group access.
	 * @param LoggerInterface     $logger     Structured logger.
	 */
	public function __construct(
		private readonly IRootFolder $rootFolder,
		private readonly DomainFolderGateway $gateway,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * The folder path a domain owns.
	 *
	 * @param array<string, mixed> $domain The domain.
	 *
	 * @return string The path.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function pathFor(array $domain): string {
		$id = trim((string)($domain['id'] ?? $domain['uuid'] ?? ''));

		return self::ROOT . '/' . $id;
	}//end pathFor()

	/**
	 * Create the domain's folder if it is not there yet.
	 *
	 * @param array<string, mixed> $domain The domain.
	 * @param string               $owner  The user whose storage holds it.
	 *
	 * @return array{created: bool, path: string, error: ?string} What happened.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function ensureFolder(array $domain, string $owner): array {
		$path = $this->pathFor(domain: $domain);

		try {
			$userFolder = $this->rootFolder->getUserFolder($owner);

			if ($userFolder->nodeExists($path) === true) {
				return ['created' => false, 'path' => $path, 'error' => null];
			}

			$userFolder->newFolder($path);

			return ['created' => true, 'path' => $path, 'error' => null];
		} catch (Throwable $e) {
			// 🔑 A FOLDER THAT COULD NOT BE MADE IS REPORTED, NOT THROWN. The
			// caller is usually a domain create, and failing that write because
			// the file storage refused would lose the domain over something the
			// author cannot act on. The domain exists; its folder is named as
			// missing and the reconciler picks it up.
			$this->logger->warning(
				'filinq.domain-folder.create-refused',
				['path' => $path, 'owner' => $owner, 'error' => $e->getMessage()]
			);

			return ['created' => false, 'path' => $path, 'error' => $e->getMessage()];
		}
	}//end ensureFolder()

	/**
	 * Bring the folder's group access back to what the domain declares.
	 *
	 * @param array<string, mixed> $domain The domain, carrying `groups`.
	 * @param string               $owner  The user whose storage holds it.
	 *
	 * @return array{state: string, path: string, granted: array<int, string>, revoked: array<int, string>,
	 *               refused: array<int, array{group: string, action: string, reason: string}>,
	 *               pinnedReason: ?string} The outcome.
	 *
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity) Each branch here is one of the
	 * outcomes REQ-CDF-02 names: pinned, unreadable, granted, revoked, refused,
	 * already in step. Collapsing any two of them is exactly the "partly
	 * reconciled reported as reconciled" this method exists to refuse.
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity) Same reason: the paths are the
	 * outcome matrix, not nesting that could be flattened.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function reconcile(array $domain, string $owner): array {
		$path = $this->pathFor(domain: $domain);

		$pinned = trim((string)($domain[self::PIN_KEY] ?? ''));
		if ($pinned !== '') {
			// Reported as pinned WITH the reason, not omitted. Somebody decided
			// this; the next person reading the report should see the decision
			// rather than a gap.
			return $this->outcome(state: self::STATE_PINNED, path: $path, pinnedReason: $pinned);
		}

		$declared = $this->declaredGroups(domain: $domain);

		try {
			$observed = $this->gateway->groupsWithAccess(path: $path, owner: $owner);
		} catch (Throwable $e) {
			return $this->outcome(
				state: self::STATE_REFUSED,
				path: $path,
				refused: [['group' => '*', 'action' => 'read', 'reason' => $e->getMessage()]]
			);
		}

		$granted = [];
		$revoked = [];
		$refused = [];

		foreach (array_diff($declared, $observed) as $group) {
			try {
				$this->gateway->grant(path: $path, owner: $owner, group: (string)$group);
				$granted[] = (string)$group;
			} catch (Throwable $e) {
				$refused[] = ['group' => (string)$group, 'action' => 'grant', 'reason' => $e->getMessage()];
			}
		}

		foreach (array_diff($observed, $declared) as $group) {
			try {
				$this->gateway->revoke(path: $path, owner: $owner, group: (string)$group);
				$revoked[] = (string)$group;
			} catch (Throwable $e) {
				// 🔴 A REVOKE THAT FAILS IS THE ONE THAT MATTERS. A grant that
				// fails leaves somebody without access, which they will report.
				// A revoke that fails leaves somebody WITH access nobody meant
				// them to have, and nobody reports that.
				$refused[] = ['group' => (string)$group, 'action' => 'revoke', 'reason' => $e->getMessage()];
			}
		}

		if ($refused !== []) {
			// Refused wins over corrected even when some corrections landed.
			// "Partly reconciled" reported as reconciled is the line somebody
			// reads and stops looking at.
			return $this->outcome(
				state: self::STATE_REFUSED,
				path: $path,
				granted: $granted,
				revoked: $revoked,
				refused: $refused
			);
		}

		if ($granted === [] && $revoked === []) {
			return $this->outcome(state: self::STATE_IN_STEP, path: $path);
		}

		return $this->outcome(
			state: self::STATE_CORRECTED,
			path: $path,
			granted: $granted,
			revoked: $revoked
		);
	}//end reconcile()

	/**
	 * The groups a domain declares, as a clean list.
	 *
	 * @param array<string, mixed> $domain The domain.
	 *
	 * @return array<int, string> The group ids.
	 */
	private function declaredGroups(array $domain): array {
		$groups = [];
		foreach ((array)($domain['groups'] ?? []) as $group) {
			$candidate = $group;
			if (is_array($group) === true) {
				$candidate = ($group['id'] ?? '');
			}

			$id = trim((string)$candidate);
			if ($id !== '') {
				$groups[] = $id;
			}
		}

		return array_values(array_unique($groups));
	}//end declaredGroups()

	/**
	 * One outcome, in the one shape every caller reads.
	 *
	 * @param string                                                        $state        One of the STATE_ constants.
	 * @param string                                                        $path         The folder.
	 * @param array<int, string>                                            $granted      Groups given access.
	 * @param array<int, string>                                            $revoked      Groups whose access was removed.
	 * @param array<int, array{group: string, action: string, reason: string}> $refused    What could not be done, and why.
	 * @param string|null                                                   $pinnedReason Why it was pinned out.
	 *
	 * @return array<string, mixed> The outcome.
	 */
	private function outcome(
		string $state,
		string $path,
		array $granted = [],
		array $revoked = [],
		array $refused = [],
		?string $pinnedReason = null,
	): array {
		return [
			'state' => $state,
			'path' => $path,
			'granted' => $granted,
			'revoked' => $revoked,
			'refused' => $refused,
			'pinnedReason' => $pinnedReason,
		];
	}//end outcome()
}//end class
