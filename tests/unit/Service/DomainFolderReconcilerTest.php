<?php

/**
 * The night's report, and the half of it that is easiest to lose.
 *
 * 🔴 A REPORT WITH ONLY THE CORRECTIONS IN IT READS AS A CLEAN NIGHT.
 * REQ-CDF-02 asks for the drift corrected AND the drift that could not be, and
 * the failure mode is not a wrong number, it is a missing list: somebody opens
 * the report, sees three folders corrected and nothing else, and concludes the
 * instance is in step while a group still reaches a folder it was removed from.
 * So every test here asserts BOTH halves, and the mutation check breaks the
 * refused half specifically.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DomainDirectory;
use OCA\Filinq\Service\DomainFolderReconciler;
use OCA\Filinq\Service\DomainFolderService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * `DomainFolderReconciler`.
 *
 * @covers \OCA\Filinq\Service\DomainFolderReconciler
 */
class DomainFolderReconcilerTest extends TestCase {

	/**
	 * A directory answering with the given domains.
	 *
	 * The double is built with `onlyMethods` so it can only answer questions
	 * DomainDirectory really has: a double that invents `all()` after the real
	 * class renames it would keep every test here green against a class nobody
	 * calls any more.
	 *
	 * @param array<int, array<string, mixed>> $domains    The domains.
	 * @param bool                             $configured Whether the directory is pointed anywhere.
	 * @param string                           $reason     Why not, when it is not.
	 *
	 * @return DomainDirectory The double.
	 */
	private function directory(array $domains, bool $configured = true, string $reason = ''): DomainDirectory {
		$directory = $this->getMockBuilder(DomainDirectory::class)
			->disableOriginalConstructor()
			->onlyMethods(['all', 'owner'])
			->getMock();

		$directory->method('all')->willReturn(
			['configured' => $configured, 'reason' => $reason, 'domains' => $domains]
		);
		$directory->method('owner')->willReturn('archief');

		return $directory;
	}//end directory()

	/**
	 * A folder service answering with the given outcomes, in order.
	 *
	 * @param array<int, array<string, mixed>> $outcomes One outcome per domain.
	 * @param string|null                      $createError Why the folder could not be made, or null when it can.
	 *
	 * @return DomainFolderService The double.
	 */
	private function folders(array $outcomes, ?string $createError = null): DomainFolderService {
		$service = $this->getMockBuilder(DomainFolderService::class)
			->disableOriginalConstructor()
			->onlyMethods(['reconcile', 'pathFor', 'ensureFolder'])
			->getMock();

		$service->method('pathFor')->willReturnCallback(
			static fn (array $domain): string => 'Filinq/' . (string)($domain['id'] ?? '')
		);

		// The reconciler makes the folder before it reconciles access, because
		// reconcile() reads and writes group access on a path and never
		// creates one.
		$service->method('ensureFolder')->willReturnCallback(
			static fn (array $domain): array => [
				'created' => ($createError === null),
				'path' => 'Filinq/' . (string)($domain['id'] ?? ''),
				'error' => $createError,
			]
		);

		$calls = 0;
		$service->method('reconcile')->willReturnCallback(
			static function () use ($outcomes, &$calls): array {
				$outcome = $outcomes[$calls];
				$calls++;

				if ($outcome instanceof RuntimeException) {
					throw $outcome;
				}

				return $outcome;
			}
		);

		return $service;
	}//end folders()

	/**
	 * One outcome in DomainFolderService's shape.
	 *
	 * @param string             $state   The state.
	 * @param string             $path    The folder.
	 * @param array<int, string> $granted Groups given access.
	 * @param array<int, string> $revoked Groups whose access was removed.
	 * @param array<int, mixed>  $refused What could not be done.
	 * @param string|null        $pinned  The pin reason.
	 *
	 * @return array<string, mixed> The outcome.
	 */
	private function outcome(
		string $state,
		string $path = 'Filinq/zaak-1',
		array $granted = [],
		array $revoked = [],
		array $refused = [],
		?string $pinned = null,
	): array {
		return [
			'state' => $state,
			'path' => $path,
			'granted' => $granted,
			'revoked' => $revoked,
			'refused' => $refused,
			'pinnedReason' => $pinned,
		];
	}//end outcome()

