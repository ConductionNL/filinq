<?php

/**
 * Document Production Controller
 *
 * The HTTP surface of the paper a document goes out on, the bundle a case
 * leaves as, the documents a saved view renders by itself, and the ones that
 * come back for review.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Service\CaseArchiveService;
use OCA\Filinq\Service\DocumentReviewService;
use OCA\Filinq\Service\PageLayoutService;
use OCA\Filinq\Service\PeriodicDocumentService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use Throwable;

/**
 * Controller for layouts, bundles, periodic documents and reviews.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 */
class DocumentProductionController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param string $appName The app name.
	 * @param IRequest $request The request.
	 * @param PageLayoutService $layouts The paper.
	 * @param CaseArchiveService $archives The bundle.
	 * @param PeriodicDocumentService $periodic The documents that make themselves.
	 * @param DocumentReviewService $reviews The ones that come back.
	 * @param IUserSession $userSession The current session.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly PageLayoutService $layouts,
		private readonly CaseArchiveService $archives,
		private readonly PeriodicDocumentService $periodic,
		private readonly DocumentReviewService $reviews,
		private readonly IUserSession $userSession,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Every version of one page layout, newest first.
	 *
	 * @param string $name The layout name.
	 *
	 * @return JSONResponse The versions.
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	#[NoAdminRequired]
	public function layoutVersions(string $name = ''): JSONResponse {
		return $this->answer(handler: fn (): array => ['results' => $this->layouts->versionsOf(name: $name)]);

	}//end layoutVersions()

	/**
	 * Edit a layout by writing the next version of it.
	 *
	 * @param string $name The layout name.
	 * @param array<string, mixed> $changes The fields to change.
	 *
	 * @return JSONResponse The new version.
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	#[NoAdminRequired]
	public function editLayout(string $name = '', array $changes = []): JSONResponse {
		return $this->answer(handler: fn (): array => $this->layouts->edit(name: $name, changes: $changes));

	}//end editLayout()

	/**
	 * What a bundle of this object would be, before it is built.
	 *
	 * @param string $register The object's register slug.
	 * @param string $schema The object's schema slug.
	 * @param string $id The object's id.
	 * @param int $ceiling The ceiling in force, in bytes.
	 *
	 * @return JSONResponse The preflight, including whether it exceeds the ceiling.
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	#[NoAdminRequired]
	public function archivePreflight(
		string $register = '',
		string $schema = '',
		string $id = '',
		int $ceiling = CaseArchiveService::DEFAULT_CEILING,
	): JSONResponse {
		return $this->answer(
			handler: fn (): array => $this->archives->preflight(
				domain: ['register' => $register, 'schema' => $schema, 'id' => $id],
				ceiling: $ceiling
			)
		);

	}//end archivePreflight()

	/**
	 * The manifest of a bundle of this object: what goes in, and what does not.
	 *
	 * @param string $register The object's register slug.
	 * @param string $schema The object's schema slug.
	 * @param string $id The object's id.
	 * @param int $ceiling The ceiling in force, in bytes.
	 *
	 * @return JSONResponse The manifest.
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	#[NoAdminRequired]
	public function archiveManifest(
		string $register = '',
		string $schema = '',
		string $id = '',
		int $ceiling = CaseArchiveService::DEFAULT_CEILING,
	): JSONResponse {
		return $this->answer(
			handler: function () use ($register, $schema, $id, $ceiling): array {
				$domain = ['register' => $register, 'schema' => $schema, 'id' => $id];
				$manifest = $this->archives->manifestFor(domain: $domain, ceiling: $ceiling);
				$this->archives->record(domain: $domain, manifest: $manifest, ceiling: $ceiling);

				return $manifest;
			}
		);

	}//end archiveManifest()

	/**
	 * Run one periodic document now.
	 *
	 * @param array<string, mixed> $schedule The schedule to run.
	 *
	 * @return JSONResponse The document this run produced.
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	#[NoAdminRequired]
	public function runPeriodic(array $schedule = []): JSONResponse {
		return $this->answer(handler: fn (): array => $this->periodic->run(schedule: $schedule));

	}//end runPeriodic()

	/**
	 * The documents due for review.
	 *
	 * @param string $day The day to ask about, or an empty string for today.
	 *
	 * @return JSONResponse The due documents.
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	#[NoAdminRequired]
	public function dueForReview(string $day = ''): JSONResponse {
		return $this->answer(
			handler: function () use ($day): array {
				$due = $this->reviews->listDue(day: $day);

				return ['results' => $due, 'total' => count($due)];
			}
		);

	}//end dueForReview()

	/**
	 * Record that somebody reviewed a document.
	 *
	 * @param string $uuid The document.
	 *
	 * @return JSONResponse The document, no longer due.
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	#[NoAdminRequired]
	public function markReviewed(string $uuid): JSONResponse {
		return $this->answer(handler: fn (): array => $this->reviews->markReviewed(uuid: $uuid));

	}//end markReviewed()

	/**
	 * Run one call and shape its answer.
	 *
	 * @param callable $handler The call.
	 *
	 * @return JSONResponse The answer, or the failure.
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	private function answer(callable $handler): JSONResponse {
		if ($this->userSession->getUser() === null) {
			return new JSONResponse(
				data: ['error' => 'You must be logged in.'],
				statusCode: Http::STATUS_UNAUTHORIZED
			);
		}

		try {
			return new JSONResponse(data: $handler(), statusCode: Http::STATUS_OK);
		} catch (Throwable $e) {
			return new JSONResponse(
				data: ['error' => $e->getMessage()],
				statusCode: Http::STATUS_BAD_REQUEST
			);
		}

	}//end answer()
}//end class
