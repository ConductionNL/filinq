<?php

/**
 * Redaction Output Controller
 *
 * The HTTP surface of what leaves the building: marking a detection run
 * checked, composing a publication list out of redacted copies, and the
 * conditions a gated download waits on.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Service\Redaction\DownloadAgreementGate;
use OCA\Filinq\Service\Redaction\PublicationListComposer;
use OCA\Filinq\Service\Redaction\RedactionOutputGuard;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use Throwable;

/**
 * Controller for the review mark, the composed list and the download gate.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */
class RedactionOutputController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param string                  $appName     The app name.
	 * @param IRequest                $request     The request.
	 * @param RedactionOutputGuard    $guard       The human review gate.
	 * @param PublicationListComposer $composer    The publication list.
	 * @param DownloadAgreementGate   $agreements  The download conditions.
	 * @param IUserSession            $userSession The current session.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly RedactionOutputGuard $guard,
		private readonly PublicationListComposer $composer,
		private readonly DownloadAgreementGate $agreements,
		private readonly IUserSession $userSession,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Record that this person checked this detection run.
	 *
	 * 🔴 THE CHECKER IS THE SESSION, NEVER THE PAYLOAD. Taking `checkedBy` from
	 * the request would let a caller sign an approval in somebody else's name,
	 * which turns the accountability record into the opposite of one.
	 *
	 * @param int                              $fileId   The document.
	 * @param array<int, array<string, mixed>> $entities The entities this run found.
	 * @param string                           $note     What the checker wants recorded.
	 *
	 * @return JSONResponse The stored mark, or the refusal.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	#[NoAdminRequired]
	public function markChecked(int $fileId, array $entities = [], string $note = ''): JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new JSONResponse(
				data: ['error' => 'A check is signed by a person, and this request has none.'],
				statusCode: Http::STATUS_UNAUTHORIZED
			);
		}

		try {
			$mark = $this->guard->markChecked(
				fileId: $fileId,
				entities: $entities,
				checkedBy: $user->getUID(),
				note: $note
			);
		} catch (Throwable $e) {
			return new JSONResponse(
				data: ['error' => $e->getMessage()],
				statusCode: Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}

		return new JSONResponse(data: $mark);

	}//end markChecked()

	/**
	 * Compose a publication list over one saved view.
	 *
	 * @param string $view  The view id or slug.
	 * @param string $title What the list is called.
	 *
	 * @return JSONResponse The list, or what is not ready.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	#[NoAdminRequired]
	public function composeList(string $view, string $title = ''): JSONResponse {
		$composed = $this->composer->compose(view: $view, title: $title);

		if (isset($composed['refused']) === true) {
			return new JSONResponse(data: $composed, statusCode: Http::STATUS_CONFLICT);
		}

		return new JSONResponse(data: $composed);

	}//end composeList()

	/**
	 * The conditions this document is gated on, and whether they are met.
	 *
	 * @param string $document The document.
	 *
	 * @return JSONResponse The decision and the terms.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	#[NoAdminRequired]
	public function agreement(string $document): JSONResponse {
		return new JSONResponse(
			data: $this->agreements->check(document: $document, person: $this->personAsking())
		);

	}//end agreement()

	/**
	 * Accept the conditions, and say whether the file may now be served.
	 *
	 * @param string $document The document.
	 * @param string $version  The version the reader was shown.
	 *
	 * @return JSONResponse The decision.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	#[NoAdminRequired]
	public function acceptAgreement(string $document, string $version): JSONResponse {
		$decision = $this->agreements->accept(
			document: $document,
			person: $this->personAsking(),
			version: $version
		);

		if (($decision['mayDownload'] ?? false) === false) {
			return new JSONResponse(data: $decision, statusCode: Http::STATUS_CONFLICT);
		}

		return new JSONResponse(data: $decision);

	}//end acceptAgreement()

	/**
	 * Who is asking, as the acceptance records them.
	 *
	 * @return string The uid, or an empty string when there is no session.
	 *
	 * @spec exclude Session helper behind the agreement endpoints.
	 */
	private function personAsking(): string {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return '';
		}

		return $user->getUID();

	}//end personAsking()
}//end class
