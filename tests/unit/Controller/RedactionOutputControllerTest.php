<?php

/**
 * The redaction output surface, asserted from the caller.
 *
 * 🔴 THESE ARE CALLER-SIDE ASSERTIONS ON PURPOSE. The composer and the download
 * gate have their own suites, and nothing in those can see whether a route ever
 * reaches them. A gate with a green suite and no caller is a gate the product
 * does not have.
 *
 * 🔴 A LIST THAT IS NOT READY AND AN AGREEMENT THAT IS NOT MET BOTH ANSWER 409,
 * NEVER 200 WITH A BODY THAT SAYS SO. A caller reading the status alone is the
 * ordinary case, and a refusal dressed as success is how an unredacted record
 * leaves the building.
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

use OCA\Filinq\Controller\RedactionOutputController;
use OCA\Filinq\Service\Redaction\DownloadAgreementGate;
use OCA\Filinq\Service\Redaction\PublicationListComposer;
use OCA\Filinq\Service\Redaction\RedactionOutputGuard;
use OCP\AppFramework\Http;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;

/**
 * `RedactionOutputController`.
 *
 * @covers \OCA\Filinq\Controller\RedactionOutputController
 */
class RedactionOutputControllerTest extends TestCase {

	/**
	 * A composer double.
	 *
	 * `onlyMethods` so it cannot answer a question the real composer lacks: a
	 * controller calling a method nothing implements would pass here and 500 on
	 * the route.
	 *
	 * @return PublicationListComposer The double.
	 */
	private function composer(): PublicationListComposer {
		return $this->getMockBuilder(PublicationListComposer::class)
			->disableOriginalConstructor()
			->onlyMethods(['compose'])
			->getMock();
	}//end composer()

	/**
	 * A download-agreement double.
	 *
	 * @return DownloadAgreementGate The double.
	 */
	private function agreements(): DownloadAgreementGate {
		return $this->getMockBuilder(DownloadAgreementGate::class)
			->disableOriginalConstructor()
			->onlyMethods(['check', 'accept'])
			->getMock();
	}//end agreements()

	/**
	 * A controller over the given collaborators.
	 *
	 * @param PublicationListComposer $composer   The composer.
	 * @param DownloadAgreementGate   $agreements The agreement gate.
	 * @param string                  $uid        Who is asking, or '' for nobody.
	 *
	 * @return RedactionOutputController The controller.
	 */
	private function controller(
		PublicationListComposer $composer,
		DownloadAgreementGate $agreements,
		string $uid = 'beheerder',
	): RedactionOutputController {
		$session = $this->createMock(IUserSession::class);
		if ($uid === '') {
			$session->method('getUser')->willReturn(null);
		} else {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($uid);
			$session->method('getUser')->willReturn($user);
		}

		return new RedactionOutputController(
			'filinq',
			$this->createMock(IRequest::class),
			$this->getMockBuilder(RedactionOutputGuard::class)
				->disableOriginalConstructor()
				->onlyMethods(['markChecked'])
				->getMock(),
			$composer,
			$agreements,
			$session
		);
	}//end controller()

	/**
	 * A composed list is served as it stands.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function testAComposedListIsServed(): void {
		$composer = $this->composer();
		$composer->expects($this->once())
			->method('compose')
			->with('woo-2026', 'Woo-verzoek 2026')
			->willReturn(['view' => 'woo-2026', 'title' => 'Woo-verzoek 2026', 'count' => 2, 'entries' => []]);

		$response = $this->controller($composer, $this->agreements())
			->composeList(view: 'woo-2026', title: 'Woo-verzoek 2026');

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame('woo-2026', $response->getData()['view']);
	}//end testAComposedListIsServed()

	/**
	 * A view holding an unredacted record is refused with 409, not served.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function testAViewThatIsNotReadyIsRefusedWithAConflict(): void {
		$composer = $this->composer();
		$composer->method('compose')->willReturn(
			['refused' => 'notReady', 'message' => 'One record has no redacted copy yet.', 'notReady' => [7]]
		);

		$response = $this->controller($composer, $this->agreements())->composeList(view: 'woo-2026');

		$this->assertSame(Http::STATUS_CONFLICT, $response->getStatus());
		$this->assertSame('notReady', $response->getData()['refused']);
	}//end testAViewThatIsNotReadyIsRefusedWithAConflict()

	/**
	 * The conditions are read for the person asking, never for nobody.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function testTheAgreementIsCheckedForThePersonAsking(): void {
		$agreements = $this->agreements();
		$agreements->expects($this->once())
			->method('check')
			->with('doc-1', 'beheerder')
			->willReturn(['mayDownload' => false, 'terms' => 'Niet verder verspreiden.']);

		$response = $this->controller($this->composer(), $agreements, 'beheerder')
			->agreement(document: 'doc-1');

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertFalse($response->getData()['mayDownload']);
	}//end testTheAgreementIsCheckedForThePersonAsking()

	/**
	 * An acceptance that does not free the file answers 409.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function testAnAcceptanceThatDoesNotFreeTheFileIsAConflict(): void {
		$agreements = $this->agreements();
		$agreements->expects($this->once())
			->method('accept')
			->with('doc-1', 'beheerder', '3')
			->willReturn(['mayDownload' => false, 'reason' => 'The version shown is not the current one.']);

		$response = $this->controller($this->composer(), $agreements, 'beheerder')
			->acceptAgreement(document: 'doc-1', version: '3');

		$this->assertSame(Http::STATUS_CONFLICT, $response->getStatus());
	}//end testAnAcceptanceThatDoesNotFreeTheFileIsAConflict()

	/**
	 * An acceptance that frees the file answers 200.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function testAnAcceptanceThatFreesTheFileIsServed(): void {
		$agreements = $this->agreements();
		$agreements->method('accept')->willReturn(['mayDownload' => true, 'acceptedAt' => '2026-09-18T20:00:00+00:00']);

		$response = $this->controller($this->composer(), $agreements, 'beheerder')
			->acceptAgreement(document: 'doc-1', version: '4');

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertTrue($response->getData()['mayDownload']);
	}//end testAnAcceptanceThatFreesTheFileIsServed()

	/**
	 * With no session the acceptance is recorded against nobody, not a guess.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function testWithNoSessionTheAcceptanceNamesNobody(): void {
		$agreements = $this->agreements();
		$agreements->expects($this->once())
			->method('accept')
			->with('doc-1', '', '1')
			->willReturn(['mayDownload' => false]);

		$this->controller($this->composer(), $agreements, '')
			->acceptAgreement(document: 'doc-1', version: '1');
	}//end testWithNoSessionTheAcceptanceNamesNobody()
}//end class
