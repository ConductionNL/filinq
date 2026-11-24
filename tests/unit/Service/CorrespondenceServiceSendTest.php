<?php

/**
 * Unit tests for sending a composed letter by email
 *
 * The real CorrespondenceService composes and logs; the real
 * CorrespondenceSendService reads the recipient and sends. Only the
 * boundaries are doubled: OpenRegister's object service, the template store,
 * the renderer, Nextcloud's mailer and the system configuration.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/tasks.md#task-1
 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/tasks.md#task-2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\BackgroundJob\JobQueue;
use OCA\Filinq\Exception\CorrespondenceSendRefused;
use OCA\Filinq\Service\CorrespondenceSendService;
use OCA\Filinq\Service\CorrespondenceService;
use OCA\Filinq\Service\DataResolverService;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\Support\OpenRegisterAccess;
use OCA\Filinq\Service\Support\PdfRendering;
use OCA\Filinq\Service\Support\TemplateSources;
use OCA\Filinq\Service\TemplateRenderer;
use OCA\Filinq\Service\TemplateService;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\Mail\IAttachment;
use OCP\Mail\IMailer;
use OCP\Mail\IMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * A letter goes to the recipient's address and is logged as sent; a send that
 * cannot happen is refused before anything is composed and logs nothing as sent.
 *
 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/specs/letter-correspondence-generation/spec.md
 */
class CorrespondenceServiceSendTest extends TestCase {

	/**
	 * The recipient reference every test sends to.
	 *
	 * @var array<int, array<string, string>>
	 */
	private const RECIPIENT = [['register' => 'brp', 'schema' => 'persoon', 'id' => 'p-1']];

	/**
	 * The service under test.
	 *
	 * @var CorrespondenceSendService
	 */
	private CorrespondenceSendService $sender;

	/**
	 * OpenRegister's object service.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService $objects;

	/**
	 * The template store.
	 *
	 * @var TemplateService&MockObject
	 */
	private TemplateService $templates;

	/**
	 * The template renderer.
	 *
	 * @var TemplateRenderer&MockObject
	 */
	private TemplateRenderer $renderer;

	/**
	 * The PDF renderer.
	 *
	 * @var PdfService&MockObject
	 */
	private PdfService $pdf;

	/**
	 * Nextcloud's mailer.
	 *
	 * @var IMailer&MockObject
	 */
	private IMailer $mailer;

	/**
	 * The message the mailer creates.
	 *
	 * @var IMessage&MockObject
	 */
	private IMessage $message;

	/**
	 * The system configuration.
	 *
	 * @var IConfig&MockObject
	 */
	private IConfig $config;

	/**
	 * Every correspondence entry the service saved.
	 *
	 * @var list<array<string, mixed>>
	 */
	private array $logged = [];

