<?php

/**
 * The final-document endpoints other than the rule declaration, asserted from the caller.
 *
 * Reading a document's final state; finalising, correcting and unfreezing it,
 * each of which needs a reason that becomes part of the record (unfreezing
 * also the administrator right); and applying a host app's state change. All
 * refuse without a session user, and every failure is answered through the
 * failure mapper with the action's own fallback sentence.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\FinalDocumentController;
use OCA\Filinq\Controller\Support\FinalDocumentChanges;
use OCA\Filinq\Controller\Support\FinalityRules;
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
 * `FinalDocumentController`: show, finalise, correct, unfreeze, applyStateChange.
 *
 * @covers \OCA\Filinq\Controller\FinalDocumentController
 * @uses   \OCA\Filinq\Controller\Support\FinalDocumentChanges
 * @uses   \OCA\Filinq\Controller\Support\FinalityRules
 */
class FinalDocumentControllerActionsTest extends TestCase {

	/**
	 * The final-document service double.
	 *
	 * @var FinalDocumentServiceInterface&MockObject
	 */
	private FinalDocumentServiceInterface&MockObject $finalDocuments;

	/**
	 * The correction service double.
	 *
	 * @var FinalDocumentCorrectionServiceInterface&MockObject
	 */
	private FinalDocumentCorrectionServiceInterface&MockObject $corrections;

	/**
	 * The unfreeze service double.
	 *
	 * @var FinalDocumentUnfreezeServiceInterface&MockObject
	 */
	private FinalDocumentUnfreezeServiceInterface&MockObject $unfreeze;

	/**
	 * The finality rule service double.
	 *
	 * @var DocumentFinalityRuleServiceInterface&MockObject
	 */
	private DocumentFinalityRuleServiceInterface&MockObject $rules;

	/**
	 * The failure mapper double.
	 *
	 * @var FinalDocumentFailureMapperInterface&MockObject
	 */
	private FinalDocumentFailureMapperInterface&MockObject $failures;

	/**
	 * Fresh doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->finalDocuments = $this->createMock(FinalDocumentServiceInterface::class);
		$this->corrections    = $this->createMock(FinalDocumentCorrectionServiceInterface::class);
		$this->unfreeze       = $this->createMock(FinalDocumentUnfreezeServiceInterface::class);
		$this->rules          = $this->createMock(DocumentFinalityRuleServiceInterface::class);
		$this->failures       = $this->createMock(FinalDocumentFailureMapperInterface::class);
	}//end setUp()

	/**
	 * The controller for a logged-in user (or none) over the given request parameters.
	 *
	 * @param array<string, mixed> $params   The request parameters.
	 * @param bool                 $loggedIn Whether a user is logged in.
	 *
	 * @return FinalDocumentController The controller.
	 */
	private function controller(array $params = [], bool $loggedIn = true): FinalDocumentController {
		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnCallback(
			static fn (string $key, mixed $default = null): mixed => (array_key_exists($key, $params) === true ? $params[$key] : $default)
		);

		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($loggedIn === true ? $this->createMock(IUser::class) : null);

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		return new FinalDocumentController(
			request: $request,
			finalDocuments: $this->finalDocuments,
			changes: new FinalDocumentChanges(corrections: $this->corrections, unfreeze: $this->unfreeze),
			finality: new FinalityRules(rules: $this->rules, failures: $this->failures),
			userSession: $session,
			l10n: $l10n,
		);
	}//end controller()

	/**
	 * Expect one failure shaped by the mapper with the given fallback.
	 *
	 * @param \Throwable $failure  The thrown failure.
	 * @param string     $fallback The action's fallback sentence.
	 *
	 * @return void
	 */
	private function expectMappedFailure(\Throwable $failure, string $fallback): void {
		$this->failures->expects($this->once())->method('shape')
			->with($failure, $fallback)
			->willReturn(['status' => 409, 'body' => ['error' => 'mapped']]);
	}//end expectMappedFailure()

	/**
	 * Every action refuses without a session user and touches no service.
	 *
	 * @return void
	 */
	public function testEveryActionRefusesWithoutAUser(): void {
		foreach ([$this->finalDocuments, $this->corrections, $this->unfreeze, $this->rules] as $service) {
			$service->expects($this->never())->method($this->anything());
		}

		$controller = $this->controller(['reason' => 'r', 'fileIds' => [1]], loggedIn: false);
		$responses  = [
			$controller->show(fileId: 1),
			$controller->finalise(fileId: 1),
			$controller->correct(fileId: 1),
			$controller->unfreeze(fileId: 1),
			$controller->applyStateChange(),
		];

		foreach ($responses as $response) {
			$this->assertSame([401, ['error' => 'Not authenticated']], [$response->getStatus(), $response->getData()]);
		}
	}//end testEveryActionRefusesWithoutAUser()

