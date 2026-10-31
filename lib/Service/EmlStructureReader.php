<?php

/**
 * EML Structure Reader
 *
 * Reads and normalises OpenRegister's redacted EML value objects
 * (`AnonymisedEmlStructure` / `AnonymisedEmlAttachment`) into the plain shapes
 * Filinq's PDF assembly renders. Extracted from
 * {@see EmlPdfAssemblyService} so that knowledge of OR's value-object shape
 * lives in exactly one place, shared by the envelope and attachment renderers.
 *
 * The reader performs NO redaction and NO rendering — it only reads.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * Normalises OpenRegister's redacted EML value objects.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 */
class EmlStructureReader {
	/**
	 * Read a public property from an OR value object with a default.
	 *
	 * Properties are accessed directly (the OR value objects expose readonly
	 * public properties); arrays are tolerated for test fixtures.
	 *
	 * @param object|array<string,mixed> $obj Source object or array.
	 * @param string $name Property/key name.
	 * @param mixed $default Default when absent.
	 *
	 * @return mixed The value or default.
	 */
	public function prop(object|array $obj, string $name, mixed $default): mixed {
		if (is_array($obj) === true) {
			return $obj[$name] ?? $default;
		}

		if (isset($obj->$name) === true) {
			return $obj->$name;
		}

		// Distinguish a present-but-null property from an absent one.
		if (property_exists($obj, $name) === true) {
			return $obj->$name;
		}

		return $default;
	}//end prop()

	/**
	 * Normalise OR's redacted headers map into the flat shape the template
	 * consumes (string From/Subject/Date/Reply-To, comma-joined To/Cc).
	 *
	 * @param object $result AnonymisedEmlStructure.
	 *
	 * @return array<string,string> Flattened header strings.
	 */
	public function headers(object $result): array {
		$raw = $this->prop(obj: $result, name: 'headers', default: []);
		if (is_array($raw) === false) {
			$raw = [];
		}

		return [
			'from' => $this->stringOrEmpty(value: $raw['from'] ?? null),
			'replyTo' => $this->stringOrEmpty(value: $raw['replyTo'] ?? null),
			'to' => $this->joinList(value: $raw['to'] ?? ''),
			'cc' => $this->joinList(value: $raw['cc'] ?? ''),
			'subject' => $this->stringOrEmpty(value: $raw['subject'] ?? null),
			'date' => $this->formatDate(date: $raw['date'] ?? ''),
		];

	}//end headers()

	/**
	 * The value when it is a string, else an empty string.
	 *
	 * @param mixed $value Raw header value.
	 *
	 * @return string The string, or ''.
	 */
	private function stringOrEmpty(mixed $value): string {
		if (is_string($value) === true) {
			return $value;
		}

		return '';
	}//end stringOrEmpty()

	/**
	 * Join an address list into one comma-separated string; a single string is trimmed.
	 *
	 * @param mixed $value Raw header value: a list of addresses or one string.
	 *
	 * @return string The joined addresses, or ''.
	 */
	private function joinList(mixed $value): string {
		if (is_array($value) === false) {
			return trim($this->stringOrEmpty(value: $value));
		}

		$parts = [];
		foreach ($value as $entry) {
			$trimmed = trim($this->stringOrEmpty(value: $entry));
			if ($trimmed !== '') {
				$parts[] = $trimmed;
			}
		}

		return implode(', ', $parts);
	}//end joinList()

	/**
	 * Format OR's redacted date string to `YYYY-MM-DD HH:MM`. Passes through
	 * unparseable values unchanged (they may be redacted to a placeholder).
	 *
	 * @param mixed $date Raw date value.
	 *
	 * @return string Formatted date, or the original/empty string.
	 */
	private function formatDate(mixed $date): string {
		if (is_string($date) === false || trim($date) === '') {
			return '';
		}

		$timestamp = strtotime($date);
		if ($timestamp === false) {
			return $date;
		}

		return date('Y-m-d H:i', $timestamp);
	}//end formatDate()

	/**
	 * Extract the attachments array from a structure (typed property or map).
	 *
	 * @param object $result AnonymisedEmlStructure.
	 *
	 * @return array<int, object> Attachment value objects.
	 */
	public function attachments(object $result): array {
		$attachments = $this->prop(obj: $result, name: 'attachments', default: []);
		if (is_array($attachments) === false) {
			return [];
		}

		return array_values(array_filter($attachments, 'is_object'));
	}//end attachments()

	/**
	 * Extract the inline-image map (contentId => redacted bytes).
	 *
	 * @param object $result AnonymisedEmlStructure.
	 *
	 * @return array<string, string> Inline-image map.
	 */
	public function inlineImages(object $result): array {
		$map = $this->prop(obj: $result, name: 'inlineImages', default: []);
		if (is_array($map) === false) {
			return [];
		}

		$clean = [];
		foreach ($map as $key => $value) {
			if (is_string($value) === true) {
				$clean[(string)$key] = $value;
			}
		}

		return $clean;
	}//end inlineImages()
}//end class
