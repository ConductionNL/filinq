<?php

/**
 * Thread headers of a raw email
 *
 * OpenRegister's EML parser surfaces the Message-ID but not In-Reply-To or
 * References, so filinq reads those id-shaped headers from the raw header
 * block itself: header lines only, never the body. When OpenRegister's
 * parser returns them as header extras this class can go.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\EmailIngestion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\EmailIngestion;

/**
 * Message-ID, In-Reply-To, References and the thread key.
 */
class EmailThreadHeaders {

	/**
	 * How many bytes of a message are read for its header block.
	 *
	 * @var int
	 */
	public const HEADER_BYTES = 65536;

	/**
	 * Read the thread headers from the start of a raw message.
	 *
	 * @param string $raw The first bytes of the .eml.
	 *
	 * @return array{messageId: string, inReplyTo: string, references: list<string>} Ids without angle brackets.
	 *
	 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-2
	 */
	public function read(string $raw): array {
		$headers = $this->headerLines(raw: $raw);

		$inReplyTo = $this->ids(value: ($headers['in-reply-to'] ?? ''));

		return [
			'messageId' => ($this->ids(value: ($headers['message-id'] ?? ''))[0] ?? ''),
			'inReplyTo' => ($inReplyTo[0] ?? ''),
			'references' => $this->ids(value: ($headers['references'] ?? '')),
		];

	}//end read()

	/**
	 * The thread key: the first reference, else in-reply-to, else the own id.
	 *
	 * @param string       $messageId  The message's own id.
	 * @param string       $inReplyTo  The id it answers.
	 * @param list<string> $references The thread, oldest first.
	 *
	 * @return string The key, brackets stripped and lower case; '' when there is no id at all.
	 *
	 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-2
	 */
	public function threadKey(string $messageId, string $inReplyTo, array $references): string {
		foreach ([($references[0] ?? ''), $inReplyTo, $messageId] as $candidate) {
			$key = strtolower(trim(trim($candidate), '<>'));
			if ($key !== '') {
				return $key;
			}
		}

		return '';

	}//end threadKey()

	/**
	 * The unfolded header lines, by lower-case name; the first occurrence wins.
	 *
	 * @param string $raw The raw message start.
	 *
	 * @return array<string, string> The headers.
	 */
	private function headerLines(string $raw): array {
		$raw = substr($raw, 0, self::HEADER_BYTES);
		$parts = preg_split('/\r?\n\r?\n/', $raw, 2);
		$block = preg_replace('/\r?\n[ \t]+/', ' ', ($parts[0] ?? ''));

		$headers = [];
		foreach (preg_split('/\r?\n/', (string) $block) as $line) {
			$colon = strpos($line, ':');
			if ($colon === false || $colon === 0) {
				continue;
			}

			$name = strtolower(trim(substr($line, 0, $colon)));
			if (isset($headers[$name]) === false) {
				$headers[$name] = trim(substr($line, $colon + 1));
			}
		}

		return $headers;

	}//end headerLines()

	/**
	 * The message ids in a header value.
	 *
	 * @param string $value The value.
	 *
	 * @return list<string> The ids, without brackets.
	 */
	private function ids(string $value): array {
		if (preg_match_all('/<([^<>\s]+)>/', $value, $matches) > 0) {
			return array_values($matches[1]);
		}

		return array_values(array_filter(preg_split('/\s+/', trim($value)), static fn (string $id): bool => $id !== ''));

	}//end ids()
}//end class
