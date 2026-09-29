<?php

/**
 * The erasure routes: refusals first, then the statuses of a normal run.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-5.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\SubjectErasureController;
use OCA\Filinq\Tests\Unit\Service\SubjectErasure\SubjectErasureDoubles;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * SubjectErasureController over the real services and the erasure doubles.
 */
class SubjectErasureControllerTest extends TestCase {
	use SubjectErasureDoubles;

	private const PERSON = 'Jan Jansen';

	/**
	 * One document naming the person.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->addFile(fileId: 11, name: 'aanvraag.txt', content: 'Aanvrager: Jan Jansen.');
		$this->catalogueHas(type: 'PERSON', value: self::PERSON, fileIds: [11]);

	}//end setUp()

	/**
	 * The controller as a user, with request parameters.
	 *
	 * @param string               $userId The caller, '' for nobody.
	 * @param array<string, mixed> $params The request parameters.
	 *
	 * @return SubjectErasureController The controller.
	 */
	private function controller(string $userId, array $params = []): SubjectErasureController {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($userId);
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($userId === '' ? null : $user);
		$request = $this->createMock(IRequest::class);
		$request->method('getParams')->willReturn($params);
		$request->method('getParam')->willReturnCallback(static fn (string $key, $default = null) => ($params[$key] ?? $default));
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);
		$services = $this->erasure();

		return new SubjectErasureController('filinq', $request, $services['service'], $services['run'], $session, $l10n, new NullLogger());

	}//end controller()

	/**
	 * Place a request as the privacy officer and return its uuid.
	 *
	 * @return string The uuid.
	 */
	private function placed(): string {
		$response = $this->controller(userId: 'petra', params: ['subject' => self::PERSON, 'ground' => 'AVG artikel 17'])->create();
		$this->assertSame(201, $response->getStatus());

		return (string) $response->getData()['uuid'];

	}//end placed()

	/**
	 * Somebody outside the erasure groups, or nobody, gets 403 on every route and nothing changes.
	 *
	 * @return void
	 */
	public function testOutsidersGet403OnEveryRoute(): void {
		$uuid = $this->placed();
		$before = $this->files[11]['content'];
		$statuses = [];
		foreach (['bob', ''] as $user) {
			$controller = $this->controller(userId: $user, params: ['subject' => self::PERSON, 'ground' => 'x', 'exclusions' => []]);
			$statuses[] = $controller->index()->getStatus();
			$statuses[] = $controller->create()->getStatus();
			$statuses[] = $controller->show(id: $uuid)->getStatus();
			$statuses[] = $controller->preview(id: $uuid)->getStatus();
			$statuses[] = $controller->exclude(id: $uuid)->getStatus();
			$statuses[] = $controller->run(id: $uuid)->getStatus();
			$statuses[] = $controller->certificate(id: $uuid)->getStatus();
		}

		$this->assertSame(array_fill(0, 14, 403), $statuses);
		$this->assertSame($before, $this->files[11]['content']);
		$this->assertSame('not_allowed', $this->controller(userId: 'bob')->index()->getData()['reason']);

	}//end testOutsidersGet403OnEveryRoute()

	/**
	 * A request without a ground is 400, a run before the preview 409, an unknown request 404.
	 *
	 * @return void
	 */
	public function testMisuseIsRefusedWithItsOwnStatus(): void {
		$uuid = $this->placed();

		$this->assertSame(400, $this->controller(userId: 'petra', params: ['subject' => self::PERSON])->create()->getStatus());
		$this->assertSame(409, $this->controller(userId: 'petra')->run(id: $uuid)->getStatus());
		$this->assertSame(404, $this->controller(userId: 'petra')->show(id: 'no-such-request')->getStatus());
		$this->assertStringContainsString(self::PERSON, $this->files[11]['content']);

	}//end testMisuseIsRefusedWithItsOwnStatus()

	/**
	 * An unreadable entity catalogue is 503 and says nothing was changed.
	 *
	 * @return void
	 */
	public function testAnUnreadableCatalogueIs503(): void {
		$uuid = $this->placed();
		$this->catalogueDown = true;

		$response = $this->controller(userId: 'petra')->preview(id: $uuid);

		$this->assertSame(503, $response->getStatus());
		$this->assertSame('catalogue_unavailable', $response->getData()['reason']);

	}//end testAnUnreadableCatalogueIs503()

	/**
	 * Record, preview, run and certificate answer 201, 200, 200, 200 and the person is gone.
	 *
	 * @return void
	 */
	public function testTheWholeRunAnswersOk(): void {
		$uuid = $this->placed();

		$preview = $this->controller(userId: 'petra')->preview(id: $uuid);
		$run = $this->controller(userId: 'petra')->run(id: $uuid);
		$certificate = $this->controller(userId: 'petra')->certificate(id: $uuid);

		$this->assertSame([200, 200, 200], [$preview->getStatus(), $run->getStatus(), $certificate->getStatus()]);
		$this->assertStringNotContainsString(self::PERSON, $this->files[11]['content']);
		$this->assertTrue($run->getData()['certificate']['complete']);

	}//end testTheWholeRunAnswersOk()
}//end class