	/**
	 * Show reports the version chain, finality, checksum and the unfreeze right.
	 *
	 * @return void
	 */
	public function testShowReportsTheFinalState(): void {
		$version = ['id' => 'v2', 'final' => true];
		$this->corrections->method('chainFor')->with(5)->willReturn(['version' => $version, 'supersedes' => 'v1', 'supersededBy' => null]);
		$this->finalDocuments->expects($this->once())->method('isFinal')->with($version)->willReturn(true);
		$this->finalDocuments->method('verifyChecksum')->with(5)->willReturn(['valid' => true]);
		$this->unfreeze->method('mayUnfreeze')->willReturn(false);

		$response = $this->controller()->show(fileId: 5);

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(
			['final' => true, 'version' => $version, 'supersedes' => 'v1', 'supersededBy' => null, 'checksum' => ['valid' => true], 'mayUnfreeze' => false],
			$response->getData()
		);
	}//end testShowReportsTheFinalState()

	/**
	 * A document without a version is not final, and finality is not even asked.
	 *
	 * @return void
	 */
	public function testShowWithoutAVersionIsNotFinal(): void {
		$this->corrections->method('chainFor')->willReturn(['version' => null, 'supersedes' => null, 'supersededBy' => null]);
		$this->finalDocuments->expects($this->never())->method('isFinal');
		$this->finalDocuments->method('verifyChecksum')->willReturn([]);
		$this->unfreeze->method('mayUnfreeze')->willReturn(true);

		$data = $this->controller()->show(fileId: 5)->getData();

		$this->assertFalse($data['final']);
		$this->assertTrue($data['mayUnfreeze']);
	}//end testShowWithoutAVersionIsNotFinal()

	/**
	 * A failing read answers through the mapper.
	 *
	 * @return void
	 */
	public function testShowFailureIsMapped(): void {
		$failure = new RuntimeException('register down');
		$this->corrections->method('chainFor')->willThrowException($failure);
		$this->expectMappedFailure($failure, 'Could not read the final state of this document');

		$response = $this->controller()->show(fileId: 5);

		$this->assertSame([409, ['error' => 'mapped']], [$response->getStatus(), $response->getData()]);
	}//end testShowFailureIsMapped()

	/**
	 * Finalise needs a reason, passes it trimmed, and answers the new version.
	 *
	 * @return void
	 */
	public function testFinaliseNeedsAReason(): void {
		$this->finalDocuments->expects($this->once())->method('finalise')->with(9, 'Signed decision')->willReturn(['id' => 'v1']);

		$blank = $this->controller(['reason' => '   '])->finalise(fileId: 9);
		$this->assertSame(400, $blank->getStatus());
		$this->assertSame(['error' => 'Say why this document is final. The reason is part of the record.'], $blank->getData());

		$done = $this->controller(['reason' => '  Signed decision '])->finalise(fileId: 9);
		$this->assertSame([200, ['version' => ['id' => 'v1']]], [$done->getStatus(), $done->getData()]);
	}//end testFinaliseNeedsAReason()

	/**
	 * A failing finalise answers through the mapper.
	 *
	 * @return void
	 */
	public function testFinaliseFailureIsMapped(): void {
		$failure = new RuntimeException('already final');
		$this->finalDocuments->method('finalise')->willThrowException($failure);
		$this->expectMappedFailure($failure, 'Could not make this document final');

		$this->assertSame(409, $this->controller(['reason' => 'x'])->finalise(fileId: 9)->getStatus());
	}//end testFinaliseFailureIsMapped()

	/**
	 * Correct needs a reason and answers the correction as the service returns it.
	 *
	 * @return void
	 */
	public function testCorrectNeedsAReason(): void {
		$this->corrections->expects($this->once())->method('correct')->with(9, 'Wrong date')->willReturn(['version' => 'v3', 'supersedes' => 'v2']);

		$blank = $this->controller()->correct(fileId: 9);
		$this->assertSame(400, $blank->getStatus());
		$this->assertSame(['error' => 'Say what the correction changes. The reason is part of the record.'], $blank->getData());

		$done = $this->controller(['reason' => 'Wrong date'])->correct(fileId: 9);
		$this->assertSame([200, ['version' => 'v3', 'supersedes' => 'v2']], [$done->getStatus(), $done->getData()]);
	}//end testCorrectNeedsAReason()