	/**
	 * Build the real services over doubled boundaries.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->objects   = $this->createMock(ObjectService::class);
		$this->templates = $this->createMock(TemplateService::class);
		$this->renderer  = $this->createMock(TemplateRenderer::class);
		$this->pdf       = $this->createMock(PdfService::class);
		$this->mailer    = $this->createMock(IMailer::class);
		$this->message   = $this->createMock(IMessage::class);
		$this->config    = $this->createMock(IConfig::class);

		$this->templates->method('getTemplate')->willReturn(['id' => 'tmpl-1', 'name' => 'Uitnodiging gesprek', 'content' => '<p>{{ naam }}</p>']);
		$this->renderer->method('renderTemplate')->willReturn('<p>Beste Jan,</p><p>Wij nodigen u uit.</p>');
		$this->mailer->method('createMessage')->willReturn($this->message);
		$this->mailer->method('validateMailAddress')->willReturnCallback(static fn (string $address): bool => str_contains($address, '@'));
		$this->config->method('getSystemValueString')->willReturn('smtp');

		$this->objects->method('saveObject')->willReturnCallback(
			function (array $object) {
				$this->logged[] = $object;
				$entity = $this->createMock(ObjectEntity::class);
				$entity->method('jsonSerialize')->willReturn(['id' => 'log-' . count($this->logged)] + $object);
				return $entity;
			}
		);

		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn(['openregister']);
		$appConfig = $this->createMock(IAppConfig::class);
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			fn (string $class) => match ($class) {
				'OCA\OpenRegister\Service\ObjectService' => $this->objects,
				IAppConfig::class => $appConfig,
				default => null,
			}
		);

		$data = $this->createMock(DataResolverService::class);
		$data->method('resolve')->willReturn(['data' => ['naam' => 'Jan'], 'errors' => [], 'warnings' => []]);
		$logger = $this->createMock(LoggerInterface::class);

		$correspondence = new CorrespondenceService(
			new TemplateSources(templates: $this->templates, data: $data),
			new PdfRendering(renderer: $this->renderer, pdf: $this->pdf),
			new OpenRegisterAccess(apps: $appManager, container: $container),
			new JobQueue(jobList: $this->createMock(IJobList::class)),
			$logger,
			$appConfig
		);

		$this->sender = new CorrespondenceSendService(
			correspondence: $correspondence,
			templates: $this->templates,
			objects: new DocumentObjectServiceResolver(container: $container, appManager: $appManager),
			mailer: $this->mailer,
			config: $this->config,
			logger: $logger
		);

	}//end setUp()

	/**
	 * The recipient OpenRegister answers, holding these fields.
	 *
	 * @param array<string, mixed>|null $fields The recipient's data, or null when the caller cannot read it.
	 *
	 * @return void
	 */
	private function recipient(?array $fields): void {
		$entity = null;
		if ($fields !== null) {
			$entity = $this->createMock(ObjectEntity::class);
			$entity->method('getObject')->willReturn($fields);
		}

		$this->objects->method('find')->willReturn($entity);

	}//end recipient()

	/**
	 * One message goes to the recipient's address with the composed body, and the
	 * one correspondence entry says sent, to whom, when and by whom.
	 *
	 * @return void
	 */
	public function testALetterGoesToTheRecipientAndIsLoggedAsSent(): void {
		$this->recipient(['naam' => 'Jan', 'email' => 'j.jansen@example.nl']);
		$this->message->expects($this->once())->method('setTo')->with(['j.jansen@example.nl']);
		$this->message->expects($this->once())->method('setSubject')->with('Uitnodiging gesprek');
		$this->message->expects($this->once())->method('setHtmlBody')->with('<p>Beste Jan,</p><p>Wij nodigen u uit.</p>');
		$this->message->expects($this->once())->method('setPlainBody')->with("Beste Jan,\nWij nodigen u uit.");
		$this->message->expects($this->never())->method('attach');
		$this->mailer->expects($this->once())->method('send')->with($this->message)->willReturn([]);

		$result = $this->sender->send(templateId: 'tmpl-1', dataRefs: self::RECIPIENT, options: ['userId' => 'officer']);

		$this->assertSame('sent', $result['status']);
		$this->assertSame('j.jansen@example.nl', $result['to']);
		$this->assertCount(1, $this->logged, 'one sent letter is one correspondence entry');
		$entry = $this->logged[0];
		$this->assertSame('sent', $entry['status']);
		$this->assertSame('email', $entry['format']);
		$this->assertSame('p-1', $entry['recipientId']);
		$this->assertSame('j.jansen@example.nl', $entry['sentTo']);
		$this->assertSame('officer', $entry['sentBy']);
		$this->assertSame($result['sentAt'], $entry['sentAt']);
		$this->assertNotFalse(\DateTimeImmutable::createFromFormat(\DATE_ATOM, $entry['sentAt']));

	}//end testALetterGoesToTheRecipientAndIsLoggedAsSent()

	/**
	 * Asked for, the PDF of the same letter goes along as one attachment.
	 *
	 * @return void
	 */
	public function testThePdfGoesAlong(): void {
		$this->recipient(['emailAddress' => 'j.jansen@example.nl']);
		$this->pdf->expects($this->once())->method('renderPdf')->willReturn('%PDF-letter%');
		$attachment = $this->createMock(IAttachment::class);
		$this->mailer->expects($this->once())->method('createAttachment')
			->with('%PDF-letter%', 'Uitnodiging gesprek.pdf', 'application/pdf')
			->willReturn($attachment);
		$this->message->expects($this->once())->method('attach')->with($attachment);
		$this->mailer->method('send')->willReturn([]);

		$result = $this->sender->send(templateId: 'tmpl-1', dataRefs: self::RECIPIENT, options: ['attachPdf' => true]);

		$this->assertSame('sent', $result['status']);
		$this->assertCount(1, $this->logged);

	}//end testThePdfGoesAlong()

