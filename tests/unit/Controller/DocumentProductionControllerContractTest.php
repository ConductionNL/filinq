<?php

/**
 * Wire-contract tests for the six newly-exposed document-production endpoints
 *
 * Covers `documentProduction#layoutVersions` (GET api/page-layouts),
 * `documentProduction#editLayout` (PUT api/page-layouts),
 * `documentProduction#archivePreflight` (GET api/case-archive/preflight),
 * `documentProduction#archiveManifest` (GET api/case-archive/manifest),
 * `documentProduction#runPeriodic` (POST api/periodic-documents/run) and
 * `documentProduction#dueForReview` (GET api/documents/due-for-review).
 *
 * All six answer through one private `answer()` helper, so the interesting
 * part of each contract is what it hands its service and what it puts in the
 * body. `archiveManifest` is the one with a second obligation: it RECORDS the
 * manifest as well as returning it, and a manifest returned but not recorded
 * is a bundle nobody can check afterwards.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\DocumentProductionController;
use OCA\Filinq\Service\CaseArchiveService;
use OCA\Filinq\Service\DocumentReviewService;
use OCA\Filinq\Service\PageLayoutService;
use OCA\Filinq\Service\PeriodicDocumentService;
use OCP\AppFramework\Http;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests the wire contract of the six document-production endpoints.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class DocumentProductionControllerContractTest extends TestCase {

	/**
	 * What the services were asked, in order.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $asked = [];

	/**
	 * The layout service double.
	 *
	 * @var PageLayoutService
	 */
	private PageLayoutService $layouts;

	/**
	 * The archive service double.
	 *
	 * @var CaseArchiveService
	 */
	private CaseArchiveService $archives;

	/**
	 * The periodic-document service double.
	 *
	 * @var PeriodicDocumentService
	 */
	private PeriodicDocumentService $periodic;

	/**
	 * The review service double.
	 *
	 * @var DocumentReviewService
	 */
	private DocumentReviewService $reviews;

	/**
	 * Build the four recording services once per test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->layouts = $this->createMock(PageLayoutService::class);
		$this->layouts->method('versionsOf')->willReturnCallback(
			function (string $name): array {
				$this->asked[] = ['call' => 'versionsOf', 'name' => $name];

				return [['name' => $name, 'version' => 2], ['name' => $name, 'version' => 1]];
			}
		);
		$this->layouts->method('edit')->willReturnCallback(
			function (string $name, array $changes): array {
				$this->asked[] = ['call' => 'edit', 'name' => $name, 'changes' => $changes];

				return ['name' => $name, 'version' => 3];
			}
		);

		$this->archives = $this->createMock(CaseArchiveService::class);
		$this->archives->method('preflight')->willReturnCallback(
			function (array $domain, int $ceiling): array {
				$this->asked[] = ['call' => 'preflight', 'domain' => $domain, 'ceiling' => $ceiling];

				return ['files' => 3, 'totalSize' => 99, 'ceilingBytes' => $ceiling];
			}
		);
		$this->archives->method('manifestFor')->willReturnCallback(
			function (array $domain, int $ceiling): array {
				$this->asked[] = ['call' => 'manifestFor', 'domain' => $domain, 'ceiling' => $ceiling];

				return ['included' => ['a.pdf'], 'excluded' => []];
			}
		);
		$this->archives->method('record')->willReturnCallback(
			function (array $domain, array $manifest, int $ceiling, int $fileId = 0): array {
				$this->asked[] = ['call' => 'record', 'domain' => $domain, 'manifest' => $manifest];

				return ['uuid' => 'archive-job-1', 'status' => 'completed'];
			}
		);

		$this->periodic = $this->createMock(PeriodicDocumentService::class);
		$this->periodic->method('run')->willReturnCallback(
			function (array $schedule): array {
				$this->asked[] = ['call' => 'run', 'schedule' => $schedule];

				return ['document' => ['uuid' => 'gen-1'], 'records' => 4];
			}
		);

		$this->reviews = $this->createMock(DocumentReviewService::class);
		$this->reviews->method('listDue')->willReturnCallback(
			function (string $day): array {
				$this->asked[] = ['call' => 'listDue', 'day' => $day];

				return [['uuid' => 'doc-1'], ['uuid' => 'doc-2'], ['uuid' => 'doc-3']];
			}
		);

	}//end setUp()

	/**
	 * A controller over the recording services.
	 *
	 * @param bool $signedIn Whether somebody is logged in.
	 *
	 * @return DocumentProductionController The controller.
	 */
	private function controller(bool $signedIn = true): DocumentProductionController {
		$session = $this->createMock(IUserSession::class);
		if ($signedIn === true) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn('anna');
			$session->method('getUser')->willReturn($user);
		} else {
			$session->method('getUser')->willReturn(null);
		}

		return new DocumentProductionController(
			'filinq',
			$this->createMock(IRequest::class),
			$this->layouts,
			$this->archives,
			$this->periodic,
			$this->reviews,
			$session
		);

	}//end controller()

	/**
	 * The versions endpoint answers them under `results`, newest first.
	 *
	 * @return void
	 */
	public function testLayoutVersionsAnswersTheVersionsUnderResults(): void {
		$response = $this->controller()->layoutVersions(name: 'Gemeente, besluit');

		$this->assertSame(Http::STATUS_OK, $response->getStatus(), 'the versions answer 200');
		$this->assertSame(
			[2, 1],
			array_column($response->getData()['results'], 'version'),
			'the order the service gave is the order the wire carries'
		);
		$this->assertSame('Gemeente, besluit', ($this->asked[0]['name'] ?? ''), 'the layout name reaches the service');

	}//end testLayoutVersionsAnswersTheVersionsUnderResults()

	/**
	 * Editing a layout hands the changes through and answers the new version.
	 *
	 * @return void
	 */
	public function testEditLayoutPassesTheChangesThrough(): void {
		$changes = ['footer' => 'Gemeente Amsterdam', 'paperSize' => 'A4'];

		$response = $this->controller()->editLayout(name: 'Gemeente, besluit', changes: $changes);

		$this->assertSame(Http::STATUS_OK, $response->getStatus(), 'an edit answers 200');
		$this->assertSame($changes, ($this->asked[0]['changes'] ?? []), 'the changes reach the service unaltered');
		$this->assertSame(3, $response->getData()['version'], 'and the answer carries the version the edit produced');

	}//end testEditLayoutPassesTheChangesThrough()

	/**
	 * The preflight endpoint carries the ceiling it was given, not the default.
	 *
	 * @return void
	 */
	public function testArchivePreflightHonoursTheCeilingItWasGiven(): void {
		$response = $this->controller()->archivePreflight(
			register: 'zaken',
			schema: 'zaak',
			id: 'zaak-9',
			ceiling: 4096
		);

		$this->assertSame(Http::STATUS_OK, $response->getStatus(), 'a preflight answers 200');
		$this->assertSame(4096, ($this->asked[0]['ceiling'] ?? 0), 'the ceiling reaches the service');
		$this->assertSame(
			['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-9'],
			($this->asked[0]['domain'] ?? []),
			'and so does the whole domain'
		);

	}//end testArchivePreflightHonoursTheCeilingItWasGiven()

	/**
	 * The preflight falls back to the administered default ceiling.
	 *
	 * @return void
	 */
	public function testArchivePreflightFallsBackToTheDefaultCeiling(): void {
		$this->controller()->archivePreflight(register: 'zaken', schema: 'zaak', id: 'zaak-9');

		$this->assertSame(
			CaseArchiveService::DEFAULT_CEILING,
			($this->asked[0]['ceiling'] ?? 0),
			'a caller that names no ceiling gets the declared default, not zero'
		);

	}//end testArchivePreflightFallsBackToTheDefaultCeiling()

	/**
	 * The manifest endpoint RECORDS the manifest it returns.
	 *
	 * @return void
	 */
	public function testArchiveManifestRecordsTheManifestItReturns(): void {
		$response = $this->controller()->archiveManifest(register: 'zaken', schema: 'zaak', id: 'zaak-9');

		$this->assertSame(Http::STATUS_OK, $response->getStatus(), 'the manifest answers 200');
		$this->assertSame(
			['manifestFor', 'record'],
			array_column($this->asked, 'call'),
			'the manifest is built and then recorded: one returned but never recorded is a bundle nobody can check afterwards'
		);
		$this->assertSame(
			$response->getData(),
			($this->asked[1]['manifest'] ?? []),
			'and the manifest recorded is the very one the caller was handed'
		);

	}//end testArchiveManifestRecordsTheManifestItReturns()

	/**
	 * Running a periodic document passes the schedule through.
	 *
	 * @return void
	 */
	public function testRunPeriodicPassesTheScheduleThrough(): void {
		$schedule = ['uuid' => 'sched-1', 'viewSlug' => 'open-zaken'];

		$response = $this->controller()->runPeriodic(schedule: $schedule);

		$this->assertSame(Http::STATUS_OK, $response->getStatus(), 'a run answers 200');
		$this->assertSame($schedule, ($this->asked[0]['schedule'] ?? []), 'the schedule reaches the service unaltered');
		$this->assertSame(4, $response->getData()['records'], 'and the answer says how many records it rendered over');

	}//end testRunPeriodicPassesTheScheduleThrough()

	/**
	 * A refused run is a 400 naming the reason, not a 500.
	 *
	 * @return void
	 */
	public function testARefusedPeriodicRunIsABadRequestWithTheReason(): void {
		$periodic = $this->createMock(PeriodicDocumentService::class);
		$periodic->method('run')->willThrowException(
			new RuntimeException('The view open-zaken no longer exists.')
		);
		$this->periodic = $periodic;

		$response = $this->controller()->runPeriodic(schedule: ['uuid' => 'sched-1']);

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus(), 'a refusal is a 400');
		$this->assertSame(
			'The view open-zaken no longer exists.',
			($response->getData()['error'] ?? ''),
			'and names the view, so the person can fix the schedule'
		);

	}//end testARefusedPeriodicRunIsABadRequestWithTheReason()

	/**
	 * The due-for-review endpoint answers a total that agrees with its results.
	 *
	 * @return void
	 */
	public function testDueForReviewAnswersAMatchingTotal(): void {
		$data = $this->controller()->dueForReview(day: '2026-03-02T09:00:00+00:00')->getData();

		$this->assertSame(3, $data['total'], 'the total counts the rows');
		$this->assertSame(count($data['results']), $data['total'], 'and counts the rows in this same answer');
		$this->assertSame(
			'2026-03-02T09:00:00+00:00',
			($this->asked[0]['day'] ?? ''),
			'the day asked about reaches the service, so "due on a given day" is answerable at all'
		);

	}//end testDueForReviewAnswersAMatchingTotal()

	/**
	 * An empty day means today, and is passed as an empty string rather than guessed at here.
	 *
	 * @return void
	 */
	public function testDueForReviewDefaultsToTodayAtTheService(): void {
		$this->controller()->dueForReview();

		$this->assertSame(
			'',
			($this->asked[0]['day'] ?? 'unset'),
			'the controller does not invent a date: the service owns what "today" means'
		);

	}//end testDueForReviewDefaultsToTodayAtTheService()

	/**
	 * Every one of the six refuses an anonymous caller with 401.
	 *
	 * @return void
	 */
	public function testAllSixRefuseAnAnonymousCaller(): void {
		$controller = $this->controller(signedIn: false);

		$responses = [
			'layoutVersions' => $controller->layoutVersions(),
			'editLayout' => $controller->editLayout(),
			'archivePreflight' => $controller->archivePreflight(),
			'archiveManifest' => $controller->archiveManifest(),
			'runPeriodic' => $controller->runPeriodic(),
			'dueForReview' => $controller->dueForReview(),
		];

		foreach ($responses as $endpoint => $response) {
			$this->assertSame(
				Http::STATUS_UNAUTHORIZED,
				$response->getStatus(),
				$endpoint . ' refuses an anonymous caller'
			);
		}

		$this->assertSame([], $this->asked, 'and no service was reached on the way');

	}//end testAllSixRefuseAnAnonymousCaller()
}//end class
