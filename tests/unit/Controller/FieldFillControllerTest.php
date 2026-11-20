<?php

/**
 * Unit tests for FieldFillController
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\FieldFillController;
use OCA\Filinq\Exception\FieldFillRefused;
use OCA\Filinq\Service\FieldFill\FieldFillProfileService;
use OCA\Filinq\Service\FieldFill\SchemaFieldFillService;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The field fill endpoints: parameters in, statuses out.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class FieldFillControllerTest extends TestCase {

	/** @var SchemaFieldFillService&MockObject */
	private SchemaFieldFillService $fieldFill;

	/** @var FieldFillProfileService&MockObject */
	private FieldFillProfileService $profiles;

	/**
	 * Set up the doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->fieldFill = $this->createMock(SchemaFieldFillService::class);
		$this->profiles  = $this->createMock(FieldFillProfileService::class);

	}//end setUp()

	/**
	 * The controller over a request with these parameters.
	 *
	 * @param array<string, mixed> $params   The request parameters.
	 * @param bool                 $loggedIn Whether there is a session user.
	 *
	 * @return FieldFillController
	 */
	private function controller(array $params = [], bool $loggedIn = true): FieldFillController {
		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnCallback(static fn (string $key, mixed $default = null): mixed => ($params[$key] ?? $default));
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('clerk');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($loggedIn === true ? $user : null);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn (string $text): string => $text);

		return new FieldFillController(
			request: $request,
			fieldFill: $this->fieldFill,
			profiles: $this->profiles,
			userSession: $session,
			l10n: $l10n,
			logger: $this->createMock(LoggerInterface::class)
		);

	}//end controller()

	/**
	 * A suggestion request passes the record and file as the session user and answers 201.
	 *
	 * @return void
	 */
	public function testFieldsAnswersCreated(): void {
		$this->fieldFill->expects($this->once())->method('suggest')
			->with(7, 'subsidies', 'aanvraag', 'rec-1', 'clerk')
			->willReturn(['uuid' => 'sug-1']);

		$response = $this->controller(['fileId' => '7', 'register' => ' subsidies ', 'schema' => 'aanvraag', 'objectId' => 'rec-1'])->fields();

		$this->assertSame(201, $response->getStatus());
		$this->assertSame(['uuid' => 'sug-1'], $response->getData());

	}//end testFieldsAnswersCreated()

	/**
	 * A refusal answers its own status with its message; a non-string parameter reads as empty.
	 *
	 * @return void
	 */
	public function testARefusalAnswersItsStatus(): void {
		$this->fieldFill->method('suggest')->with(0, '', '', '', 'clerk')
			->willThrowException(new FieldFillRefused(message: 'No fields are declared for model filling on this schema.', code: 409));

		$response = $this->controller(['register' => ['x']])->fields();

		$this->assertSame(409, $response->getStatus());
		$this->assertSame(['error' => 'No fields are declared for model filling on this schema.'], $response->getData());

	}//end testARefusalAnswersItsStatus()

	/**
	 * Without a session user nothing runs.
	 *
	 * @return void
	 */
	public function testNoUserIsUnauthorised(): void {
		$this->fieldFill->expects($this->never())->method('suggest');
		$this->fieldFill->expects($this->never())->method('decide');

		$this->assertSame(401, $this->controller(loggedIn: false)->fields()->getStatus());
		$this->assertSame(401, $this->controller(loggedIn: false)->decide('sug-1')->getStatus());

	}//end testNoUserIsUnauthorised()

	/**
	 * Decide passes the decisions; a non-array body is no decisions; an unexpected failure is a 500.
	 *
	 * @return void
	 */
	public function testDecide(): void {
		$this->fieldFill->expects($this->exactly(2))->method('decide')
			->willReturnCallback(static function (string $id, array $decisions, string $userId): array {
				if ($decisions === []) {
					throw new RuntimeException('boom');
				}

				return ['id' => $id, 'decisions' => $decisions, 'by' => $userId];
			});

		$ok = $this->controller(['decisions' => ['amount' => 'accept']])->decide('sug-1');
		$this->assertSame(200, $ok->getStatus());
		$this->assertSame(['id' => 'sug-1', 'decisions' => ['amount' => 'accept'], 'by' => 'clerk'], $ok->getData());

		$failed = $this->controller(['decisions' => 'accept'])->decide('sug-1');
		$this->assertSame(500, $failed->getStatus());
		$this->assertSame(['error' => 'Filling fields from the document failed'], $failed->getData());

	}//end testDecide()

	/**
	 * Status, switch and profiles reach their services.
	 *
	 * @return void
	 */
	public function testAdminEndpoints(): void {
		$this->fieldFill->method('status')->willReturn(['enabled' => false]);
		$this->fieldFill->expects($this->once())->method('setEnabled')->with(true)->willReturn(['enabled' => true]);
		$this->profiles->method('all')->willReturn([['schema' => 's']]);
		$this->profiles->expects($this->once())->method('save')
			->with(['declaringApp' => 'dossiq', 'register' => 'r', 'schema' => 's', 'fields' => ['a'], 'active' => true], 'p-1')
			->willReturn(['uuid' => 'p-1']);

		$this->assertSame(['enabled' => false], $this->controller()->status()->getData());
		$this->assertSame(['enabled' => true], $this->controller(['enabled' => 'true'])->setEnabled()->getData());
		$this->assertSame(['results' => [['schema' => 's']]], $this->controller()->profiles()->getData());
		$saved = $this->controller(['declaringApp' => 'dossiq', 'register' => 'r', 'schema' => 's', 'fields' => ['a'], 'active' => '1'])->saveProfile('p-1');
		$this->assertSame(['uuid' => 'p-1'], $saved->getData());

	}//end testAdminEndpoints()
}//end class