	/**
	 * A failing correction answers through the mapper.
	 *
	 * @return void
	 */
	public function testCorrectFailureIsMapped(): void {
		$failure = new RuntimeException('not final');
		$this->corrections->method('correct')->willThrowException($failure);
		$this->expectMappedFailure($failure, 'Could not issue a correction');

		$this->assertSame(409, $this->controller(['reason' => 'x'])->correct(fileId: 9)->getStatus());
	}//end testCorrectFailureIsMapped()

	/**
	 * Unfreezing needs the administrator right before anything else, then a reason.
	 *
	 * @return void
	 */
	public function testUnfreezeNeedsTheRightAndAReason(): void {
		$this->unfreeze->method('mayUnfreeze')->willReturnOnConsecutiveCalls(false, true, true);
		$this->unfreeze->expects($this->once())->method('unfreeze')->with(9, 'Wrong file')->willReturn(['id' => 'v2', 'final' => false]);

		$refused = $this->controller(['reason' => 'Wrong file'])->unfreeze(fileId: 9);
		$this->assertSame(403, $refused->getStatus());
		$this->assertSame(['error' => 'Unfreezing a final document needs the final-document administrator right.'], $refused->getData());

		$blank = $this->controller(['reason' => ''])->unfreeze(fileId: 9);
		$this->assertSame(400, $blank->getStatus());
		$this->assertSame(['error' => 'Say why you are unfreezing this document. The reason stays on the record.'], $blank->getData());

		$done = $this->controller(['reason' => 'Wrong file'])->unfreeze(fileId: 9);
		$this->assertSame([200, ['version' => ['id' => 'v2', 'final' => false]]], [$done->getStatus(), $done->getData()]);
	}//end testUnfreezeNeedsTheRightAndAReason()

	/**
	 * A failing unfreeze answers through the mapper.
	 *
	 * @return void
	 */
	public function testUnfreezeFailureIsMapped(): void {
		$failure = new RuntimeException('locked');
		$this->unfreeze->method('mayUnfreeze')->willReturn(true);
		$this->unfreeze->method('unfreeze')->willThrowException($failure);
		$this->expectMappedFailure($failure, 'Could not unfreeze this document');

		$this->assertSame(409, $this->controller(['reason' => 'x'])->unfreeze(fileId: 9)->getStatus());
	}//end testUnfreezeFailureIsMapped()

	/**
	 * A state change passes the host's fields, file ids as integers, and counts what became final.
	 *
	 * @return void
	 */
	public function testAStateChangeFinalisesAndCounts(): void {
		$this->rules->expects($this->once())->method('applyStateChange')
			->with('dossiq', 'case-type-1', 'closed', $this->identicalTo([3, 4]), 'decision')
			->willReturn([['fileId' => 3], ['fileId' => 4]]);

		$response = $this->controller([
			'declaringApp' => 'dossiq',
			'typeReference' => 'case-type-1',
			'state' => 'closed',
			'fileIds' => ['3', 4],
			'documentRole' => 'decision',
		])->applyStateChange();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['finalised' => [['fileId' => 3], ['fileId' => 4]], 'count' => 2], $response->getData());
	}//end testAStateChangeFinalisesAndCounts()

	/**
	 * Missing fields read as empty, and file ids that are not a list as none.
	 *
	 * @return void
	 */
	public function testAStateChangeWithoutFieldsIsEmpty(): void {
		$this->rules->expects($this->once())->method('applyStateChange')->with('', '', '', [], '')->willReturn([]);

		$response = $this->controller(['fileIds' => '3,4'])->applyStateChange();

		$this->assertSame(['finalised' => [], 'count' => 0], $response->getData());
	}//end testAStateChangeWithoutFieldsIsEmpty()

	/**
	 * A failing state change answers through the mapper.
	 *
	 * @return void
	 */
	public function testAStateChangeFailureIsMapped(): void {
		$failure = new RuntimeException('no rule');
		$this->rules->method('applyStateChange')->willThrowException($failure);
		$this->expectMappedFailure($failure, 'Could not apply the state change');

		$this->assertSame(409, $this->controller()->applyStateChange()->getStatus());
	}//end testAStateChangeFailureIsMapped()
}//end class
