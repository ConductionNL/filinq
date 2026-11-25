<?php

/**
 * Unit tests for ObjectBoundMail
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

require_once __DIR__ . '/../../stubs/OpenRegisterMailStubs.php';

use OCA\Filinq\Service\ObjectBoundMail;
use OCA\Filinq\Service\ObjectBoundMailTarget;
use OCA\Filinq\Service\ObjectBoundMessage;
use OCA\OpenRegister\Db\Schema;
use OCA\OpenRegister\Db\SchemaMapper;
use OCA\OpenRegister\Kernel\Contracts\MailObjectBinding;
use OCA\OpenRegister\Notifications\Service\Notification\EmailSenderInterface;
use OCA\OpenRegister\Notifications\Service\Notification\MailAddressee;
use OCA\OpenRegister\Notifications\Service\Notification\MailContent;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Mail\IMailer;
use OCP\Mail\IMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * A mail is sent through OpenRegister bound to its object, so OpenRegister links it there.
 *
 * @spec openspec/changes/signer-consent-notifications-to-email-leaf/tasks.md#d-2-consent-notification-via-the-email-leaf-drive-notificationstatus-m
 */
class ObjectBoundMailTest extends TestCase {

	/**
	 * What OpenRegister's sender was handed: the recipient and the content.
	 *
	 * @var list<array{0: mixed, 1: MailContent}>
	 */
	private array $sent = [];

	/**
	 * Nextcloud's mailer, for the path without OpenRegister.
	 *
	 * @var IMailer&MockObject
	 */
	private IMailer $mailer;

	/**
	 * Set up the mailer.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->mailer = $this->createMock(originalClassName: IMailer::class);

	}//end setUp()

	/**
	 * OpenRegister's sender, recording what it is handed.
	 *
	 * @param string $outcome What it answers.
	 *
	 * @return EmailSenderInterface
	 */
	private function sender(string $outcome='dispatched'): EmailSenderInterface {
		$sender = $this->createMock(originalClassName: EmailSenderInterface::class);
		$record = function (mixed $recipient, MailContent $content) use ($outcome): string {
			$this->sent[] = [$recipient, $content];
			return $outcome;
		};
		$sender->method('sendMailTo')->willReturnCallback($record);
		$sender->method('sendMail')->willReturnCallback($record);

		return $sender;

	}//end sender()

	/**
	 * A mapper whose find() answers an entity with the given id.
	 *
	 * @param integer $id The id.
	 *
	 * @return SchemaMapper
	 */
	private function mapper(int $id): SchemaMapper {
		$entity = $this->createMock(originalClassName: Schema::class);
		$entity->method('getId')->willReturn($id);
		$mapper = $this->createMock(originalClassName: SchemaMapper::class);
		$mapper->method('find')->willReturn($entity);

		return $mapper;

	}//end mapper()

	/**
	 * The service, with the given OpenRegister services and signed-in user.
	 *
	 * @param array<string, mixed> $services The services the container answers, by id.
	 * @param string|null          $uid      The signed-in user, null for none.
	 *
	 * @return ObjectBoundMail
	 */
	private function mail(array $services, ?string $uid='jan'): ObjectBoundMail {
		$container = $this->createMock(originalClassName: ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			static function (string $id) use ($services): mixed {
				if (array_key_exists($id, $services) === false) {
					throw new RuntimeException('not registered: ' . $id);
				}

				return $services[$id];
			}
		);
		$user = null;
		if ($uid !== null) {
			$user = $this->createMock(originalClassName: IUser::class);
			$user->method('getUID')->willReturn($uid);
		}

		$session = $this->createMock(originalClassName: IUserSession::class);
		$session->method('getUser')->willReturn($user);

		return new ObjectBoundMail(container: $container, userSession: $session, mailer: $this->mailer);

	}//end mail()

	/**
	 * A message about one object.
	 *
	 * @param string $uuid     The object.
	 * @param string $register Its register.
	 * @param string $schema   Its schema.
	 *
	 * @return ObjectBoundMessage
	 */
	private static function message(string $uuid, string $register, string $schema): ObjectBoundMessage {
		return new ObjectBoundMessage(
			subject: 'Onderwerp',
			body: 'Tekst',
			object: new ObjectBoundMailTarget(uuid: $uuid, register: $register, schema: $schema)
		);

	}//end message()

