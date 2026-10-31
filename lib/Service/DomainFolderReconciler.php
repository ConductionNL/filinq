<?php

/**
 * A night's reconciliation, and what it could not do.
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
 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Kernel\Contracts\DomainFolderReconcilerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Reconciles every domain folder and reports both halves of the night.
 *
 * 🔴 THE REPORT HAS TWO HALVES AND NEITHER MAY BE DROPPED. REQ-CDF-02 asks the
 * nightly job to report the drift it corrected AND the drift it could not, and
 * a report that carries only the first is the one somebody reads as a clean
 * night. So `corrected` and `refused` are separate lists, both always present,
 * and the summary counts them apart.
 *
 * 🔴 A DOMAIN THAT THREW IS REFUSED, NOT SKIPPED. A reconciler that swallows a
 * throw and moves to the next domain reports a shorter run rather than a
 * failure, and the folder it never reached looks exactly like a folder that was
 * already right.
 *
 * 🔑 THE JOB REPORTS, IT DOES NOT DECIDE. Nothing here retries, backs off or
 * escalates: what to do about a folder that refuses a revoke for the fourth
 * night running is a decision a person makes with the report in front of them.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction Development Team <dev@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
 */
class DomainFolderReconciler implements DomainFolderReconcilerInterface {

	/**
	 * Collaborators.
	 *
	 * @param DomainDirectory     $directory     Where the domains come from.
	 * @param DomainFolderService $folderService Reconciles one folder.
	 * @param LoggerInterface     $logger        Structured logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DomainDirectory $directory,
		private readonly DomainFolderService $folderService,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Reconcile every domain folder once.
	 *
	 * @return array<string, mixed> The report: what was corrected, what was refused, what was pinned, what was already right.
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function run(): array {
		$directory = $this->directory->all();

		if ($directory['configured'] === false) {
			// Reported as a SKIP with its reason, never as a run that found
			// nothing. See DomainDirectory for why those two must not collapse.
			$this->logger->warning('filinq.domain-reconcile.skipped', ['reason' => $directory['reason']]);

			return $this->report(night: ['skipped' => true, 'reason' => (string)$directory['reason']]);
		}

		$owner = $this->directory->owner();
		$night = ['corrected' => [], 'refused' => [], 'pinned' => [], 'inStep' => 0];
		foreach ($directory['domains'] as $domain) {
			$night = $this->reconcileDomain(night: $night, target: ['domain' => $domain, 'owner' => $owner]);
		}

		$report = $this->report(night: $night);
		$this->announce(report: $report);

		return $report;

	}//end run()

	/**
	 * Make one domain's folder, reconcile its access and add the result to the night.
	 *
	 * @param array<string, mixed>                                       $night  The night so far: corrected, refused, pinned, inStep.
	 * @param array{domain: array<string, mixed>, owner: string|null}    $target The domain and the folder owner.
	 *
	 * @return array<string, mixed> The night with this domain added.
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	private function reconcileDomain(array $night, array $target): array {
		$domain   = $target['domain'];
		$domainId = (string)($domain['id'] ?? '');

		// 🔴 THE FOLDER IS MADE BEFORE ITS ACCESS IS RECONCILED. reconcile()
		// reads and writes group access on a PATH; it never creates one. A
		// domain whose folder does not exist yet therefore had every grant
		// refused, night after night, and the report said "refused" with a
		// file-system message rather than "there is no folder here".
		$made = $this->folderService->ensureFolder(domain: $domain, owner: $target['owner']);
		if ($made['error'] !== null) {
			// A folder that could not be made is a refusal with its reason,
			// not a skip. Skipping would make it indistinguishable from a
			// folder that needed nothing.
			$night['refused'][] = [
				'domain' => $domainId,
				'path' => (string)$made['path'],
				'refused' => [['group' => '*', 'action' => 'create', 'reason' => (string)$made['error']]],
				'granted' => [],
				'revoked' => [],
			];
			return $night;
		}

		try {
			$outcome = $this->folderService->reconcile(domain: $domain, owner: $target['owner']);
		} catch (Throwable $e) {
			// A throw is a refusal with the throw as its reason. Skipping
			// would make an unreached folder indistinguishable from one
			// that was already right.
			$night['refused'][] = [
				'domain' => $domainId,
				'path' => $this->folderService->pathFor(domain: $domain),
				'refused' => [['group' => '*', 'action' => 'reconcile', 'reason' => $e->getMessage()]],
				'granted' => [],
				'revoked' => [],
			];
			return $night;
		}

		return $this->recordOutcome(night: $night, outcome: ['domain' => $domainId] + $outcome);

	}//end reconcileDomain()

	/**
	 * File one folder's outcome under corrected, refused, pinned or in step.
	 *
	 * @param array<string, mixed> $night   The night so far.
	 * @param array<string, mixed> $outcome DomainFolderService's outcome plus the domain id.
	 *
	 * @return array<string, mixed> The night with the outcome filed.
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	private function recordOutcome(array $night, array $outcome): array {
		$entry = [
			'domain' => $outcome['domain'],
			'path' => (string)$outcome['path'],
			'granted' => $outcome['granted'],
			'revoked' => $outcome['revoked'],
			'refused' => $outcome['refused'],
		];

		switch ($outcome['state']) {
			case DomainFolderService::STATE_REFUSED:
				$night['refused'][] = $entry;
				break;
			case DomainFolderService::STATE_CORRECTED:
				$night['corrected'][] = $entry;
				break;
			case DomainFolderService::STATE_PINNED:
				// A pin is reported WITH its reason. Omitting it would make
				// a folder nobody reconciles look like a folder that needed
				// nothing, which is the reading pinning exists to prevent.
				$night['pinned'][] = [
					'domain' => $outcome['domain'],
					'path' => (string)$outcome['path'],
					'reason' => (string)$outcome['pinnedReason'],
				];
				break;
			default:
				$night['inStep']++;
		}

		return $night;

	}//end recordOutcome()

	/**
	 * Put the night in the log, loudly for the half that failed.
	 *
	 * @param array<string, mixed> $report The report.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	private function announce(array $report): void {
		$this->logger->info(
			'filinq.domain-reconcile.finished',
			[
				'corrected' => $report['correctedCount'],
				'refused' => $report['refusedCount'],
				'pinned' => $report['pinnedCount'],
				'inStep' => $report['inStepCount'],
			]
		);

		foreach ($report['refused'] as $entry) {
			// Each refusal gets its own line naming the folder, the permission
			// and the reason, because a count alone cannot be acted on.
			$this->logger->warning(
				'filinq.domain-reconcile.refused',
				[
					'domain' => $entry['domain'],
					'path' => $entry['path'],
					'refused' => $entry['refused'],
					'granted' => $entry['granted'],
					'revoked' => $entry['revoked'],
				]
			);
		}

	}//end announce()

	/**
	 * One report, in the one shape every caller reads.
	 *
	 * @param array<string, mixed> $night skipped and reason when the run never started; otherwise the
	 *                                    corrected, refused and pinned folders and how many were in step.
	 *
	 * @return array<string, mixed> The report.
	 *
	 * @spec exclude Shape helper; the behaviour is in run().
	 */
	private function report(array $night): array {
		$corrected = ($night['corrected'] ?? []);
		$refused   = ($night['refused'] ?? []);
		$pinned    = ($night['pinned'] ?? []);

		return [
			'skipped' => ($night['skipped'] ?? false),
			'reason' => ($night['reason'] ?? ''),
			'corrected' => $corrected,
			'correctedCount' => count($corrected),
			'refused' => $refused,
			'refusedCount' => count($refused),
			'pinned' => $pinned,
			'pinnedCount' => count($pinned),
			'inStepCount' => ($night['inStep'] ?? 0),
		];

	}//end report()
}//end class
