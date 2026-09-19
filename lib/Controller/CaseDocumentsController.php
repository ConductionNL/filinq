<?php

/**
 * Case Documents Controller
 *
 * The HTTP surface of the flat file list, the domains a document record serves
 * and the documents one person created.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Service\DocumentDomainService;
use OCA\Filinq\Service\FlatFileListService;
use OCA\Filinq\Service\UploadPolicyService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use Throwable;

/**
 * Controller for the files, the domains and the personal documents list.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */
class CaseDocumentsController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param string $appName The app name.
	 * @param IRequest $request The request.
	 * @param FlatFileListService $flatList Every file on every record of an object.
	 * @param DocumentDomainService $domains The domains a record serves.
	 * @param UploadPolicyService $policy What may be uploaded.
	 * @param IUserSession $userSession The current session.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly FlatFileListService $flatList,
		private readonly DocumentDomainService $domains,
		private readonly UploadPolicyService $policy,
		private readonly IUserSession $userSession,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Every file on one object, one page of it.
	 *
	 * @param string $register The object's register slug.
	 * @param string $schema The object's schema slug.
	 * @param string $id The object's id.
	 * @param int $page The page to read.
	 * @param int $limit How many rows a page holds.
	 * @param string $search A file-name filter.
	 *
	 * @return JSONResponse The page of files.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	#[NoAdminRequired]
	public function files(
		string $register = '',
		string $schema = '',
		string $id = '',
		int $page = 1,
		int $limit = 50,
		string $search = '',
	): JSONResponse {
		$unauthenticated = $this->requireUser();
		if ($unauthenticated !== null) {
			return $unauthenticated;
		}

		try {
			return new JSONResponse(
				data: $this->flatList->listFor(
					domain: ['register' => $register, 'schema' => $schema, 'id' => $id],
					page: $page,
					limit: $limit,
					search: $search
				),
				statusCode: Http::STATUS_OK
			);
		} catch (Throwable $e) {
			return new JSONResponse(
				data: ['error' => $e->getMessage()],
				statusCode: Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}

	}//end files()

	/**
	 * Link one document record to one domain.
	 *
	 * @param string $uuid The document record.
	 * @param string $register The domain's register slug.
	 * @param string $schema The domain's schema slug.
	 * @param string $id The domain's id.
	 *
	 * @return JSONResponse The record, with its domains.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	#[NoAdminRequired]
	public function linkDomain(string $uuid, string $register = '', string $schema = '', string $id = ''): JSONResponse {
		return $this->domainCall(
			handler: fn (): array => $this->domains->link(
				uuid: $uuid,
				domain: ['register' => $register, 'schema' => $schema, 'id' => $id]
			)
		);

	}//end linkDomain()

	/**
	 * Unlink one domain from one document record.
	 *
	 * @param string $uuid The document record.
	 * @param string $register The domain's register slug.
	 * @param string $schema The domain's schema slug.
	 * @param string $id The domain's id.
	 *
	 * @return JSONResponse The record, which still exists.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	#[NoAdminRequired]
	public function unlinkDomain(string $uuid, string $register = '', string $schema = '', string $id = ''): JSONResponse {
		return $this->domainCall(
			handler: fn (): array => $this->domains->unlink(
				uuid: $uuid,
				domain: ['register' => $register, 'schema' => $schema, 'id' => $id]
			)
		);

	}//end unlinkDomain()

	/**
	 * The document records the current user created.
	 *
	 * @return JSONResponse Their records.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	#[NoAdminRequired]
	public function mine(): JSONResponse {
		$unauthenticated = $this->requireUser();
		if ($unauthenticated !== null) {
			return $unauthenticated;
		}

		$records = $this->domains->listMine();

		return new JSONResponse(
			data: ['results' => $records, 'total' => count($records)],
			statusCode: Http::STATUS_OK
		);

	}//end mine()

	/**
	 * The upload policy in force, so a surface can say what it allows.
	 *
	 * @return JSONResponse The policy, or an empty answer when none is declared.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	#[NoAdminRequired]
	public function uploadPolicy(): JSONResponse {
		$unauthenticated = $this->requireUser();
		if ($unauthenticated !== null) {
			return $unauthenticated;
		}

		return new JSONResponse(
			data: ['policy' => $this->policy->activePolicy()],
			statusCode: Http::STATUS_OK
		);

	}//end uploadPolicy()

	/**
	 * Run one domain call and shape its answer.
	 *
	 * @param callable $handler The call.
	 *
	 * @return JSONResponse The record, or the refusal.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	private function domainCall(callable $handler): JSONResponse {
		$unauthenticated = $this->requireUser();
		if ($unauthenticated !== null) {
			return $unauthenticated;
		}

		try {
			return new JSONResponse(data: $handler(), statusCode: Http::STATUS_OK);
		} catch (Throwable $e) {
			return new JSONResponse(
				data: ['error' => $e->getMessage()],
				statusCode: Http::STATUS_BAD_REQUEST
			);
		}

	}//end domainCall()

	/**
	 * Refuse an anonymous caller.
	 *
	 * @return JSONResponse|null The refusal, or null when somebody is logged in.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	private function requireUser(): ?JSONResponse {
		if ($this->userSession->getUser() !== null) {
			return null;
		}

		return new JSONResponse(
			data: ['error' => 'You must be logged in to read case documents.'],
			statusCode: Http::STATUS_UNAUTHORIZED
		);

	}//end requireUser()
}//end class
