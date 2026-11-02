<?php

/**
 * OpenRegister's EML value classes, for the unit tests.
 *
 * EmlStructure, EmlBody, EmlAttachment and EmlParseException are copied from
 * OpenRegister development (lib/Service/TextExtraction and lib/Exception,
 * 8e001f4e) with their constructors and properties unchanged, so a test
 * cannot build a structure OpenRegister's parser would never return.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\OpenRegister\Exception {
use Exception;

/**
 * Thrown by parseEmlStructured() on malformed input.
 *
 * @category Exception
 * @package  OCA\OpenRegister\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://OpenRegister.app
 */
class EmlParseException extends Exception {
}//end class
}

namespace OCA\OpenRegister\Service\TextExtraction {
use JsonSerializable;

/**
 * A parsed email: headers, body and attachments.
 *
 * @category Service
 * @package  OCA\OpenRegister\Service\TextExtraction
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://OpenRegister.app
 */
final class EmlStructure implements JsonSerializable {

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed>      $headers     from, to, cc, subject, date, messageId.
	 * @param EmlBody                   $body        The body.
	 * @param array<int, EmlAttachment> $attachments The attachments.
	 */
	public function __construct(
		public readonly array $headers,
		public readonly EmlBody $body,
		public readonly array $attachments,
	) {
	}//end __construct()

	/**
	 * Serialise.
	 *
	 * @return array<string, mixed>
	 */
	public function jsonSerialize(): array {
		return [
			'headers' => $this->headers,
			'body' => $this->body,
			'attachments' => $this->attachments,
		];
	}//end jsonSerialize()
}//end class

/**
 * The body of a parsed email.
 *
 * @category Service
 * @package  OCA\OpenRegister\Service\TextExtraction
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://OpenRegister.app
 */
final class EmlBody implements JsonSerializable {

	/**
	 * Constructor.
	 *
	 * @param string|null $plainText The text part.
	 * @param string|null $html      The html part.
	 */
	public function __construct(
		public readonly ?string $plainText,
		public readonly ?string $html,
	) {
	}//end __construct()

	/**
	 * Serialise.
	 *
	 * @return array<string, mixed>
	 */
	public function jsonSerialize(): array {
		return [
			'plainText' => $this->plainText,
			'html' => $this->html,
		];
	}//end jsonSerialize()
}//end class

/**
 * One attachment of a parsed email.
 *
 * @category Service
 * @package  OCA\OpenRegister\Service\TextExtraction
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://OpenRegister.app
 */
final class EmlAttachment implements JsonSerializable {

	/**
	 * Constructor.
	 *
	 * @param string            $filename  Leaf file name.
	 * @param string            $mimeType  MIME type.
	 * @param string            $content   Raw bytes.
	 * @param bool              $isInline  Inline part.
	 * @param string|null       $contentId Content-ID.
	 * @param EmlStructure|null $nestedEml A nested message.
	 */
	public function __construct(
		public readonly string $filename,
		public readonly string $mimeType,
		public readonly string $content,
		public readonly bool $isInline,
		public readonly ?string $contentId,
		public readonly ?EmlStructure $nestedEml,
	) {
	}//end __construct()

	/**
	 * Serialise.
	 *
	 * @return array<string, mixed>
	 */
	public function jsonSerialize(): array {
		return [
			'filename' => $this->filename,
			'mimeType' => $this->mimeType,
			'content' => base64_encode($this->content),
			'isInline' => $this->isInline,
			'contentId' => $this->contentId,
			'nestedEml' => $this->nestedEml,
		];
	}//end jsonSerialize()
}//end class
}