	/**
	 * A recipient without an email address is refused with 422; nothing is sent or logged.
	 *
	 * @return void
	 */
	public function testARecipientWithoutAnAddressIsRefused(): void {
		$this->recipient(['naam' => 'Jan', 'email' => '']);
		$this->mailer->expects($this->never())->method('send');
		$this->renderer->expects($this->never())->method('renderTemplate');

		try {
			$this->sender->send(templateId: 'tmpl-1', dataRefs: self::RECIPIENT);
			$this->fail('a recipient without an address must be refused');
		} catch (CorrespondenceSendRefused $refused) {
			$this->assertSame(422, $refused->getCode());
			$this->assertSame(CorrespondenceSendService::NO_ADDRESS, $refused->getMessage());
		}

		$this->assertSame([], $this->logged);

	}//end testARecipientWithoutAnAddressIsRefused()

	/**
	 * A recipient the caller cannot read is a 404 before anything is composed.
	 *
	 * @return void
	 */
	public function testAnUnreadableRecipientIs404BeforeComposing(): void {
		$this->recipient(null);
		$this->templates->expects($this->never())->method('getTemplate');
		$this->mailer->expects($this->never())->method('send');

		try {
			$this->sender->send(templateId: 'tmpl-1', dataRefs: self::RECIPIENT);
			$this->fail('an unreadable recipient must be refused');
		} catch (CorrespondenceSendRefused $refused) {
			$this->assertSame(404, $refused->getCode());
		}

		$this->assertSame([], $this->logged);

	}//end testAnUnreadableRecipientIs404BeforeComposing()

	/**
	 * An object service that throws on the read is the same 404.
	 *
	 * @return void
	 */
	public function testAFailingReadIsThe404(): void {
		$this->objects->method('find')->willThrowException(new RuntimeException('access denied'));

		$this->expectException(CorrespondenceSendRefused::class);
		$this->expectExceptionCode(404);

		$this->sender->send(templateId: 'tmpl-1', dataRefs: self::RECIPIENT);

	}//end testAFailingReadIsThe404()

	/**
	 * A request that names no recipient is a 400.
	 *
	 * @return void
	 */
	public function testNoRecipientIs400(): void {
		$this->objects->expects($this->never())->method('find');

		$this->expectException(CorrespondenceSendRefused::class);
		$this->expectExceptionCode(400);

		$this->sender->send(templateId: 'tmpl-1', dataRefs: [['register' => 'brp', 'schema' => 'persoon', 'id' => ' ']]);

	}//end testNoRecipientIs400()

	/**
	 * An instance whose mail mode is `null` sends no mail: 422 naming that, nothing sent.
	 *
	 * @return void
	 */
	public function testNoMailServerIsRefused(): void {
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValueString')->with('mail_smtpmode', 'smtp')->willReturn('null');
		$this->recipient(['email' => 'j.jansen@example.nl']);
		$this->mailer->expects($this->never())->method('send');
		$sender = $this->senderWith(config: $config);

		try {
			$sender->send(templateId: 'tmpl-1', dataRefs: self::RECIPIENT);
			$this->fail('an instance without a mail server must refuse');
		} catch (CorrespondenceSendRefused $refused) {
			$this->assertSame(422, $refused->getCode());
			$this->assertSame(CorrespondenceSendService::NO_MAIL_SERVER, $refused->getMessage());
		}

		$this->assertSame([], $this->logged);

	}//end testNoMailServerIsRefused()

	/**
	 * A mailer that throws logs the send as failed with the reason, and nothing as sent.
	 *
	 * @return void
	 */
	public function testAFailingMailServerIsLoggedAsFailed(): void {
		$this->recipient(['email' => 'j.jansen@example.nl']);
		$this->mailer->method('send')->willThrowException(new RuntimeException('Connection refused by smtp.example.nl'));

		$result = $this->sender->send(templateId: 'tmpl-1', dataRefs: self::RECIPIENT, options: ['userId' => 'officer']);

		$this->assertSame('failed', $result['status']);
		$this->assertNull($result['sentAt']);
		$this->assertCount(1, $this->logged);
		$this->assertSame('failed', $this->logged[0]['status']);
		$this->assertSame('Connection refused by smtp.example.nl', $this->logged[0]['errorMessage']);
		$this->assertArrayNotHasKey('sentTo', $this->logged[0]);
		$this->assertArrayNotHasKey('sentAt', $this->logged[0]);

	}//end testAFailingMailServerIsLoggedAsFailed()

