<?php

/**
 * Golden: intake#assign as Nextcloud dispatches it
 *
 * Records, for every request shape, the response and what the intake service
 * was asked, recorded on the action that took its seven request values as
 * arguments. The cases include unauthenticated calls, values of the wrong
 * type, every withAttachments spelling, service refusals and forged
 * identity fields in the request.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\IntakeController;
use OCA\Filinq\Exception\IntakeRefusedException;
use OCA\Filinq\Kernel\Contracts\IntakeDetachmentServiceInterface;
use OCA\Filinq\Kernel\Contracts\IntakeNotificationReachInterface;
use OCA\Filinq\Kernel\Contracts\IntakeRoutingServiceInterface;
use OCA\Filinq\Kernel\Contracts\IntakeServiceInterface;
use OCA\Filinq\Kernel\Contracts\PartySuggestionServiceInterface;
use OCA\Filinq\Tests\Unit\Support\NextcloudDispatch;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;

/**
 * Golden for intake#assign.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Controller\IntakeController
 * @uses   \OCA\Filinq\Exception\IntakeRefusedException
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class IntakeAssignGoldenTest extends TestCase {

	private const GOLDEN = __DIR__ . '/golden/intake-assign.json';

	/**
	 * The values each request field takes in the cases.
	 */
	private const VALUES = [
		'missing'   => '(missing)',
		'null'      => null,
		'empty'     => '',
		'text'      => 'abc',
		'false'     => 'false',
		'true'      => 'true',
		'zeroText'  => '0',
		'oneText'   => '1',
		'zero'      => 0,
		'one'       => 1,
		'float'     => 1.5,
		'boolFalse' => false,
		'boolTrue'  => true,
		'list'      => ['a'],
		'emptyList' => [],
	];

	/**
	 * The service calls of the current case.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $calls = [];

	/**
	 * Every case of the golden.
	 *
	 * @return array<string, array{0: bool, 1: array<string, mixed>, 2: string}>
	 */
	public static function cases(): array {
		$valid = [
			'uuid'            => 'doc-1',
			'register'        => 'zaken',
			'schema'          => 'zaak',
			'id'              => 'z-9',
			'declaringApp'    => 'dossiq',
			'typeReference'   => 'zaaktype-1',
			'withAttachments' => true,
		];

		$cases = [
			'valid'                => [true, $valid, 'ok'],
			'valid anonymous'      => [false, $valid, 'ok'],
			'only uuid'            => [true, ['uuid' => 'doc-1'], 'ok'],
			'no uuid'              => [true, ['register' => 'zaken'], 'ok'],
			'no uuid anonymous'    => [false, ['register' => 'zaken'], 'ok'],
			'int uuid'             => [true, ['uuid' => 5], 'ok'],
			'refused 403'          => [true, $valid, 'refused403'],
			'refused 409'          => [true, $valid, 'refused409'],
			'runtime failure'      => [true, $valid, 'runtime'],
			'object missing'       => [true, $valid, 'missing'],
			'refused no bijlagen'  => [true, (['withAttachments' => false] + $valid), 'refused409'],
			'forged identity'      => [
				true,
				($valid + ['userId' => 'mallory', 'user' => 'mallory', 'requestedBy' => 'mallory', 'owner' => 'mallory', 'uid' => 'mallory']),
				'ok',
			],
			'forged identity anonymous' => [
				false,
				($valid + ['userId' => 'admin', 'user' => 'admin', 'requestedBy' => 'admin', 'owner' => 'admin', 'uid' => 'admin']),
				'ok',
			],
		];

		$fields = ['register', 'schema', 'id', 'declaringApp', 'typeReference', 'withAttachments'];
		foreach ($fields as $field) {
			foreach (self::VALUES as $label => $value) {
				$params = $valid;
				unset($params[$field]);
				if ($value !== '(missing)') {
					$params[$field] = $value;
				}

				$cases[$field.' '.$label] = [true, $params, 'ok'];
				$cases[$field.' '.$label.' anonymous'] = [false, $params, 'ok'];
			}
		}

		return $cases;

	}//end cases()

	/**
	 * Every case answers and asks as recorded.
	 *
	 * @return void
	 */
	public function testGolden(): void {
		$actual = [];
		foreach (self::cases() as $name => [$loggedIn, $params, $service]) {
			$this->calls = [];
			$controller  = $this->controller(loggedIn: $loggedIn, params: $params, service: $service);
			$response    = NextcloudDispatch::call($controller, 'assign', $params);
			$actual[$name] = [
				'response' => $this->describe(response: $response),
				'calls'    => $this->calls,
			];
		}

		if (getenv('GOLDEN_RECORD') === '1') {
			file_put_contents(self::GOLDEN, json_encode($actual, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
		}

		$recorded = json_decode((string) file_get_contents(self::GOLDEN), true);
		$this->assertSame($recorded, json_decode((string) json_encode($actual), true));

	}//end testGolden()

	/**
	 * The response's class, status and body.
	 *
	 * @param mixed $response The action's answer.
	 *
	 * @return array<string, mixed>
	 */
	private function describe(mixed $response): array {
		$this->assertInstanceOf(Response::class, $response);
		$data = null;
		if ($response instanceof JSONResponse) {
			$data = $response->getData();
		}

		return ['class' => get_class($response), 'status' => $response->getStatus(), 'data' => $data];

	}//end describe()

	/**
	 * The controller over a request holding the params and a recording service.
	 *
	 * @param bool                 $loggedIn Whether the session has a user.
	 * @param array<string, mixed> $params   The request parameters.
	 * @param string               $service  How the intake service answers.
	 *
	 * @return IntakeController
	 */
	private function controller(bool $loggedIn, array $params, string $service): IntakeController {
		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnCallback(
			static fn (string $key, mixed $default = null): mixed => (isset($params[$key]) === true ? $params[$key] : $default)
		);
		$request->method('getParams')->willReturn($params);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('clerk');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($loggedIn === true ? $user : null);

		$intake = $this->createMock(IntakeServiceInterface::class);
		foreach (['assign', 'assignWithAttachments'] as $method) {
			$intake->method($method)->willReturnCallback(
				fn (mixed ...$args): array => $this->answer(method: $method, args: $args, service: $service)
			);
		}

		return new IntakeController(
			appName: 'filinq',
			request: $request,
			intake: $intake,
			detachment: $this->createMock(IntakeDetachmentServiceInterface::class),
			routing: $this->createMock(IntakeRoutingServiceInterface::class),
			parties: $this->createMock(PartySuggestionServiceInterface::class),
			userSession: $session,
			reach: $this->createMock(IntakeNotificationReachInterface::class),
			groups: $this->createMock(IGroupManager::class),
		);

	}//end controller()

	/**
	 * Record one service call by the arguments passed, and answer it.
	 *
	 * @param string            $method  The service method.
	 * @param array<int, mixed> $args    The arguments passed.
	 * @param string            $service How the service answers.
	 *
	 * @return array<string, mixed>
	 */
	private function answer(string $method, array $args, string $service): array {
		$named = [];
		foreach ((new ReflectionMethod(IntakeServiceInterface::class, $method))->getParameters() as $index => $param) {
			if (array_key_exists($index, $args) === true) {
				$named[$param->getName()] = $args[$index];
			}
		}

		$this->calls[] = ['method' => $method, 'args' => $named];

		return match ($service) {
			'refused403' => throw new IntakeRefusedException(message: 'Not yours', status: 403),
			'refused409' => throw new IntakeRefusedException(message: 'Already filed', status: 409),
			'runtime'    => throw new RuntimeException('Store down'),
			'missing'    => throw new DoesNotExistException('Object not found'),
			default      => ['via' => $method, 'uuid' => $named['uuid'] ?? null, 'target' => $named['target'] ?? null],
		};

	}//end answer()
}//end class
