<?php

/**
 * Unit tests for DigitisationController.
 *
 * The project page's per-box counts, a box's separator sheets, the CSV box
 * list and the check of a scan, each refused for a caller who is not logged
 * in or cannot read the object.
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use InvalidArgumentException;
use OCA\Filinq\Controller\DigitisationController;
use OCA\Filinq\Kernel\Contracts\SeparatorSheetServiceInterface;
use OCA\Filinq\Kernel\Contracts\DigitisationWorklistInterface;
use OCA\Filinq\Kernel\Contracts\PaperOriginalImportInterface;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Controller\DigitisationController
 */
class DigitisationControllerTest extends TestCase {

	private DigitisationWorklistInterface&MockObject $worklist;

	private PaperOriginalImportInterface&MockObject $import;

	private SeparatorSheetServiceInterface&MockObject $sheets;

	protected function setUp(): void {
		$this->worklist = $this->createMock(DigitisationWorklistInterface::class);
		$this->import = $this->createMock(PaperOriginalImportInterface::class);
		$this->sheets = $this->createMock(SeparatorSheetServiceInterface::class);
	}//end setUp()

	/**
	 * The controller, with checker logged in unless $loggedIn is false.
	 *
	 * @param bool $loggedIn Whether anybody is logged in.
	 *
	 * @return DigitisationController
	 */
	private function controller(bool $loggedIn = true): DigitisationController {
		$session = $this->createMock(IUserSession::class);
		$user = null;
		if ($loggedIn === true) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn('checker');
		}

		$session->method('getUser')->willReturn($user);

