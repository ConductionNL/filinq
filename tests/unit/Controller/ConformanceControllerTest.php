<?php

/**
 * The conformance routes: a check from the document stores a report the
 * next read returns, a file the caller cannot open is a plain 404, a
 * missing validator is a 503 that says so, and a non-PDF is refused.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-4.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\ConformanceController;
use OCA\Filinq\Kernel\Contracts\ConformanceServiceInterface;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\VeraPdf\ConformanceGuidance;
use OCA\Filinq\Service\VeraPdf\ConformanceReportRepository;
use OCA\Filinq\Service\VeraPdf\ConformanceService;
use OCA\Filinq\Service\VeraPdf\VeraPdfService;
use OCA\Filinq\Tests\Unit\Service\SubjectErasure\SubjectErasureDoubles;
use OCA\Filinq\Tests\Unit\Service\VeraPdf\VeraPdfDoubles;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

class ConformanceControllerTest extends TestCase {
	use SubjectErasureDoubles;
	use VeraPdfDoubles;

	/**
	 * The caller's own files: file id => [name, mime, fixture].
	 *
	 * @var array<int, array{0: string, 1: string, 2: string}>
	 */
	private array $own = [
		42 => ['brief.pdf', 'application/pdf', 'pdfa3b-imported-unembedded-font'],
		43 => ['notitie.txt', 'text/plain', ''],
	];

	/**
	 * The controller for alice, over the real conformance service unless one is given.
	 *
	 * @param ConformanceServiceInterface|null $conformance The service to use instead.
	 *
	 * @return ConformanceController The controller.
	 */
	private function controller(?ConformanceServiceInterface $conformance = null): ConformanceController {
		$folder = $this->createMock(Folder::class);
		$folder->method('getById')->willReturnCallback(
			function (int $id): array {
				if (isset($this->own[$id]) === false) {
					return [];
				}

				[$name, $mime, $fixture] = $this->own[$id];
				$file = $this->createMock(File::class);
				$file->method('getId')->willReturn($id);
				$file->method('getName')->willReturn($name);
				$file->method('getMimetype')->willReturn($mime);
				$file->method('getContent')->willReturnCallback(fn (): string => $fixture === '' ? 'tekst' : $this->pdf(name: $fixture));

				return [$file];
			}
		);
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->with('alice')->willReturn($folder);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);
		$conformance ??= new ConformanceService(
			$this->veraPdf(),
			new ConformanceGuidance(),
			new ConformanceReportRepository(new DocumentObjectServiceResolver($this->container(), $this->apps())),
			$this->clock()
		);

		return new ConformanceController('filinq', $this->createMock(IRequest::class), $conformance, $root, $session, $l10n, new NullLogger());

	}//end controller()

	/**
	 * Check, then read: the stored report comes back with its verdict.
	 *
	 * @return void
	 */
	public function testACheckIsStoredAndShown(): void {
		$checked = $this->controller()->check(fileId: 42);

		$this->assertSame(200, $checked->getStatus());
		$this->assertFalse($checked->getData()['report']['compliant']);

		$shown = $this->controller()->show(fileId: 42)->getData();
		$this->assertTrue($shown['available']);
		$this->assertSame(['Helvetica'], ((array) $shown['reports'])['file']['fontsNotEmbedded']);
		$this->assertSame('veraPDF 1.30.2', ((array) $shown['reports'])['file']['validatorVersion']);

	}//end testACheckIsStoredAndShown()

	/**
	 * Somebody else's file, or none: the same 404, and nothing checked.
	 *
	 * @return void
	 */
	public function testAFileTheCallerCannotOpenIs404(): void {
		foreach ([99, 0] as $id) {
			$this->assertSame(404, $this->controller()->check(fileId: $id)->getStatus());
			$this->assertSame(['error' => 'File not found'], $this->controller()->show(fileId: $id)->getData());
		}

		$this->assertSame([], $this->rows[ConformanceReportRepository::SCHEMA] ?? []);

	}//end testAFileTheCallerCannotOpenIs404()

	/**
	 * No validator: 503 naming why, no report.
	 *
	 * @return void
	 */
	public function testNoValidatorIs503AndStoresNothing(): void {
		$this->config[VeraPdfService::CFG_BINARY_PATH] = '/nonexistent/verapdf';

		$response = $this->controller()->check(fileId: 42);

		$this->assertSame(503, $response->getStatus());
		$this->assertSame('unavailable', $response->getData()['reason']);
		$this->assertFalse($this->controller()->show(fileId: 42)->getData()['available']);
		$this->assertSame([], $this->rows[ConformanceReportRepository::SCHEMA] ?? []);

	}//end testNoValidatorIs503AndStoresNothing()

	/**
	 * A text file is not sent to a PDF validator.
	 *
	 * @return void
	 */
	public function testANonPdfIsRefused(): void {
		$this->assertSame(400, $this->controller()->check(fileId: 43)->getStatus());

	}//end testANonPdfIsRefused()

	/**
	 * The admin status row is the service's status, unchanged.
	 *
	 * @return void
	 */
	public function testStatusIsTheServiceStatus(): void {
		$conformance = $this->createMock(originalClassName: ConformanceServiceInterface::class);
		$conformance->method('status')->willReturn(value: ['enabled' => true, 'available' => false, 'version' => null]);

		$response = $this->controller(conformance: $conformance)->status();

		$this->assertSame(expected: ['enabled' => true, 'available' => false, 'version' => null], actual: $response->getData());

	}//end testStatusIsTheServiceStatus()

	/**
	 * Reports that cannot be read are a 500 with a message, not a crash.
	 *
	 * @return void
	 */
	public function testUnreadableReportsAre500(): void {
		$conformance = $this->createMock(originalClassName: ConformanceServiceInterface::class);
		$conformance->method('reportsFor')->willThrowException(exception: new RuntimeException(message: 'store down'));

		$response = $this->controller(conformance: $conformance)->show(fileId: 42);

		$this->assertSame(expected: 500, actual: $response->getStatus());
		$this->assertSame(expected: 'The conformance report could not be read. Try again later.', actual: $response->getData()['error']);

	}//end testUnreadableReportsAre500()

	/**
	 * A check whose report cannot be stored is a 500 with a message.
	 *
	 * @return void
	 */
	public function testAReportThatCannotBeStoredIs500(): void {
		$conformance = $this->createMock(originalClassName: ConformanceServiceInterface::class);
		$conformance->method('checkFile')->willThrowException(exception: new RuntimeException(message: 'store down'));

		$response = $this->controller(conformance: $conformance)->check(fileId: 42);

		$this->assertSame(expected: 500, actual: $response->getStatus());
		$this->assertSame(expected: 'The check ran, but its report could not be stored. Try again later.', actual: $response->getData()['error']);

	}//end testAReportThatCannotBeStoredIs500()
}//end class
