<?php

/**
 * The post register's HTTP surface, asserted from the caller.
 *
 * 🔴 THESE ARE CALLER-SIDE ASSERTIONS ON PURPOSE. PostRegisterReaderTest proves
 * the reader answers correctly; nothing in it can see whether anything ever
 * asks. A reader with a green suite and no route is a class the product does
 * not have, and that is what these tests exist to catch.
 *
 * 🔴 AND A FAILED READ ANSWERS AN ERROR, NEVER AN EMPTY LIST. The reader raises
 * rather than reporting "nothing open"; a controller that caught that and
 * answered `[]` would empty somebody's work list and they would go home.
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

use OCA\Filinq\Controller\PostRegisterController;
use OCA\Filinq\Service\PostRegisterReader;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * `PostRegisterController`.
 *
 * @covers \OCA\Filinq\Controller\PostRegisterController
 */
class PostRegisterControllerTest extends TestCase {

	/**
	 * A reader double.
	 *
	 * `onlyMethods` so it cannot answer a question the real reader lacks: a
	 * controller calling a method nothing implements would pass here and 500 on
	 * the route.
	 *
	 * @return PostRegisterReader The double.
	 */
	private function reader(): PostRegisterReader {
		return $this->getMockBuilder(PostRegisterReader::class)
			->disableOriginalConstructor()
			->onlyMethods(['openPostFor', 'answersFor', 'seriesFor'])
			->getMock();
	}//end reader()

	/**
	 * A session holding somebody, or nobody.
	 *
	 * @param bool $loggedIn Whether anybody is logged in.
	 *
	 * @return IUserSession The double.
	 */
	private function session(bool $loggedIn = true): IUserSession {
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($loggedIn === true ? $this->createMock(IUser::class) : null);

		return $session;
	}//end session()

	/**
	 * A controller over the given reader.
	 *
	 * @param PostRegisterReader $reader   The reader.
	 * @param bool               $loggedIn Whether anybody is logged in.
	 *
	 * @return PostRegisterController The controller.
	 */
	private function controller(PostRegisterReader $reader, bool $loggedIn = true): PostRegisterController {
		return new PostRegisterController(
			'filinq',
			$this->createMock(IRequest::class),
			$reader,
			$this->session(loggedIn: $loggedIn)
		);
	}//end controller()

	/**
	 * The open post route asks the reader and answers what it said.
	 *
	 * Removing the `openPostFor()` call from the controller reddens here with
	 * "method was expected to be called 1 times, actually called 0 times", and
	 * the body assertion goes with it.
	 *
	 * @return void
	 */
	public function testTheOpenPostRouteAsksTheReader(): void {
		$reader = $this->reader();
		$reader->expects(self::once())
			->method('openPostFor')
			->with('burgerzaken')
			->willReturn([['id' => 'in-1'], ['id' => 'in-2']]);

		$data = $this->controller($reader)->openPost('burgerzaken')->getData();

		self::assertSame(['in-1', 'in-2'], array_column($data['results'], 'id'));
	}//end testTheOpenPostRouteAsksTheReader()

	/**
	 * The answers route returns the documents, and derives the flag from them.
	 *
	 * @return void
	 */
	public function testTheAnswersRouteReturnsTheDocumentsNotJustAFlag(): void {
		$reader = $this->reader();
		$reader->expects(self::once())
			->method('answersFor')
			->with('in-1')
			->willReturn([['registrationNumber' => '2026-00099']]);

		$data = $this->controller($reader)->answers('in-1')->getData();

		// The list, not a boolean. A caller given only "discharged: yes" cannot
		// get back which document did it, which is what somebody reading the
		// register a year later actually wants.
		self::assertSame('2026-00099', $data['results'][0]['registrationNumber']);
		self::assertTrue($data['discharged']);
	}//end testTheAnswersRouteReturnsTheDocumentsNotJustAFlag()

	/**
	 * The series route asks the reader for the unit's series.
	 *
	 * @return void
	 */
	public function testTheSeriesRouteAsksTheReader(): void {
		$reader = $this->reader();
		$reader->expects(self::once())
			->method('seriesFor')
			->with('burgerzaken')
			->willReturn([['registrationNumber' => '2026-00001']]);

		$data = $this->controller($reader)->series('burgerzaken')->getData();

		self::assertSame('2026-00001', $data['results'][0]['registrationNumber']);
	}//end testTheSeriesRouteAsksTheReader()

	/**
	 * A register that could not be read answers an error, not an empty list.
	 *
	 * @return void
	 */
	public function testAFailedReadIsAnErrorNotAnEmptyWorkList(): void {
		$reader = $this->reader();
		$reader->method('openPostFor')->willThrowException(new RuntimeException('register unreachable'));

		$response = $this->controller($reader)->openPost('burgerzaken');

		self::assertSame(500, $response->getStatus());
		self::assertStringContainsString('register unreachable', $response->getData()['error']);
		self::assertArrayNotHasKey('results', $response->getData());
	}//end testAFailedReadIsAnErrorNotAnEmptyWorkList()

	/**
	 * Nobody logged in is refused, and the register is never read.
	 *
	 * 🔑 THE LEAST PRIVILEGED PRINCIPAL THAT SHOULD BE REFUSED. `#[NoAdminRequired]`
	 * only says "not admin-only"; without the session guard the route would run
	 * for an anonymous request and hand out a unit's correspondence.
	 *
	 * @return void
	 */
	public function testAnAnonymousRequestIsRefusedBeforeTheRegisterIsRead(): void {
		$reader = $this->reader();
		$reader->expects(self::never())->method('openPostFor');

		$response = $this->controller($reader, loggedIn: false)->openPost('burgerzaken');

		self::assertSame(401, $response->getStatus());
	}//end testAnAnonymousRequestIsRefusedBeforeTheRegisterIsRead()
}//end class
