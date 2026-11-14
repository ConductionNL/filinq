<?php

/**
 * Unit tests for the four outward document events and their envelope.
 *
 * Each envelope holds exactly the common keys plus that event's own fields,
 * and nothing a caller passes beyond them: no file content, entity, e-mail
 * address or IP address can reach a receiver.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Event\Webhook
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Event\Webhook;

use OCA\Filinq\Event\Webhook\DocumentAnonymisedEvent;
use OCA\Filinq\Event\Webhook\DocumentGeneratedEvent;
use OCA\Filinq\Event\Webhook\DocumentRegisteredEvent;
use OCA\Filinq\Event\Webhook\DocumentSignedEvent;
use OCA\Filinq\Event\Webhook\DocumentWebhookEvent;
use OCA\Filinq\Event\Webhook\DocumentWebhookSubject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the document webhook events.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Event\Webhook
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
#[CoversClass(DocumentWebhookEvent::class)]
#[CoversClass(DocumentWebhookSubject::class)]
#[CoversClass(DocumentSignedEvent::class)]
#[CoversClass(DocumentAnonymisedEvent::class)]
#[CoversClass(DocumentGeneratedEvent::class)]
#[CoversClass(DocumentRegisteredEvent::class)]
class DocumentWebhookEventsTest extends TestCase {

	/**
	 * Each event, its name and the fields it adds.
	 *
	 * @return array<string, array{class-string<DocumentWebhookEvent>, string, array<string, mixed>}>
	 */
	public static function events(): array {
		return [
			'signed' => [
				DocumentSignedEvent::class,
				'document.signed',
				['signingRequestId' => 'sr-1', 'signedFileRef' => '991:2', 'signatureLevel' => 'AES'],
			],
			'anonymised' => [
				DocumentAnonymisedEvent::class,
				'document.anonymised',
				['sourceFileId' => 12, 'anonymisedFileId' => 13, 'verificationVerdict' => 'clean'],
			],
			'generated' => [
				DocumentGeneratedEvent::class,
				'document.generated',
				['templateId' => 'tpl-1', 'templateVersion' => 3, 'format' => 'pdf'],
			],
			'registered' => [
				DocumentRegisteredEvent::class,
				'document.registered',
				['direction' => 'inbound', 'unit' => 'post', 'registrationNumber' => 'IN-2026-0001'],
			],
		];

	}//end events()

	/**
	 * The envelope holds exactly the listed keys, in order, with their values.
	 *
	 * @param string               $class   The event class.
	 * @param string               $name    The event name.
	 * @param array<string, mixed> $details The event's own fields.
	 *
	 * @return void
	 */
	#[DataProvider('events')]
	public function testEnvelopeHoldsExactlyTheListedKeys(string $class, string $name, array $details): void {
		$event = new $class(
			new DocumentWebhookSubject(fileId: 77, name: 'contract.pdf', register: '4', schema: '9', id: 'uuid-1', organisation: 'org-1'),
			'alice',
			$details,
			'2026-11-13T10:00:00+00:00'
		);

		$expected = array_merge(
			[
				'event' => $name,
				'occurredAt' => '2026-11-13T10:00:00+00:00',
				'document' => ['fileId' => 77, 'name' => 'contract.pdf'],
				'record' => ['register' => '4', 'schema' => '9', 'id' => 'uuid-1'],
				'actor' => 'alice',
			],
			$details
		);

		self::assertSame($expected, $event->getPayload());
		self::assertSame($name, $event->getEventName());
		self::assertSame(array_keys($details), $event->detailKeys());
		self::assertSame('org-1', $event->getOrganisation());

	}//end testEnvelopeHoldsExactlyTheListedKeys()

	/**
	 * Fields beyond the event's own list never reach the envelope, and a
	 * missing one is null rather than absent.
	 *
	 * @param string               $class   The event class.
	 * @param string               $name    The event name.
	 * @param array<string, mixed> $details The event's own fields.
	 *
	 * @return void
	 */
	#[DataProvider('events')]
	public function testContentAndPersonalDataAreDropped(string $class, string $name, array $details): void {
		$leaky = [
			'content' => base64_encode('%PDF-1.7 secret bytes'),
			'entities' => [['type' => 'BSN', 'value' => '123456782']],
			'signerEmail' => 'signer@example.org',
			'ipAddress' => '192.0.2.10',
		];
		$event = new $class(
			new DocumentWebhookSubject(fileId: null, name: null, register: null, schema: null, id: null),
			null,
			$leaky,
			'2026-11-13T10:00:00+00:00'
		);

		$payload = $event->getPayload();
		$encoded = json_encode($payload, JSON_THROW_ON_ERROR);

		self::assertSame(
			array_merge(['event', 'occurredAt', 'document', 'record', 'actor'], array_keys($details)),
			array_keys($payload)
		);
		foreach (array_keys($details) as $key) {
			self::assertNull($payload[$key]);
		}

		self::assertNull($payload['actor']);
		self::assertNull($event->getOrganisation());
		foreach (['secret bytes', 'JVBERi', '123456782', 'signer@example.org', '192.0.2.10'] as $needle) {
			self::assertStringNotContainsString($needle, $encoded);
		}

	}//end testContentAndPersonalDataAreDropped()
}//end class
