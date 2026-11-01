<?php

/**
 * Characterization tests for FinalDocumentController::declareRule()
 *
 * Pins the administrator-right refusal, the declaration handed to the rule
 * service, and that `declaredBy` is always the session user.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\FinalDocumentController;
use OCA\Filinq\Kernel\Contracts\DocumentFinalityRuleServiceInterface;
use OCA\Filinq\Kernel\Contracts\FinalDocumentCorrectionServiceInterface;
use OCA\Filinq\Kernel\Contracts\FinalDocumentFailureMapperInterface;
use OCA\Filinq\Kernel\Contracts\FinalDocumentServiceInterface;
use OCA\Filinq\Kernel\Contracts\FinalDocumentUnfreezeServiceInterface;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Characterization of the declare-rule endpoint.
 *
 * @covers \OCA\Filinq\Controller\FinalDocumentController
 */
class FinalDocumentControllerDeclareRuleTraceTest extends TestCase {

	private const BODY = [
		'declaringApp' => 'dossiq',
		'typeReference' => 'case-type-1',
		'finalStates' => ['closed', 'archived'],
		'documentRole' => 'decision',
		'reasonTemplate' => 'Closed on {date}',
	];

	private DocumentFinalityRuleServiceInterface&MockObject $rules;

	private FinalDocumentUnfreezeServiceInterface&MockObject $unfreeze;

	private FinalDocumentFailureMapperInterface&MockObject $failures;

	/**
	 * Fresh doubles; the session user holds the administrator right.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->rules = $this->createMock(DocumentFinalityRuleServiceInterface::class);
		$this->unfreeze = $this->createMock(FinalDocumentUnfreezeServiceInterface::class);
		$this->failures = $this->createMock(FinalDocumentFailureMapperInterface::class);

	}//end setUp()

	/**
	 * The declaration reaches the rule service with the SESSION user as declaredBy.
	 *
	 * @return void
	 */
	public function testTheDeclarationCarriesTheSessionUser(): void {
		$this->unfreeze->method('mayUnfreeze')->willReturn(true);
		$this->rules->expects($this->once())->method('declareRule')
			->with('dossiq', 'case-type-1', ['closed', 'archived'], 'decision', 'Closed on {date}', 'alice')
			->willReturn(['id' => 'rule-1']);

		$params = self::BODY + ['declaredBy' => 'mallory', 'userId' => 'mallory', 'requestedBy' => 'mallory'];
		$response = $this->controller(uid: 'alice', params: $params)->declareRule();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['rule' => ['id' => 'rule-1']], $response->getData());

	}//end testTheDeclarationCarriesTheSessionUser()

	/**
	 * Missing fields read as empty strings and non-array states as none.
	 *
	 * @return void
	 */
	public function testMissingFieldsAreEmpty(): void {
		$this->unfreeze->method('mayUnfreeze')->willReturn(true);
		$this->rules->expects($this->once())->method('declareRule')
			->with('', '', [], '', '', 'alice')
			->willReturn([]);

		$this->assertSame(200, $this->controller(uid: 'alice', params: ['finalStates' => 'closed'])->declareRule()->getStatus());

	}//end testMissingFieldsAreEmpty()

	/**
	 * Without the administrator right nothing is stored, whatever the body claims.
	 *
	 * @return void
	 */
	public function testWithoutTheRightNothingIsStored(): void {
		$this->unfreeze->method('mayUnfreeze')->willReturn(false);
		$this->rules->expects($this->never())->method('declareRule');

		$response = $this->controller(uid: 'alice', params: self::BODY + ['userId' => 'admin', 'requestedBy' => 'admin'])->declareRule();

		$this->assertSame(403, $response->getStatus());
		$this->assertSame(['error' => 'Declaring which states make a document final needs the final-document administrator right.'], $response->getData());

	}//end testWithoutTheRightNothingIsStored()

	/**
	 * Without a session the right is not even asked.
	 *
	 * @return void
	 */
	public function testNoSessionAnswers401(): void {
		$this->unfreeze->expects($this->never())->method('mayUnfreeze');
		$this->rules->expects($this->never())->method('declareRule');

		$response = $this->controller(uid: null, params: self::BODY)->declareRule();

		$this->assertSame([401, ['error' => 'Not authenticated']], [$response->getStatus(), $response->getData()]);

	}//end testNoSessionAnswers401()

	/**
	 * A failing store answers through the failure mapper with its own fallback.
	 *
	 * @return void
	 */
	public function testFailureGoesThroughTheMapper(): void {
		$this->unfreeze->method('mayUnfreeze')->willReturn(true);
		$failure = new RuntimeException('incomplete');
		$this->rules->method('declareRule')->willThrowException($failure);
		$this->failures->expects($this->once())->method('shape')
			->with($failure, 'Could not store the declaration')
			->willReturn(['status' => 422, 'body' => ['error' => 'incomplete']]);

		$response = $this->controller(uid: 'alice', params: self::BODY)->declareRule();

		$this->assertSame([422, ['error' => 'incomplete']], [$response->getStatus(), $response->getData()]);

	}//end testFailureGoesThroughTheMapper()

	/**
	 * The controller for session user $uid over $params.
	 *
	 * @param string|null $uid The session user, or null for none.
	 * @param array<string, mixed> $params The request body.
	 *
	 * @return FinalDocumentController The controller.
	 */
	private function controller(?string $uid, array $params): FinalDocumentController {
		$request = $this->createMock(IRequest::class);
		$request->method('getParams')->willReturn($params);
		$request->method('getParam')->willReturnCallback(
			static fn (string $key, mixed $default = null): mixed => (array_key_exists($key, $params) === true ? $params[$key] : $default)
		);

		$session = $this->createMock(IUserSession::class);
		$user = null;
		if ($uid !== null) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($uid);
		}

		$session->method('getUser')->willReturn($user);

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		return new FinalDocumentController(
			appName: 'filinq',
			request: $request,
			finalDocuments: $this->createMock(FinalDocumentServiceInterface::class),
			corrections: $this->createMock(FinalDocumentCorrectionServiceInterface::class),
			unfreeze: $this->unfreeze,
			rules: $this->rules,
			failures: $this->failures,
			userSession: $session,
			l10n: $l10n,
		);

	}//end controller()
}//end class