	/**
	 * A mail to an address is bound to its object by numeric ids and the acting user.
	 *
	 * @return void
	 */
	public function testAMailToAnAddressIsBoundToItsObjectByNumericIds(): void {
		$outcome = $this->mail(services: [EmailSenderInterface::class => $this->sender()])
			->toAddress(address: 'a@b.nl', displayName: 'Bakkerij', message: self::message(uuid: 'uuid-1', register: '12', schema: '34'));

		$this->assertSame(expected: ObjectBoundMail::OUTCOME_DISPATCHED, actual: $outcome);
		[$recipient, $content] = $this->sent[0];
		$this->assertInstanceOf(expected: MailAddressee::class, actual: $recipient);
		$this->assertSame(expected: ['a@b.nl', 'Bakkerij'], actual: [$recipient->address, $recipient->displayName]);
		$this->assertSame(expected: ['Onderwerp', 'Tekst'], actual: [$content->subject, $content->body]);
		$this->assertInstanceOf(expected: MailObjectBinding::class, actual: $content->object);
		$binding = $content->object;
		$this->assertSame(
			expected: ['uuid-1', 12, 34, 'jan'],
			actual: [$binding->objectUuid, $binding->registerId, $binding->schemaId, $binding->linkedBy]
		);

	}//end testAMailToAnAddressIsBoundToItsObjectByNumericIds()

	/**
	 * A slug is resolved through OpenRegister's mappers; without a user, filinq is recorded.
	 *
	 * @return void
	 */
	public function testASlugIsResolvedThroughOpenRegistersMappers(): void {
		$outcome = $this->mail(
			services: [
				EmailSenderInterface::class => $this->sender(),
				'OCA\\OpenRegister\\Db\\RegisterMapper' => $this->mapper(id: 5),
				'OCA\\OpenRegister\\Db\\SchemaMapper' => $this->mapper(id: 9),
			],
			uid: null
		)->toUser(uid: 'marie', message: self::message(uuid: 'uuid-2', register: 'filinq', schema: 'signingRequest'));

		$this->assertSame(expected: 'dispatched', actual: $outcome);
		[$recipient, $content] = $this->sent[0];
		$this->assertSame(expected: 'marie', actual: $recipient);
		$this->assertSame(
			expected: [5, 9, ObjectBoundMail::SYSTEM_ACTOR],
			actual: [$content->object->registerId, $content->object->schemaId, $content->object->linkedBy]
		);

	}//end testASlugIsResolvedThroughOpenRegistersMappers()

	/**
	 * A register that cannot be resolved still sends the mail, unlinked.
	 *
	 * @return void
	 */
	public function testAnUnresolvableRegisterStillSendsTheMailUnlinked(): void {
		$outcome = $this->mail(services: [EmailSenderInterface::class => $this->sender(outcome: 'failed')])
			->toAddress(address: 'a@b.nl', displayName: '', message: self::message(uuid: 'uuid-3', register: 'missing', schema: ''));

		$this->assertSame(expected: 'failed', actual: $outcome);
		$this->assertNull(actual: $this->sent[0][1]->object);

	}//end testAnUnresolvableRegisterStillSendsTheMailUnlinked()

	/**
	 * An object without an id is sent unlinked.
	 *
	 * @return void
	 */
	public function testAnObjectWithoutAnIdIsSentUnlinked(): void {
		$this->mail(services: [EmailSenderInterface::class => $this->sender()])
			->toAddress(address: 'a@b.nl', displayName: '', message: self::message(uuid: '', register: '1', schema: '2'));

		$this->assertNull(actual: $this->sent[0][1]->object);

	}//end testAnObjectWithoutAnIdIsSentUnlinked()

	/**
	 * Without OpenRegister's sender an address is mailed through Nextcloud's mailer and a user is not mailed.
	 *
	 * @return void
	 */
	public function testWithoutOpenRegistersSenderTheMailerMailsTheAddress(): void {
		$message = $this->createMock(originalClassName: IMessage::class);
		$message->expects($this->once())->method('setTo')->with(['a@b.nl']);
		$message->expects($this->once())->method('setSubject')->with('Onderwerp');
		$message->expects($this->once())->method('setPlainBody')->with('Tekst');
		$this->mailer->method('createMessage')->willReturn($message);
		$this->mailer->expects($this->once())->method('send')->with($message);
		$mail = $this->mail(services: []);

		$this->assertSame(
			expected: ObjectBoundMail::OUTCOME_DISPATCHED,
			actual: $mail->toAddress(address: 'a@b.nl', displayName: '', message: self::message(uuid: 'u', register: '1', schema: '2'))
		);
		$this->assertSame(
			expected: ObjectBoundMail::OUTCOME_UNAVAILABLE,
			actual: $mail->toUser(uid: 'jan', message: self::message(uuid: 'u', register: '1', schema: '2'))
		);
		$this->assertSame(expected: [], actual: $this->sent);

	}//end testWithoutOpenRegistersSenderTheMailerMailsTheAddress()
}//end class
