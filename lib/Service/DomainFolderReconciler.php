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
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

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
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */
class DomainFolderReconciler {

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
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function run(): array {
		$directory = $this->directory->all();

		if ($directory['configured'] === false) {
			// Reported as a SKIP with its reason, never as a run that found
			// nothing. See DomainDirectory for why those two must not collapse.
			$this->logger->warning('filinq.domain-reconcile.skipped', ['reason' => $directory['reason']]);

			return $this->report(skipped: true, reason: (string)$directory['reason']);
		}

		$owner = $this->directory->owner();
		$corrected = [];
		$refused = [];
		$pinned = [];
		$inStep = 0;

		foreach ($directory['domains'] as $domain) {
			try {
				$outcome = $this->folderService->reconcile(domain: $domain, owner: $owner);
			} catch (Throwable $e) {
				// A throw is a refusal with the throw as its reason. Skipping
				// would make an unreached folder indistinguishable from one
				// that was already right.
				$refused[] = [
					'domain' => (string)($domain['id'] ?? ''),
					'path' => $this->folderService->pathFor(domain: $domain),
					'refused' => [['group' => '*', 'action' => 'reconcile', 'reason' => $e->getMessage()]],
					'granted' => [],
					'revoked' => [],
				];
				continue;
			}

			$entry = [
				'domain' => (string)($domain['id'] ?? ''),
				'path' => (string)$outcome['path'],
				'granted' => $outcome['granted'],
				'revoked' => $outcome['revoked'],
				'refused' => $outcome['refused'],
			];

			switch ($outcome['state']) {
				case DomainFolderService::STATE_REFUSED:
					$refused[] = $entry;
					break;
				case DomainFolderService::STATE_CORRECTED:
					$corrected[] = $entry;
					break;
				case DomainFolderService::STATE_PINNED:
					// A pin is reported WITH its reason. Omitting it would make
					// a folder nobody reconciles look like a folder that needed
					// nothing, which is the reading pinning exists to prevent.
					$pinned[] = [
						'domain' => (string)($domain['id'] ?? ''),
						'path' => (string)$outcome['path'],
						'reason' => (string)$outcome['pinnedReason'],
					];
					break;
				default:
					$inStep++;
			}
		}//end foreach

		$report = $this->report(
			corrected: $corrected,
			refused: $refused,
			pinned: $pinned,
			inStep: $inStep
		);

		$this->announce(report: $report);

		return $report;

	}//end run()

	/**
	 * Put the night in the log, loudly for the half that failed.
	 *
	 * @param array<string, mixed> $report The report.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
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
	 * @param bool                                $skipped   Whether the run never started.
	 * @param string                              $reason    Why it never started.
	 * @param array<int, array<string, mixed>>    $corrected Folders brought back into step.
	 * @param array<int, array<string, mixed>>    $refused   Folders that could not be, with the reason.
	 * @param array<int, array<string, mixed>>    $pinned    Folders left alone on purpose, with the reason.
	 * @param int                                 $inStep    How many needed nothing.
	 *
	 * @return array<string, mixed> The report.
	 *
	 * @spec exclude Shape helper; the behaviour is in run().
	 */
	private function report(
		bool $skipped = false,
		string $reason = '',
		array $corrected = [],
		array $refused = [],
		array $pinned = [],
		int $inStep = 0,
	): array {
		return [
			'skipped' => $skipped,
			'reason' => $reason,
			'corrected' => $corrected,
			'correctedCount' => count($corrected),
			'refused' => $refused,
			'refusedCount' => count($refused),
			'pinned' => $pinned,
			'pinnedCount' => count($pinned),
			'inStepCount' => $inStep,
		];

	}//end report()
}//end class