	/**
	 * A mailer that takes the message but refuses the address is a failed send too.
	 *
	 * @return void
	 */
	public function testARefusedAddressIsAFailedSend(): void {
		$this->recipient(['email' => 'j.jansen@example.nl']);
		$this->mailer->method('send')->willReturn(['j.jansen@example.nl']);

		$result = $this->sender->send(templateId: 'tmpl-1', dataRefs: self::RECIPIENT);

		$this->assertSame('failed', $result['status']);
		$this->assertSame(CorrespondenceSendService::ADDRESS_REFUSED, $this->logged[0]['errorMessage']);

	}//end testARefusedAddressIsAFailedSend()

	/**
	 * The preview names the address and the subject, and composes, sends and logs nothing.
	 *
	 * @return void
	 */
	public function testThePreviewNamesAddressAndSubjectOnly(): void {
		$this->recipient(['email' => ' j.jansen@example.nl ']);
		$this->renderer->expects($this->never())->method('renderTemplate');
		$this->mailer->expects($this->never())->method('send');

		$preview = $this->sender->preview(templateId: 'tmpl-1', dataRefs: self::RECIPIENT);

		$this->assertSame(['to' => 'j.jansen@example.nl', 'subject' => 'Uitnodiging gesprek'], $preview);
		$this->assertSame([], $this->logged);

	}//end testThePreviewNamesAddressAndSubjectOnly()

	/**
	 * A send works without the email leaf: the service depends on Nextcloud's
	 * mailer only, so no link is attempted and the send is logged as sent.
	 *
	 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/tasks.md#task-4
	 *
	 * @return void
	 */
	public function testTheSendNeedsNoEmailLeafAndAttemptsNoLink(): void {
		$constructor = (new \ReflectionClass(CorrespondenceSendService::class))->getConstructor();
		$this->assertNotNull(actual: $constructor);
		foreach ($constructor->getParameters() as $parameter) {
			$type = (string) $parameter->getType();
			$this->assertStringNotContainsStringIgnoringCase(
				needle: 'Email',
				haystack: $type,
				message: 'the send takes no email-leaf service: '.$parameter->getName()
			);
			$this->assertStringNotContainsStringIgnoringCase(
				needle: 'Link',
				haystack: $type,
				message: 'the send takes no link service: '.$parameter->getName()
			);
		}

		$this->recipient(fields: ['email' => 'j.jansen@example.nl']);
		$this->mailer->expects($this->once())->method('send')->willReturn([]);

		$result = $this->sender->send(templateId: 'tmpl-1', dataRefs: self::RECIPIENT, options: ['userId' => 'officer']);

		$this->assertSame(expected: 'sent', actual: $result['status']);
		$this->assertCount(expectedCount: 1, haystack: $this->logged);
		$this->assertArrayNotHasKey(key: 'emailLink', array: $this->logged[0]);

	}//end testTheSendNeedsNoEmailLeafAndAttemptsNoLink()

	/**
	 * The send service over the same doubles, with another configuration.
	 *
	 * @param IConfig $config The configuration.
	 *
	 * @return CorrespondenceSendService
	 */
	private function senderWith(IConfig $config): CorrespondenceSendService {
		$reflection = new \ReflectionClass($this->sender);
		$args = [];
		foreach (['correspondence', 'templates', 'objects', 'mailer', 'logger'] as $name) {
			$property = $reflection->getProperty($name);
			$args[$name] = $property->getValue($this->sender);
		}

		return new CorrespondenceSendService(
			correspondence: $args['correspondence'],
			templates: $args['templates'],
			objects: $args['objects'],
			mailer: $args['mailer'],
			config: $config,
			logger: $args['logger']
		);

	}//end senderWith()
}//end class
