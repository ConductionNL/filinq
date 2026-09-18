<?php

/**
 * Scan Intake Controller
 *
 * The HTTP surface of paper intake: print separator sheets, read and declare
 * the scan profiles, and cut one delivered batch.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Service\ScanBatchRepository;
use OCA\Filinq\Service\ScanBatchService;
use OCA\Filinq\Service\ScanProfileService;
use OCA\Filinq\Service\SeparatorSheetService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\IUserSession;
use Throwable;

/**
 * Controller for separator sheets, scan profiles and batch splitting.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) The thirteenth type is
 * OCP\Files\File, named so the batch id is checked to be a file before it
 * reaches the reader. Without that check a folder id passes as a batch with no
 * pages, which is the silent version of this refusal.
 */
class ScanIntakeController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param string $appName The app name.
	 * @param IRequest $request The request.
	 * @param SeparatorSheetService $sheets The sheets filinq prints and reads.
	 * @param ScanProfileService $profiles The declared scan profiles.
	 * @param ScanBatchService $batches The splitter.
	 * @param ScanBatchRepository $store The batch store.
	 * @param IRootFolder $rootFolder The file tree.
	 * @param IUserSession $userSession The current session.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly SeparatorSheetService $sheets,
		private readonly ScanProfileService $profiles,
		private readonly ScanBatchService $batches,
		private readonly ScanBatchRepository $store,
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $userSession,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Print separator sheets, one page per case number.
	 *
	 * @param string $profileId The scan profile the sheets belong to.
	 * @param array<int, string> $caseNumbers The cases to print a sheet for.
	 *
	 * @return DataDownloadResponse|JSONResponse The sheets, or the refusal.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	#[NoAdminRequired]
	public function separators(string $profileId = '', array $caseNumbers = []): DataDownloadResponse|JSONResponse {
		if ($this->userSession->getUser() === null) {
			return new JSONResponse(
				data: ['error' => 'You must be logged in to print separator sheets.'],
				statusCode: Http::STATUS_UNAUTHORIZED
			);
		}

		try {
			$pdf = $this->sheets->render(profileId: $profileId, caseNumbers: $caseNumbers);
		} catch (Throwable $e) {
			return new JSONResponse(
				data: ['error' => $e->getMessage()],
				statusCode: Http::STATUS_BAD_REQUEST
			);
		}

		return new DataDownloadResponse($pdf, 'scheidingsvellen.pdf', 'application/pdf');

	}//end separators()

	/**
	 * The declared scan profiles.
	 *
	 * @return JSONResponse The profiles.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	#[NoAdminRequired]
	public function listProfiles(): JSONResponse {
		if ($this->userSession->getUser() === null) {
			return new JSONResponse(
				data: ['error' => 'You must be logged in.'],
				statusCode: Http::STATUS_UNAUTHORIZED
			);
		}

		$profiles = $this->profiles->all();

		return new JSONResponse(
			data: ['results' => $profiles, 'total' => count($profiles)],
			statusCode: Http::STATUS_OK
		);

	}//end listProfiles()

	/**
	 * Declare the scan profiles.
	 *
	 * Administrative: a profile points a scanner's output at a folder and says
	 * how what lands there is cut, which is an instance-wide decision.
	 *
	 * @param array<int, array<string, mixed>> $profiles The profiles.
	 *
	 * @return JSONResponse What was stored, or the refusal.
	 *
	 * @auth admin-only a profile decides which folder a scanner writes into and how
	 *       everything landing there is cut, for the whole instance; the admin posture
	 *       is the absence of NoAdminRequired, and adding that attribute to satisfy a
	 *       gate would hand every authenticated user the setting this method exists to
	 *       keep administrative.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function declareProfiles(array $profiles = []): JSONResponse {
		try {
			return new JSONResponse(
				data: ['results' => $this->profiles->declare(profiles: $profiles)],
				statusCode: Http::STATUS_OK
			);
		} catch (Throwable $e) {
			return new JSONResponse(
				data: ['error' => $e->getMessage()],
				statusCode: Http::STATUS_BAD_REQUEST
			);
		}

	}//end declareProfiles()

	/**
	 * Cut one delivered batch into documents.
	 *
	 * @param int $fileId The batch the scanner wrote.
	 * @param string $profileId The profile to cut it with; when empty, the folder it sits in decides.
	 *
	 * @return JSONResponse The batch, split or failed.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	#[NoAdminRequired]
	public function split(int $fileId, string $profileId = ''): JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new JSONResponse(
				data: ['error' => 'You must be logged in.'],
				statusCode: Http::STATUS_UNAUTHORIZED
			);
		}

		try {
			$userFolder = $this->rootFolder->getUserFolder($user->getUID());
			$nodes = $userFolder->getById($fileId);
			if ($nodes === []) {
				return new JSONResponse(
					data: ['error' => 'You may not read that batch.'],
					statusCode: Http::STATUS_FORBIDDEN
				);
			}

			$file = $nodes[0];
			if (($file instanceof File) === false) {
				// The id answers with whatever node carries it, and a folder id
				// is as valid an int as a file id. Passing a folder on would
				// reach the reader as a batch with no pages.
				return new JSONResponse(
					data: ['error' => 'That id is not a scanned batch.'],
					statusCode: Http::STATUS_BAD_REQUEST
				);
			}

			$profile = $this->resolveProfile(profileId: $profileId, path: $file->getPath());
			if ($profile === null) {
				return new JSONResponse(
					data: ['error' => 'No scan profile watches this folder, so there is nothing that says how to cut this batch.'],
					statusCode: Http::STATUS_BAD_REQUEST
				);
			}

			$batch = $this->store->findByFile(fileId: $fileId);
			if ($batch === null) {
				$batch = $this->batches->receive(file: $file, profile: $profile);
			}

			$target = $file->getParent();
			if (($target instanceof Folder) === false) {
				return new JSONResponse(
					data: ['error' => 'The batch has no folder to write its segments to.'],
					statusCode: Http::STATUS_BAD_REQUEST
				);
			}

			return new JSONResponse(
				data: $this->batches->split(batch: $batch, profile: $profile, file: $file, target: $target),
				statusCode: Http::STATUS_OK
			);
		} catch (Throwable $e) {
			return new JSONResponse(
				data: ['error' => $e->getMessage()],
				statusCode: Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}//end try

	}//end split()

	/**
	 * The profile to cut a batch with.
	 *
	 * @param string $profileId The profile the caller named, if any.
	 * @param string $path The path the batch sits at.
	 *
	 * @return array<string, mixed>|null The profile, or null when nobody watches this folder.
	 *
	 * @throws Throwable When the profile store cannot be read. The caller, split(),
	 *                   catches it and answers the refusal, so a store that is down
	 *                   is reported rather than read as "nobody watches this folder".
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	private function resolveProfile(string $profileId, string $path): ?array {
		if (trim($profileId) !== '') {
			return $this->profiles->find(id: trim($profileId));
		}

		return $this->profiles->forPath(path: $path);

	}//end resolveProfile()
}//end class