		return new DigitisationController($this->createMock(IRequest::class), $this->worklist, $this->import, $this->sheets, $session);
	}//end controller()

	/**
	 * Nobody logged in gets 401 from every endpoint and nothing is read.
	 *
	 * @return void
	 */
	public function testEveryEndpointRefusesAnAnonymousCaller(): void {
		$this->worklist->expects($this->never())->method('project');
		$this->worklist->expects($this->never())->method('check');
		$controller = $this->controller(loggedIn: false);

		foreach ([$controller->boxes(id: 'prj-1'), $controller->separators(id: 'prj-1', box: '117'), $controller->importOriginals(id: 'prj-1', csv: 'x'), $controller->check(id: 'po-1', result: 'passed')] as $response) {
			self::assertInstanceOf(JSONResponse::class, $response);
			self::assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());
		}
	}//end testEveryEndpointRefusesAnAnonymousCaller()

	/**
	 * A project the caller cannot read is 404 everywhere it is named.
	 *
	 * @return void
	 */
	public function testAnUnreadableProjectIsNotFound(): void {
		$this->worklist->method('project')->with('prj-x')->willReturn(null);
		$this->worklist->expects($this->never())->method('boxCounts');
		$this->import->expects($this->never())->method('import');
		$this->sheets->expects($this->never())->method('renderOriginals');
		$controller = $this->controller();

		foreach ([$controller->boxes(id: 'prj-x'), $controller->separators(id: 'prj-x', box: '117'), $controller->importOriginals(id: 'prj-x', csv: 'x')] as $response) {
			self::assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
			self::assertSame(['error' => 'Not found', 'message' => 'Not found'], $response->getData());
		}
	}//end testAnUnreadableProjectIsNotFound()

	/**
	 * The box rows are answered as the worklist counts them.
	 *
	 * @return void
	 */
	public function testBoxesAnswersTheCounts(): void {
		$project = ['id' => 'prj-1', 'boxes' => ['117']];
		$rows = [['id' => '117', 'box' => '117', 'registered' => 40]];
		$this->worklist->method('project')->willReturn($project);
		$this->worklist->expects($this->once())->method('boxCounts')->with($project)->willReturn($rows);

		$response = $this->controller()->boxes(id: 'prj-1');

		self::assertSame(Http::STATUS_OK, $response->getStatus());
		self::assertSame($rows, $response->getData());
	}//end testBoxesAnswersTheCounts()

	/**
	 * A box's sheets are rendered for the project's scan profile and downloaded.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-11-25-intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-a-separator-sheet-names-the-original-it-precedes-req-pad-002
	 */
	public function testSeparatorsDownloadTheBoxsSheets(): void {
		$originals = [['id' => 'po-1', 'shelfMark' => '117/1']];
		$this->worklist->method('project')->willReturn(['id' => 'prj-1', 'scanProfile' => 'archief']);
		$this->worklist->expects($this->once())->method('originals')->with('prj-1', 'Doos 117')->willReturn($originals);
		$this->sheets->expects($this->once())->method('renderOriginals')->with('archief', $originals)->willReturn('%PDF-sheets');

		$response = $this->controller()->separators(id: 'prj-1', box: 'Doos 117');

		self::assertInstanceOf(DataDownloadResponse::class, $response);
		self::assertSame('%PDF-sheets', $response->getData());
		self::assertStringContainsString('scheidingsvellen-Doos-117.pdf', $response->getHeaders()['Content-Disposition']);
	}//end testSeparatorsDownloadTheBoxsSheets()

	/**
	 * A box with nothing to print is a 400 with the reason.
	 *
	 * @return void
	 */
	public function testSeparatorsOfAnEmptyBoxAreRefused(): void {
		$this->worklist->method('project')->willReturn(['id' => 'prj-1']);
		$this->worklist->method('originals')->willReturn([]);
		$this->sheets->method('renderOriginals')->with('', [])->willThrowException(new RuntimeException('There is no paper original to print a separator sheet for.'));

		$response = $this->controller()->separators(id: 'prj-1', box: '//');

		self::assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		self::assertSame(['error' => 'There is no paper original to print a separator sheet for.'], $response->getData());
	}//end testSeparatorsOfAnEmptyBoxAreRefused()

	/**
	 * A clean import is 200; one with failed rows is 422 listing them; an
	 * unusable list is 400.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-11-25-intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-an-archivist-runs-a-digitisation-project-req-pad-001
	 */
	public function testImportAnswersTheResult(): void {
		$this->worklist->method('project')->willReturn(['id' => 'prj-1']);
		$this->import->method('import')->willReturnCallback(
			static function (string $projectId, string $csv): array {
				self::assertSame('prj-1', $projectId);
				return match ($csv) {
					'clean' => ['imported' => 40, 'failures' => []],
					'bad' => ['imported' => 39, 'failures' => [['line' => 3, 'reason' => 'the shelf mark is empty']]],
					default => throw new InvalidArgumentException('The box list is empty.'),
				};
			}
		);
		$controller = $this->controller();

		$clean = $controller->importOriginals(id: 'prj-1', csv: 'clean');
		self::assertSame([Http::STATUS_OK, ['imported' => 40, 'failures' => []]], [$clean->getStatus(), $clean->getData()]);

		$bad = $controller->importOriginals(id: 'prj-1', csv: 'bad');
		self::assertSame(Http::STATUS_UNPROCESSABLE_ENTITY, $bad->getStatus());
		self::assertSame('Imported 39 originals; 1 rows failed (line 3: the shelf mark is empty).', $bad->getData()['message']);
		self::assertSame(39, $bad->getData()['imported']);

		$empty = $controller->importOriginals(id: 'prj-1', csv: '');
		self::assertSame([Http::STATUS_BAD_REQUEST, 'The box list is empty.'], [$empty->getStatus(), $empty->getData()['error']]);
	}//end testImportAnswersTheResult()

	/**
	 * A check is recorded as the logged-in user and answers the original.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-11-25-intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-a-sampled-share-is-checked-by-a-person-req-pad-004
	 */
	public function testCheckIsRecordedAsTheCaller(): void {
		$saved = ['id' => 'po-1', 'status' => 'registered'];
		$this->worklist->expects($this->once())->method('check')->with('po-1', 'rejected', 'page 2 cut off', 'checker')->willReturn($saved);

		$response = $this->controller()->check(id: 'po-1', result: 'rejected', note: 'page 2 cut off');

		self::assertSame([Http::STATUS_OK, $saved], [$response->getStatus(), $response->getData()]);
	}//end testCheckIsRecordedAsTheCaller()

	/**
	 * An invalid check is 400, an unreadable original 404 and a refused save 403.
	 *
	 * @return void
	 */
	public function testCheckRefusals(): void {
		$this->worklist->method('check')->willReturnCallback(
			static function (string $originalId): ?array {
				return match ($originalId) {
					'po-invalid' => throw new InvalidArgumentException('A rejected scan needs a note saying what is wrong.'),
					'po-locked' => throw new RuntimeException('You may not update this object'),
					default => null,
				};
			}
		);
		$controller = $this->controller();

		$invalid = $controller->check(id: 'po-invalid', result: 'rejected');
		self::assertSame([Http::STATUS_BAD_REQUEST, 'A rejected scan needs a note saying what is wrong.'], [$invalid->getStatus(), $invalid->getData()['error']]);

		$locked = $controller->check(id: 'po-locked', result: 'passed');
		self::assertSame([Http::STATUS_FORBIDDEN, 'The check could not be saved.'], [$locked->getStatus(), $locked->getData()['error']]);

		$missing = $controller->check(id: 'po-404', result: 'passed');
		self::assertSame(Http::STATUS_NOT_FOUND, $missing->getStatus());
	}//end testCheckRefusals()
}//end class
