<?php

/**
 * Post Register Controller
 *
 * The HTTP surface of the post register's three derived reads: what is still
 * open in a unit, what answered one inbound entry, and a unit's series with
 * every gap accounted for.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Service\PostRegisterReader;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use Throwable;

/**
 * Reads the post register over HTTP.
 *
 * 🔴 A FAILED READ ANSWERS AN ERROR, NEVER AN EMPTY LIST. The reader raises
 * rather than reporting "no answers" or "nothing open", and this controller
 * keeps that: an empty list on this endpoint means the unit has nothing
 * outstanding, and a handler acts on it. Turning a register outage into `[]`
 * here would empty somebody's work list and they would go home.
 *
 * 🔑 THE LEAF IS NOT THIS. ADR-066's leaf for the open post list is blocked on
 * filinq shipping no `leaves` webpack entry, so what the post register offers
 * today is these endpoints. A consuming app can already read them; when the
 * leaf lands it renders what is here rather than a second query.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
 */
class PostRegisterController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param string             $appName     The app name.
	 * @param IRequest           $request     The request.
	 * @param PostRegisterReader $reader      The post register's derived reads.
	 * @param IUserSession       $userSession The current session.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly PostRegisterReader $reader,
		private readonly IUserSession $userSession,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * What a unit still has to answer, oldest first.
	 *
	 * @param string $unit The organisational unit.
	 *
	 * @return JSONResponse The undischarged inbound entries.
	 *
	 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
	 */
	#[NoAdminRequired]
	public function openPost(string $unit = ''): JSONResponse {
		return $this->read(handler: fn (): array => ['results' => $this->reader->openPostFor($unit)]);

	}//end openPost()

	/**
	 * The outbound registrations that answer one inbound entry.
	 *
	 * @param string $uuid The inbound registration.
	 *
	 * @return JSONResponse The answers, and whether there are any.
	 *
	 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
	 */
	#[NoAdminRequired]
	public function answers(string $uuid = ''): JSONResponse {
		return $this->read(
			handler: function () use ($uuid): array {
				$answers = $this->reader->answersFor($uuid);

				// The list comes back, not a boolean. "Discharged: yes" loses
				// which document did it, and a caller given a boolean cannot
				// get the list back. `discharged` is derived here for a caller
				// that only wants the flag, from the list rather than beside it.
				return ['results' => $answers, 'discharged' => ($answers !== [])];
			}
		);

	}//end answers()

	/**
	 * A unit's series, in number order, with every gap accounted for.
	 *
	 * @param string $unit The organisational unit.
	 *
	 * @return JSONResponse The series.
	 *
	 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
	 */
	#[NoAdminRequired]
	public function series(string $unit = ''): JSONResponse {
		return $this->read(handler: fn (): array => ['results' => $this->reader->seriesFor($unit)]);

	}//end series()

	/**
	 * Run one read behind the session guard, turning a failure into an error.
	 *
	 * @param callable():array<string, mixed> $handler The read.
	 *
	 * @return JSONResponse The answer.
	 *
	 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
	 */
	private function read(callable $handler): JSONResponse {
		if ($this->userSession->getUser() === null) {
			return new JSONResponse(
				data: ['error' => 'You must be logged in to read the post register.'],
				statusCode: Http::STATUS_UNAUTHORIZED
			);
		}

		try {
			return new JSONResponse(data: $handler());
		} catch (Throwable $e) {
			// 🔴 AN ERROR, NOT AN EMPTY LIST. See the class docblock: `[]` here
			// empties a work list and somebody goes home.
			return new JSONResponse(
				data: ['error' => 'The post register could not be read: ' . $e->getMessage()],
				statusCode: Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}

	}//end read()
}//end class
