<?php

/**
 * Email message reader
 *
 * Turns an .eml file into the envelope, thread and attachment fields of its
 * emailDocument record. Parsing is OpenRegister's (`parseEmlStructured`,
 * resolved by name so filinq loads without OpenRegister); the id-shaped
 * thread headers are read by EmailThreadHeaders from the raw header block.
 * Without OpenRegister's parser the email is still filed, with its thread
 * headers only: capturing the mail matters more than its envelope.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\EmailIngestion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/email-ingestion/tasks.md#2-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\EmailIngestion;

use DateTimeInterface;
use OCP\Files\File;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Reads an .eml into record fields.
 */
class EmailMessageReader {

	private const PARSER = 'OCA\\OpenRegister\\Service\\TextExtractionService';

	/**
	 * Constructor.
	 *
	 * @param ContainerInterface $container     Resolves OpenRegister's parser.
	 * @param EmailThreadHeaders $threadHeaders The raw thread headers.
	 * @param LoggerInterface    $logger        The logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ContainerInterface $container,
		private readonly EmailThreadHeaders $threadHeaders,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * The sha256 of the file as it arrived.
	 *
	 * @param File $file The file.
	 *
	 * @return string The hex digest.
	 *
	 * @spec openspec/changes/email-ingestion/tasks.md#2-1
	 */
	public function contentHash(File $file): string {
		$stream = $file->fopen('r');
		if (is_resource($stream) === false) {
			return hash('sha256', $file->getContent());
		}

		$context = hash_init('sha256');
		hash_update_stream($context, $stream);
		fclose($stream);

		return hash_final($context);

	}//end contentHash()

	/**
	 * The envelope, thread and attachment fields of an email.
	 *
	 * @param File $file The .eml.
	 *
	 * @return array<string, mixed> Record fields; optional ones only when known.
	 *
	 * @throws EmailNotFiled With reason unparseable when OpenRegister's parser refuses the file.
	 *
	 * @spec openspec/changes/email-ingestion/tasks.md#2-2
	 */
	public function read(File $file): array {
		$thread = $this->threadHeaders->read(raw: $this->head(file: $file));
		$fields = $this->envelope(structure: $this->parse(file: $file));
		$messageId = (string) ($fields['messageId'] ?? '');
		if ($messageId === '') {
			$messageId = $thread['messageId'];
		}

		$fields['messageId'] = $messageId;
		if ($thread['inReplyTo'] !== '') {
			$fields['inReplyTo'] = $thread['inReplyTo'];
		}

		$fields['references'] = $thread['references'];
		$fields['threadKey'] = $this->threadHeaders->threadKey(messageId: $messageId, inReplyTo: $thread['inReplyTo'], references: $thread['references']);

		return $fields;

	}//end read()

	/**
	 * The start of the file, where the headers are.
	 *
	 * @param File $file The file.
	 *
	 * @return string Up to HEADER_BYTES bytes.
	 */
	private function head(File $file): string {
		$stream = $file->fopen('r');
		if (is_resource($stream) === false) {
			return substr($file->getContent(), 0, EmailThreadHeaders::HEADER_BYTES);
		}

		$head = (string) fread($stream, EmailThreadHeaders::HEADER_BYTES);
		fclose($stream);

		return $head;

	}//end head()

	/**
	 * Parse through OpenRegister, when it is installed.
	 *
	 * @param File $file The file.
	 *
	 * @return object|null The EmlStructure, or null without the parser.
	 *
	 * @throws EmailNotFiled When the parser refuses the file.
	 */
	private function parse(File $file): ?object {
		try {
			$parser = $this->container->get(self::PARSER);
		} catch (Throwable) {
			return null;
		}

		if (method_exists($parser, 'parseEmlStructured') === false) {
			return null;
		}

		try {
			return $parser->parseEmlStructured($file);
		} catch (Throwable $e) {
			// The exception class only: a parser message may quote the mail.
			$this->logger->info('[EmailMessageReader] OpenRegister could not parse an email', ['fileId' => $file->getId(), 'exception' => get_class($e)]);
			throw new EmailNotFiled(EmailIngestionService::REASON_UNPARSEABLE);
		}

	}//end parse()

	/**
	 * Record fields from OpenRegister's EmlStructure.
	 *
	 * @param object|null $structure The structure.
	 *
	 * @return array<string, mixed> The fields.
	 */
	private function envelope(?object $structure): array {
		$fields = ['subject' => '', 'toAddresses' => [], 'ccAddresses' => [], 'attachmentCount' => 0, 'attachmentNames' => []];
		if ($structure === null) {
			return $fields;
		}

		$headers = (array) ($structure->headers ?? []);
		$fields['subject'] = mb_substr(trim((string) ($headers['subject'] ?? '')), 0, 998);
		$fields['fromAddress'] = mb_substr(trim((string) ($headers['from'] ?? '')), 0, 320);
		$fields['toAddresses'] = $this->addresses(value: ($headers['to'] ?? []));
		$fields['ccAddresses'] = $this->addresses(value: ($headers['cc'] ?? []));
		$fields['messageId'] = trim(trim((string) ($headers['messageId'] ?? '')), '<>');
		if (($headers['date'] ?? null) instanceof DateTimeInterface) {
			$fields['sentAt'] = $headers['date']->format(DateTimeInterface::ATOM);
		}

		$names = [];
		foreach ((array) ($structure->attachments ?? []) as $attachment) {
			if (is_object($attachment) === true && ($attachment->isInline ?? false) === false) {
				$names[] = mb_substr((string) ($attachment->filename ?? ''), 0, 255);
			}
		}

		$fields['attachmentCount'] = count($names);
		$fields['attachmentNames'] = $names;

		return array_filter($fields, static fn (mixed $value): bool => $value !== '' || is_array($value) === true);

	}//end envelope()

	/**
	 * An address list as trimmed strings.
	 *
	 * @param mixed $value A list or one string.
	 *
	 * @return list<string> The addresses.
	 */
	private function addresses(mixed $value): array {
		$list = [];
		if (is_string($value) === true) {
			$value = [$value];
		}

		foreach ((array) $value as $address) {
			$address = mb_substr(trim((string) $address), 0, 320);
			if ($address !== '') {
				$list[] = $address;
			}
		}

		return $list;

	}//end addresses()
}//end class
