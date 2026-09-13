<?php

/**
 * Dossier Management Controller
 *
 * HTTP surface for the dossier index and detail, and for the membership and
 * lifecycle operations behind them. Every method delegates to
 * {@see \OCA\Filinq\Service\DossierManagementService}; none of them reach into
 * OpenRegister directly, and none reimplement batch, report or publication
 * logic that already exists elsewhere in the app.
 *
 * ⚠️ AUTHORISATION, STATED ACCURATELY — the same caveat that
 * {@see \OCA\Filinq\Controller\DossierController} records. Reads and writes go
 * through OpenRegister WITH its RBAC engaged (no `_rbac: false` anywhere in the
 * service), so whatever the `dossier` schema's cascade declares is what is
 * enforced. That cascade currently declares `read: ["authenticated"]`, so the
 * read guard resolves to an existence test for any authenticated user in the
 * organisation; `update` and `delete` are restricted to
 * `docudesk-policy-admins` plus OpenRegister's unconditional owner bypass, so
 * an operator can always manage the dossiers they created.
 *
 * The FILE half is real regardless: every listing runs through the caller's own
 * Nextcloud view, so a document the operator cannot see never enters a
 * response. Tightening the object cascade is tracked in ConductionNL/filinq#441.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Service\DossierManagementService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Endpoints for the dossier-management surface.
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 */
class DossierManagementController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param string $appName The application name.
	 * @param IRequest $request The current HTTP request.
	 * @param DossierManagementService $dossiers Dossier aggregation and membership.
	 * @param IUserSession $userSession The current session.
	 * @param IL10N $l10n Localisation.
	 * @param LoggerInterface $logger Logger.
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly DossierManagementService $dossiers,
		private readonly IUserSession $userSession,
		private readonly IL10N $l10n,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * List every dossier the caller can read.
	 *
	 * @return JSONResponse The index rows.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	#[NoAdminRequired]
	public function index(): JSONResponse {
		return $this->run(handler: fn (): array => ['results' => $this->dossiers->index()]);

	}//end index()

	/**
	 * One dossier, aggregated.
	 *
	 * @param string $dossierId The dossier object UUID.
	 *
	 * @return JSONResponse The aggregated dossier.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	#[NoAdminRequired]
	public function show(string $dossierId): JSONResponse {
		return $this->run(handler: fn (): array => $this->dossiers->detail(dossierId: $dossierId));

	}//end show()

	/**
	 * Create a dossier and its bound home folder.
	 *
	 * @param string $name The dossier name.
	 * @param string $description Optional free text.
	 * @param array<int, string> $bases Grondslag slugs.
	 *
	 * @return JSONResponse The created dossier.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	#[NoAdminRequired]
	public function create(string $name, string $description = '', array $bases = []): JSONResponse {
		return $this->run(
			handler: fn (): array => $this->dossiers->create(
				name: $name,
				description: $description,
				bases: $bases
			),
			status: Http::STATUS_CREATED
		);

	}//end create()

	/**
	 * Rename a dossier, keeping its bound home folder in sync.
	 *
	 * @param string $dossierId The dossier object UUID.
	 * @param string $name The new name.
	 *
	 * @return JSONResponse The refreshed dossier, carrying `folderWarning`.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	#[NoAdminRequired]
	public function rename(string $dossierId, string $name): JSONResponse {
		return $this->run(
			handler: fn (): array => $this->dossiers->rename(dossierId: $dossierId, name: $name)
		);

	}//end rename()

	/**
	 * Move a dossier to a new lifecycle status.
	 *
	 * @param string $dossierId The dossier object UUID.
	 * @param string $status The target status.
	 *
	 * @return JSONResponse The refreshed dossier.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	#[NoAdminRequired]
	public function transition(string $dossierId, string $status): JSONResponse {
		return $this->run(
			handler: fn (): array => $this->dossiers->transition(dossierId: $dossierId, status: $status)
		);

	}//end transition()

	/**
	 * Add an existing document to a dossier by reference.
	 *
	 * @param string $dossierId The dossier object UUID.
	 * @param int $fileId The Nextcloud file node id.
	 *
	 * @return JSONResponse The refreshed dossier.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	#[NoAdminRequired]
	public function linkDocument(string $dossierId, int $fileId): JSONResponse {
		return $this->run(
			handler: fn (): array => $this->dossiers->linkDocument(dossierId: $dossierId, fileId: $fileId)
		);

	}//end linkDocument()

	/**
	 * Remove a document from a dossier — trashing it or unlinking it.
	 *
	 * @param string $dossierId The dossier object UUID.
	 * @param int $fileId The Nextcloud file node id.
	 *
	 * @return JSONResponse The refreshed dossier.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	#[NoAdminRequired]
	public function removeDocument(string $dossierId, int $fileId): JSONResponse {
		return $this->run(
			handler: fn (): array => $this->dossiers->removeDocument(dossierId: $dossierId, fileId: $fileId)
		);

	}//end removeDocument()

	/**
	 * Whether removing a document would trash it or merely unlink it.
	 *
	 * Read by the confirmation dialog so it can SAY which one it is. A confirm
	 * that reads the same for both is how an operator deletes a file they meant
	 * to unlink.
	 *
	 * @param string $dossierId The dossier object UUID.
	 * @param int $fileId The Nextcloud file node id.
	 *
	 * @return JSONResponse `{ mode: 'trash' | 'unlink' }`.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	#[NoAdminRequired]
	public function removalMode(string $dossierId, int $fileId): JSONResponse {
		return $this->run(
			handler: fn (): array => [
				'mode' => $this->dossiers->removalMode(dossierId: $dossierId, fileId: $fileId),
			]
		);

	}//end removalMode()

	/**
	 * Run a handler behind the session check and a uniform error shape.
	 *
	 * The service raises RuntimeException with an HTTP code in the exception
	 * code — 400 for a bad request, 404 for absent-or-unreadable, 409 for a
	 * refused lifecycle transition, 503 when OpenRegister is away. Passing that
	 * through keeps a refused transition readable instead of collapsing every
	 * failure into a 500 the operator cannot act on.
	 *
	 * @param callable():array<string, mixed> $handler The operation.
	 * @param int $status The success status.
	 *
	 * @return JSONResponse The response.
	 */
	private function run(callable $handler, int $status = Http::STATUS_OK): JSONResponse {
		if ($this->userSession->getUser() === null) {
			return new JSONResponse(
				['error' => $this->l10n->t('Not authenticated')],
				Http::STATUS_UNAUTHORIZED
			);
		}

		try {
			return new JSONResponse($handler(), $status);
		} catch (RuntimeException $e) {
			$code = $e->getCode();
			if ($code < 400 || $code > 599) {
				$code = Http::STATUS_INTERNAL_SERVER_ERROR;
			}

			return new JSONResponse(['error' => $e->getMessage()], $code);
		} catch (Throwable $e) {
			$this->logger->error(
				'DossierManagementController: unexpected failure',
				['exception' => $e->getMessage()]
			);

			return new JSONResponse(
				['error' => $this->l10n->t('Something went wrong handling this dossier.')],
				Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}

	}//end run()

}//end class
