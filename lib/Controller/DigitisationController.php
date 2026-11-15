<?php

/**
 * Digitisation Controller
 *
 * The HTTP surface of a digitisation project: its originals counted per box,
 * the separator sheets of one box, a CSV box list imported, and the check of
 * a scanned original. Every object is read and written with the caller's own
 * OpenRegister rights.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use InvalidArgumentException;
use OCA\Filinq\Kernel\AppIdentity;
use OCA\Filinq\Kernel\Contracts\SeparatorSheetServiceInterface;
use OCA\Filinq\Service\DigitisationWorklist;
use OCA\Filinq\Service\PaperOriginalImport;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use Throwable;

/**
 * Controller for the digitisation project page and the checking queue.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md
 */
class DigitisationController extends Controller {
	use AnswersNotAuthenticated;

	/**
	 * Constructor.
	 *
	 * @param IRequest                       $request     The request.
	 * @param DigitisationWorklist           $worklist    Projects, originals and checks.
	 * @param PaperOriginalImport            $import      The CSV box list import.
	 * @param SeparatorSheetServiceInterface $sheets      The separator sheets.
	 * @param IUserSession                   $userSession The current session.
	 *
	 * @return void
	 */
	public function __construct(
		IRequest $request,
		private readonly DigitisationWorklist $worklist,
		private readonly PaperOriginalImport $import,
		private readonly SeparatorSheetServiceInterface $sheets,
		private readonly IUserSession $userSession,
	) {
		parent::__construct(appName: AppIdentity::APP_ID, request: $request);

	}//end __construct()

	/**
	 * The project's originals counted per box and state.
	 *
	 * @param string $id The digitisationProject.
	 *
	 * @return JSONResponse The rows, or the refusal.
	 *
	 * @no-admin-idor-exempt the project and its originals are read through
	 *       OpenRegister with RBAC and multitenancy on (DigitisationWorklist::
	 *       project() and originals()), so a caller who may not read the
	 *       project gets a 404 and sees only the originals they may read.
	 *
	 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-an-archivist-runs-a-digitisation-project-req-pad-001
	 */
	#[NoAdminRequired]
	public function boxes(string $id): JSONResponse {
		if ($this->userSession->getUser() === null) {
			return $this->notAuthenticated(message: 'You must be logged in to read a digitisation project.');
		}

		$project = $this->worklist->project(projectId: $id);
		if ($project === null) {
			return self::notFound();
		}

		return new JSONResponse(data: $this->worklist->boxCounts(project: $project));

	}//end boxes()