	/**
	 * The report carries the corrections AND the refusals, apart.
	 *
	 * @return void
	 */
	public function testReportsBothHalvesOfTheNight(): void {
		$reconciler = new DomainFolderReconciler(
			$this->directory([['id' => 'zaak-1'], ['id' => 'zaak-2'], ['id' => 'zaak-3']]),
			$this->folders(
				[
					$this->outcome(DomainFolderService::STATE_CORRECTED, 'Filinq/zaak-1', ['juristen'], ['stage']),
					$this->outcome(
						DomainFolderService::STATE_REFUSED,
						'Filinq/zaak-2',
						[],
						[],
						[['group' => 'oud-team', 'action' => 'revoke', 'reason' => 'read-only mount']]
					),
					$this->outcome(DomainFolderService::STATE_IN_STEP, 'Filinq/zaak-3'),
				]
			),
			new NullLogger()
		);

		$report = $reconciler->run();

		self::assertSame(1, $report['correctedCount']);
		self::assertSame(1, $report['refusedCount']);
		self::assertSame(1, $report['inStepCount']);
		self::assertSame('zaak-1', $report['corrected'][0]['domain']);

		// The refusal names the folder, the permission and the reason, which is
		// what REQ-CDF-02's third scenario asks for by name.
		self::assertSame('zaak-2', $report['refused'][0]['domain']);
		self::assertSame('Filinq/zaak-2', $report['refused'][0]['path']);
		self::assertSame('oud-team', $report['refused'][0]['refused'][0]['group']);
		self::assertSame('revoke', $report['refused'][0]['refused'][0]['action']);
		self::assertSame('read-only mount', $report['refused'][0]['refused'][0]['reason']);
	}//end testReportsBothHalvesOfTheNight()

	/**
	 * A partly corrected folder counts as refused, never as corrected.
	 *
	 * @return void
	 */
	public function testPartlyCorrectedIsRefusedNotCorrected(): void {
		$reconciler = new DomainFolderReconciler(
			$this->directory([['id' => 'zaak-1']]),
			$this->folders(
				[
					$this->outcome(
						DomainFolderService::STATE_REFUSED,
						'Filinq/zaak-1',
						['juristen'],
						[],
						[['group' => 'oud-team', 'action' => 'revoke', 'reason' => 'refused']]
					),
				]
			),
			new NullLogger()
		);

		$report = $reconciler->run();

		self::assertSame(0, $report['correctedCount']);
		self::assertSame(1, $report['refusedCount']);

		// What DID land is still listed, so the next person can see the folder
		// is half-way rather than untouched.
		self::assertSame(['juristen'], $report['refused'][0]['granted']);
	}//end testPartlyCorrectedIsRefusedNotCorrected()

	/**
	 * A pinned folder is reported with its reason, not omitted.
	 *
	 * @return void
	 */
	public function testPinnedIsReportedWithItsReason(): void {
		$reconciler = new DomainFolderReconciler(
			$this->directory([['id' => 'zaak-9']]),
			$this->folders(
				[$this->outcome(DomainFolderService::STATE_PINNED, 'Filinq/zaak-9', [], [], [], 'permissions managed in the AD')]
			),
			new NullLogger()
		);

		$report = $reconciler->run();

		self::assertSame(1, $report['pinnedCount']);
		self::assertSame('permissions managed in the AD', $report['pinned'][0]['reason']);
		self::assertSame(0, $report['inStepCount']);
	}//end testPinnedIsReportedWithItsReason()

