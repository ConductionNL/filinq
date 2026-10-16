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
 * @spec openspec/specs/document-creatie-sjablonen/spec.md
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

		$this->layouts->method('activeLayouts')->willReturnCallback(
			function (): array {
				$this->asked[] = ['call' => 'activeLayouts'];

				return [['name' => 'Gemeente, besluit', 'layoutVersion' => 2]];
			}
		);
		$this->layouts->method('create')->willReturnCallback(
			function (string $name, array $fields): array {
				$this->asked[] = ['call' => 'create', 'name' => $name, 'fields' => $fields];

				return ['name' => $name, 'layoutVersion' => 1];
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
		// The ceiling rule lives in the service (CaseArchiveServiceTest): an
		// administered 8192 bytes that a caller may lower but not raise.
		$this->archives->method('ceiling')->willReturnCallback(
			static fn (int $requested = 0): int => ($requested > 0 && $requested < 8192) ? $requested : 8192
		);
		$this->archives->method('build')->willReturnCallback(
			function (array $domain, int $requestedCeiling = 0): array {
				$this->asked[] = ['call' => 'build', 'domain' => $domain, 'ceiling' => $requestedCeiling];

				return ['included' => ['a.pdf'], 'excluded' => [], 'archive' => ['fileId' => 9001]];
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
	 * Without a name the versions endpoint lists every active layout, for the
	 * admin list.
	 *
	 * @return void
	 */
	public function testLayoutVersionsWithoutANameListsTheActiveLayouts(): void {
		$response = $this->controller()->layoutVersions();

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame('activeLayouts', ($this->asked[0]['call'] ?? ''));
		$this->assertSame(['Gemeente, besluit'], array_column($response->getData()['results'], 'name'));

	}//end testLayoutVersionsWithoutANameListsTheActiveLayouts()

	/**
	 * Creating a layout hands the fields through and answers version 1.
	 *
	 * @return void
	 */
	public function testCreateLayoutPassesTheFieldsThrough(): void {
		$fields = ['paperSize' => 'A4', 'header' => 'Gemeente'];

		$response = $this->controller()->createLayout(name: 'Gemeente, brief', fields: $fields);

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['call' => 'create', 'name' => 'Gemeente, brief', 'fields' => $fields], ($this->asked[0] ?? []));
		$this->assertSame(1, $response->getData()['layoutVersion']);

	}//end testCreateLayoutPassesTheFieldsThrough()

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
		$this->assertSame(4096, ($this->asked[0]['ceiling'] ?? 0), 'a lower ceiling reaches the service');
		$this->assertSame(
			['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-9'],
			($this->asked[0]['domain'] ?? []),
			'and so does the whole domain'
		);

	}//end testArchivePreflightHonoursTheCeilingItWasGiven()

	/**
	 * The preflight falls back to the administered ceiling, and a caller
	 * cannot raise it (#1210).
	 *
	 * @return void
	 */
	public function testArchivePreflightFallsBackToTheDefaultCeiling(): void {
		$this->controller()->archivePreflight(register: 'zaken', schema: 'zaak', id: 'zaak-9');
		$this->controller()->archivePreflight(register: 'zaken', schema: 'zaak', id: 'zaak-9', ceiling: 999999999);

		$this->assertSame(8192, ($this->asked[0]['ceiling'] ?? 0), 'a caller that names no ceiling gets the administered one, not zero');
		$this->assertSame(8192, ($this->asked[1]['ceiling'] ?? 0), 'and a caller cannot raise it');

	}//end testArchivePreflightFallsBackToTheDefaultCeiling()

	/**
	 * The manifest endpoint BUILDS the bundle (archive, manifest and job in
	 * one service call, CaseArchiveServiceTest covers the three) and answers
	 * with what the build returned, including where the archive went (#1210).
	 *
	 * @return void
	 */
	public function testArchiveManifestRecordsTheManifestItReturns(): void {
		$response = $this->controller()->archiveManifest(register: 'zaken', schema: 'zaak', id: 'zaak-9', ceiling: 2048);

		$this->assertSame(Http::STATUS_OK, $response->getStatus(), 'the manifest answers 200');
		$this->assertSame(
			['build'],
			array_column($this->asked, 'call'),
			'the controller asks for the whole bundle, not a manifest it records apart from any archive'
		);
		$this->assertSame(['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-9'], ($this->asked[0]['domain'] ?? []));
		$this->assertSame(2048, ($this->asked[0]['ceiling'] ?? 0), 'a lower ceiling is passed on');
		$this->assertSame(9001, ($response->getData()['archive']['fileId'] ?? 0), 'and the answer names the archive');

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