	/**
	 * The separator sheets of one box: one page per original.
	 *
	 * @param string $id  The digitisationProject.
	 * @param string $box The box.
	 *
	 * @return DataDownloadResponse|JSONResponse The sheets, or the refusal.
	 *
	 * @no-admin-idor-exempt the project and the box's originals are read
	 *       through OpenRegister with RBAC and multitenancy on, so a caller
	 *       prints sheets only for originals they may read.
	 *
	 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-a-separator-sheet-names-the-original-it-precedes-req-pad-002
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function separators(string $id, string $box = ''): DataDownloadResponse|JSONResponse {
		if ($this->userSession->getUser() === null) {
			return $this->notAuthenticated(message: 'You must be logged in to print separator sheets.');
		}

		$project = $this->worklist->project(projectId: $id);
		if ($project === null) {
			return self::notFound();
		}

		$originals = $this->worklist->originals(projectId: (string)$project['id'], box: $box);
		try {
			$pdf = $this->sheets->renderOriginals(profileId: (string)($project['scanProfile'] ?? ''), originals: $originals);
		} catch (Throwable $e) {
			return new JSONResponse(data: ['error' => $e->getMessage()], statusCode: Http::STATUS_BAD_REQUEST);
		}

		return new DataDownloadResponse($pdf, self::sheetName(box: $box), 'application/pdf');

	}//end separators()

	/**
	 * Import a CSV box list into the project.
	 *
	 * @param string $id  The digitisationProject.
	 * @param string $csv The box list.
	 *
	 * @return JSONResponse The count imported and the rows that failed, or the refusal.
	 *
	 * @no-admin-idor-exempt the project is read and every original is saved
	 *       through OpenRegister with RBAC and multitenancy on: a caller who
	 *       may not read the project gets a 404, and a row the caller may not
	 *       create is listed as failed with OpenRegister's reason.
	 *
	 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-an-archivist-runs-a-digitisation-project-req-pad-001
	 */
	#[NoAdminRequired]
	public function importOriginals(string $id, string $csv = ''): JSONResponse {
		if ($this->userSession->getUser() === null) {
			return $this->notAuthenticated(message: 'You must be logged in to import a box list.');
		}

		$project = $this->worklist->project(projectId: $id);
		if ($project === null) {
			return self::notFound();
		}

		try {
			$result = $this->import->import(projectId: (string)$project['id'], csv: $csv);
		} catch (InvalidArgumentException $e) {
			return new JSONResponse(data: ['error' => $e->getMessage(), 'message' => $e->getMessage()], statusCode: Http::STATUS_BAD_REQUEST);
		}

		if ($result['failures'] === []) {
			return new JSONResponse(data: $result);
		}

		$reasons = array_map(static fn (array $failure): string => sprintf('line %d: %s', $failure['line'], $failure['reason']), $result['failures']);
		$message = sprintf('Imported %d originals; %d rows failed (%s).', $result['imported'], count($result['failures']), implode('; ', $reasons));

		return new JSONResponse(data: $result + ['message' => $message], statusCode: Http::STATUS_UNPROCESSABLE_ENTITY);

	}//end importOriginals()

	/**
	 * Pass or reject the scan of an original.
	 *
	 * @param string $id     The paperOriginal.
	 * @param string $result passed or rejected.
	 * @param string $note   The checker's note; required for a rejection.
	 *
	 * @return JSONResponse The original as saved, or the refusal.
	 *
	 * @no-admin-idor-exempt the original is read and saved through OpenRegister
	 *       with RBAC and multitenancy on (DigitisationWorklist::check()), so a
	 *       caller who may not read it gets a 404 and one who may not update
	 *       it gets OpenRegister's refusal.
	 *
	 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-a-sampled-share-is-checked-by-a-person-req-pad-004
	 */
	#[NoAdminRequired]
	public function check(string $id, string $result = '', string $note = ''): JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return $this->notAuthenticated(message: 'You must be logged in to check a scan.');
		}

		try {
			$original = $this->worklist->check(originalId: $id, result: $result, note: $note, checkedBy: $user->getUID());
		} catch (InvalidArgumentException $e) {
			return new JSONResponse(data: ['error' => $e->getMessage(), 'message' => $e->getMessage()], statusCode: Http::STATUS_BAD_REQUEST);
		} catch (Throwable $e) {
			$message = 'The check could not be saved.';
			return new JSONResponse(data: ['error' => $message, 'message' => $message], statusCode: Http::STATUS_FORBIDDEN);
		}

		if ($original === null) {
			return self::notFound();
		}

		return new JSONResponse(data: $original);

	}//end check()

	/**
	 * The refusal for an object the caller cannot read.
	 *
	 * @return JSONResponse
	 */
	private static function notFound(): JSONResponse {
		return new JSONResponse(data: ['error' => 'Not found', 'message' => 'Not found'], statusCode: Http::STATUS_NOT_FOUND);

	}//end notFound()

	/**
	 * The file name of a box's sheets.
	 *
	 * @param string $box The box.
	 *
	 * @return string
	 */
	private static function sheetName(string $box): string {
		$box = trim((string)preg_replace('/[^A-Za-z0-9._-]+/', '-', $box), '-');
		if ($box === '') {
			return 'scheidingsvellen.pdf';
		}

		return 'scheidingsvellen-' . $box . '.pdf';

	}//end sheetName()
}//end class