	/**
	 * A domain that threw is refused, not skipped.
	 *
	 * @return void
	 */
	public function testAThrowIsReportedAsARefusal(): void {
		$reconciler = new DomainFolderReconciler(
			$this->directory([['id' => 'zaak-1'], ['id' => 'zaak-2']]),
			$this->folders(
				[
					new RuntimeException('the storage went away'),
					$this->outcome(DomainFolderService::STATE_IN_STEP, 'Filinq/zaak-2'),
				]
			),
			new NullLogger()
		);

		$report = $reconciler->run();

		self::assertSame(1, $report['refusedCount']);
		self::assertSame('zaak-1', $report['refused'][0]['domain']);
		self::assertSame('the storage went away', $report['refused'][0]['refused'][0]['reason']);

		// The run carried on: a folder that threw must not cost the rest of the
		// instance its night.
		self::assertSame(1, $report['inStepCount']);
	}//end testAThrowIsReportedAsARefusal()

	/**
	 * An unconfigured directory is a skip with a reason, not an empty run.
	 *
	 * @return void
	 */
	public function testUnconfiguredIsASkipNotACleanNight(): void {
		$reconciler = new DomainFolderReconciler(
			$this->directory([], false, 'No domain register and schema are configured, so no folder was reconciled.'),
			$this->folders([]),
			new NullLogger()
		);

		$report = $reconciler->run();

		self::assertTrue($report['skipped']);
		self::assertStringContainsString('configured', $report['reason']);
		self::assertSame(0, $report['inStepCount']);
	}//end testUnconfiguredIsASkipNotACleanNight()

	/**
	 * Every domain's folder is made before its access is reconciled.
	 *
	 * reconcile() reads and writes group access on a PATH and never creates
	 * one, so a domain whose folder does not exist yet had every grant refused
	 * with a file-system message, night after night. ensureFolder() exists to
	 * answer that and had no caller anywhere in lib/.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function testTheFolderIsMadeBeforeItsAccessIsReconciled(): void {
		$folders = $this->getMockBuilder(DomainFolderService::class)
			->disableOriginalConstructor()
			->onlyMethods(['reconcile', 'pathFor', 'ensureFolder'])
			->getMock();

		$folders->method('pathFor')->willReturnCallback(
			static fn (array $domain): string => 'Filinq/' . (string)($domain['id'] ?? '')
		);

		$order = [];
		$folders->method('ensureFolder')->willReturnCallback(
			static function (array $domain) use (&$order): array {
				$order[] = 'ensureFolder:' . (string)($domain['id'] ?? '');

				return ['created' => true, 'path' => 'Filinq/' . (string)($domain['id'] ?? ''), 'error' => null];
			}
		);
		$folders->method('reconcile')->willReturnCallback(
			function (array $domain) use (&$order): array {
				$order[] = 'reconcile:' . (string)($domain['id'] ?? '');

				return $this->outcome(DomainFolderService::STATE_IN_STEP, 'Filinq/' . (string)($domain['id'] ?? ''));
			}
		);

		(new DomainFolderReconciler($this->directory([['id' => 'zaak-1']]), $folders, new NullLogger()))->run();

		self::assertSame(
			['ensureFolder:zaak-1', 'reconcile:zaak-1'],
			$order,
			'the folder must be made first: reconcile() only reads and writes access on a path that already exists'
		);
	}//end testTheFolderIsMadeBeforeItsAccessIsReconciled()

	/**
	 * A folder that could not be made is a refusal with its reason.
	 *
	 * Not a skip: a skipped domain is indistinguishable in the report from one
	 * that needed nothing, which is the reading this reconciler exists to
	 * prevent.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function testAFolderThatCouldNotBeMadeIsRefusedNotSkipped(): void {
		$reconciler = new DomainFolderReconciler(
			$this->directory([['id' => 'zaak-1']]),
			$this->folders(
				[$this->outcome(DomainFolderService::STATE_IN_STEP, 'Filinq/zaak-1')],
				'the storage is read only'
			),
			new NullLogger()
		);

		$report = $reconciler->run();

		self::assertSame(1, $report['refusedCount']);
		self::assertSame('create', $report['refused'][0]['refused'][0]['action']);
		self::assertSame('the storage is read only', $report['refused'][0]['refused'][0]['reason']);
		self::assertSame(
			0,
			$report['inStepCount'],
			'a domain with no folder must not be counted as in step'
		);
	}//end testAFolderThatCouldNotBeMadeIsRefusedNotSkipped()
}//end class
