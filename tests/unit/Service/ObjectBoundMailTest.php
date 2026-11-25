<?php

/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

require_once __DIR__ . '/../../stubs/OpenRegisterMailStubs.php';

use OCA\Filinq\Service\ObjectBoundMail;
use OCA\Filinq\Service\ObjectBoundMailTarget;
use OCA\OpenRegister\Kernel\Contracts\MailObjectBinding;
use OCA\OpenRegister\Notifications\Service\Notification\EmailSenderInterface;
use OCA\OpenRegister\Notifications\Service\Notification\MailAddressee;
use OCA\OpenRegister\Notifications\Service\Notification\MailContent;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Service\ObjectBoundMail
 * @covers \OCA\Filinq\Service\ObjectBoundMailTarget
 */
class ObjectBoundMailTest extends TestCase {

	/** @var list<array{0: string, 1: mixed, 2: MailContent}> */
	private array $sent = [];

	private function sender(string $outcome = 'dispatched'): EmailSenderInterface {
		$test = $this;

		return new class ($test, $outcome) implements EmailSenderInterface {
			public function __construct(private ObjectBoundMailTest $test, private string $outcome) {
			}

			public function send(string $uid, string $subject, string $body): string {
				return $this->outcome;
			}

			public function sendMail(string $uid, MailContent $content): string {
				$this->test->record($uid, $content);
				return $this->outcome;
			}

			public function sendMailTo(MailAddressee $addressee, MailContent $content): string {
				$this->test->record($addressee, $content);
				return $this->outcome;
			}
		};
	}

	public function record(mixed $to, MailContent $content): void {
		$this->sent[] = [$to, $content];
	}

	/**
	 * @param array<string, mixed> $services
	 */
	private function mail(array $services, ?string $uid = 'jan'): ObjectBoundMail {
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(static function (string $id) use ($services): mixed {
			if (array_key_exists($id, $services) === false) {
				throw new RuntimeException('not registered: ' . $id);
			}

			return $services[$id];
		});
		$session = $this->createMock(IUserSession::class);
		$user = null;
		if ($uid !== null) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($uid);
		}

		$session->method('getUser')->willReturn($user);

		return new ObjectBoundMail(container: $container, userSession: $session);
	}

	private static function mapper(int $id): object {
		return new class ($id) {
			public function __construct(private int $id) {
			}

			public function find(string $ref): object {
				$id = $this->id;
				return new class ($id) {
					public function __construct(private int $id) {
					}

					public function getId(): int {
						return $this->id;
					}
				};
			}
		};
	}

	public function testAMailToAnAddressIsBoundToItsObjectByNumericIds(): void {
		$outcome = $this->mail([EmailSenderInterface::class => $this->sender()])
			->toAddress('a@b.nl', 'Bakkerij', 'Onderwerp', 'Tekst', new ObjectBoundMailTarget('uuid-1', '12', '34'));

		$this->assertSame(ObjectBoundMail::OUTCOME_DISPATCHED, $outcome);
		[$to, $content] = $this->sent[0];
		$this->assertSame(['a@b.nl', 'Bakkerij'], [$to->address, $to->displayName]);
		$this->assertSame(['Onderwerp', 'Tekst'], [$content->subject, $content->body]);
		$this->assertInstanceOf(MailObjectBinding::class, $content->object);
		$this->assertSame(['uuid-1', 12, 34, 'jan'], [$content->object->objectUuid, $content->object->registerId, $content->object->schemaId, $content->object->linkedBy]);
	}

	public function testASlugIsResolvedThroughOpenRegistersMappers(): void {
		$outcome = $this->mail([
			EmailSenderInterface::class => $this->sender(),
			'OCA\\OpenRegister\\Db\\RegisterMapper' => self::mapper(5),
			'OCA\\OpenRegister\\Db\\SchemaMapper' => self::mapper(9),
		], null)->toUser('marie', 'S', 'B', new ObjectBoundMailTarget('uuid-2', 'filinq', 'signingRequest'));

		$this->assertSame('dispatched', $outcome);
		[$to, $content] = $this->sent[0];
		$this->assertSame('marie', $to);
		$this->assertSame([5, 9, ObjectBoundMail::SYSTEM_ACTOR], [$content->object->registerId, $content->object->schemaId, $content->object->linkedBy]);
	}

	public function testAnUnresolvableRegisterStillSendsTheMailUnlinked(): void {
		$outcome = $this->mail([EmailSenderInterface::class => $this->sender('failed')])
			->toAddress('a@b.nl', '', 'S', 'B', new ObjectBoundMailTarget('uuid-3', 'missing', ''));

		$this->assertSame('failed', $outcome);
		$this->assertNull($this->sent[0][1]->object);
	}

	public function testARecordWithoutAnIdIsSentUnlinked(): void {
		$this->mail([EmailSenderInterface::class => $this->sender()])
			->toAddress('a@b.nl', '', 'S', 'B', ObjectBoundMailTarget::ofRecord(['id' => 7], '1', '2'));

		$this->assertNull($this->sent[0][1]->object);
	}

	public function testWithoutOpenRegistersSenderNothingIsSent(): void {
		$mail = $this->mail([]);

		$this->assertSame(ObjectBoundMail::OUTCOME_UNAVAILABLE, $mail->toAddress('a@b.nl', '', 'S', 'B', new ObjectBoundMailTarget('u', '1', '2')));
		$this->assertSame(ObjectBoundMail::OUTCOME_UNAVAILABLE, $mail->toUser('jan', 'S', 'B', new ObjectBoundMailTarget('u', '1', '2')));
		$this->assertSame([], $this->sent);
	}

	public function testTheTargetOfARecordPrefersItsUuid(): void {
		$target = ObjectBoundMailTarget::ofRecord(['uuid' => 'u-1', 'id' => 'i-1'], 'r', 's');

		$this->assertSame(['u-1', 'r', 's'], [$target->uuid, $target->register, $target->schema]);
	}
}
