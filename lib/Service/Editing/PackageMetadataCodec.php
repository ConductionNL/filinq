<?php

/**
 * DocuDesk PackageMetadataCodec
 *
 * Reads and writes document metadata — title, subject, creator, keywords,
 * description — in ODF and OOXML packages.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://docudesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Editing;

use RuntimeException;

/**
 * Document metadata, addressed by a format-neutral field name.
 *
 * Both families store the same handful of Dublin Core fields, in different parts
 * under different element names:
 *
 *   OOXML  docProps/core.xml   dc:title, dc:subject, dc:creator,
 *                              cp:keywords, dc:description
 *   ODF    meta.xml            dc:title, dc:subject, dc:creator,
 *                              meta:keyword, dc:description
 *
 * Callers name a field once (`title`, `subject`, …) and the codec resolves the
 * element. A caller that had to know `cp:keywords` for one format and
 * `meta:keyword` for the other would be writing per-format code, which is the
 * thing ADR-087 §2 exists to prevent — the formats differ, but the CAPABILITY does
 * not.
 *
 * Only these five fields are writable. `dcterms:created` / `dcterms:modified` and
 * ODF's `meta:editing-cycles` are deliberately excluded: they are a record of what
 * happened to the document, and letting an agent set them would make that record
 * a claim rather than a fact.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://docudesk.app
 *
 * @spec openspec/specs/document-rich-editing/spec.md
 */
class PackageMetadataCodec {

	/**
	 * Per-extension metadata part and element mapping.
	 *
	 * @var array<string, array{part: string, root: string, ns: string, fields: array<string, string>}>
	 */
	private const PACKAGES = [
		'docx' => [
			'part'   => 'docProps/core.xml',
			'root'   => 'cp:coreProperties',
			'ns'     => 'xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
				. 'xmlns:dc="http://purl.org/dc/elements/1.1/" '
				. 'xmlns:dcterms="http://purl.org/dc/terms/" '
				. 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"',
			'fields' => [
				'title'       => 'dc:title',
				'subject'     => 'dc:subject',
				'creator'     => 'dc:creator',
				'keywords'    => 'cp:keywords',
				'description' => 'dc:description',
			],
		],
		'odt'  => [
			'part'   => 'meta.xml',
			'root'   => 'office:document-meta',
			'ns'     => 'xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" '
				. 'xmlns:dc="http://purl.org/dc/elements/1.1/" '
				. 'xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0"',
			'fields' => [
				'title'       => 'dc:title',
				'subject'     => 'dc:subject',
				'creator'     => 'dc:creator',
				'keywords'    => 'meta:keyword',
				'description' => 'dc:description',
			],
		],
	];

	/**
	 * Constructor.
	 *
	 * @param PackagePartIo $io The package part reader/writer.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly PackagePartIo $io = new PackagePartIo(),
	) {
	}//end __construct()

	/**
	 * Whether metadata can be addressed for this extension.
	 *
	 * @param string $extension The file extension, without a leading dot.
	 *
	 * @return bool True when supported.
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function supports(string $extension): bool {
		return isset(self::PACKAGES[strtolower($extension)]);
	}//end supports()

	/**
	 * The field names this codec understands.
	 *
	 * @return array<int, string> The field names.
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function fields(): array {
		return array_keys(self::PACKAGES['docx']['fields']);
	}//end fields()

	/**
	 * Read a document's metadata.
	 *
	 * A field the document does not carry comes back as an empty string rather
	 * than being omitted, so a caller can tell "this document has no subject" from
	 * "I forgot to ask for the subject".
	 *
	 * @param string $packageBytes The raw package bytes.
	 * @param string $extension    The file extension.
	 *
	 * @return array<string, string> Field name => value.
	 *
	 * @throws RuntimeException When the extension is unsupported.
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function readMetadata(string $packageBytes, string $extension): array {
		$package = $this->packageFor(extension: $extension);

		$xml = '';
		if ($this->io->hasPart(packageBytes: $packageBytes, part: $package['part']) === true) {
			$xml = $this->io->readPart(packageBytes: $packageBytes, part: $package['part']);
		}

		$values = [];
		foreach ($package['fields'] as $field => $element) {
			$values[$field] = $this->readElement(xml: $xml, element: $element);
		}

		return $values;
	}//end readMetadata()

	/**
	 * Write metadata fields, leaving unnamed fields and every other part untouched.
	 *
	 * @param string                $packageBytes The raw package bytes.
	 * @param string                $extension    The file extension.
	 * @param array<string, string> $values       Field name => new value.
	 *
	 * @return array{bytes: string, written: array<int, string>}
	 *
	 * @throws RuntimeException When the extension or a field name is unsupported.
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function writeMetadata(string $packageBytes, string $extension, array $values): array {
		$package = $this->packageFor(extension: $extension);

		if ($values === []) {
			throw new RuntimeException('At least one metadata field is required.');
		}

		foreach (array_keys($values) as $field) {
			if (isset($package['fields'][$field]) === false) {
				throw new RuntimeException(
					sprintf(
						'Unknown metadata field "%s". This document supports: %s.',
						(string)$field,
						implode(', ', array_keys($package['fields']))
					)
				);
			}
		}

		$xml = $this->existingOrEmpty(packageBytes: $packageBytes, package: $package);

		$written = [];
		foreach ($values as $field => $value) {
			$xml       = $this->writeElement(
				xml: $xml,
				element: $package['fields'][$field],
				value: (string)$value
			);
			$written[] = (string)$field;
		}

		return [
			'bytes'   => $this->io->writePart(
				packageBytes: $packageBytes,
				part: $package['part'],
				xml: $xml
			),
			'written' => $written,
		];
	}//end writeMetadata()

	/**
	 * Return the existing metadata part, or a minimal well-formed one.
	 *
	 * A document with no metadata part is ordinary — PhpWord-generated ODF often
	 * has none — so this creates one rather than refusing. The namespace
	 * declarations must be present on the root or the written elements resolve to
	 * nothing and the suite silently ignores them.
	 *
	 * @param string $packageBytes The raw package bytes.
	 * @param array  $package      The package mapping.
	 *
	 * @return string The metadata XML.
	 */
	private function existingOrEmpty(string $packageBytes, array $package): string {
		if ($this->io->hasPart(packageBytes: $packageBytes, part: $package['part']) === true) {
			return $this->io->readPart(packageBytes: $packageBytes, part: $package['part']);
		}

		return sprintf(
			'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" . '<%s %s></%s>',
			$package['root'],
			$package['ns'],
			$package['root']
		);
	}//end existingOrEmpty()

	/**
	 * Read one element's text.
	 *
	 * @param string $xml     The metadata XML.
	 * @param string $element The element name.
	 *
	 * @return string The text, or an empty string.
	 */
	private function readElement(string $xml, string $element): string {
		$pattern = sprintf('#<%s(?:\s[^>]*)?>(.*?)</%s>#s', preg_quote($element, '#'), preg_quote($element, '#'));
		if (preg_match($pattern, $xml, $matches) !== 1) {
			return '';
		}

		return html_entity_decode($matches[1], (ENT_QUOTES | ENT_XML1), 'UTF-8');
	}//end readElement()

	/**
	 * Set one element's text, adding the element when it is absent.
	 *
	 * @param string $xml     The metadata XML.
	 * @param string $element The element name.
	 * @param string $value   The new text.
	 *
	 * @return string The rewritten XML.
	 *
	 * @throws RuntimeException When the root element cannot be found.
	 */
	private function writeElement(string $xml, string $element, string $value): string {
		$escaped = htmlspecialchars($value, (ENT_QUOTES | ENT_XML1), 'UTF-8');
		$quoted  = preg_quote($element, '#');

		$existing = sprintf('#<%s(?:\s[^>]*)?>.*?</%s>#s', $quoted, $quoted);
		if (preg_match($existing, $xml) === 1) {
			return (string)preg_replace(
				$existing,
				sprintf('<%s>%s</%s>', $element, $escaped, $element),
				$xml,
				1
			);
		}

		// Self-closing form, which is what an empty field is often serialised as.
		$selfClosing = sprintf('#<%s(?:\s[^>]*)?/>#', $quoted);
		if (preg_match($selfClosing, $xml) === 1) {
			return (string)preg_replace(
				$selfClosing,
				sprintf('<%s>%s</%s>', $element, $escaped, $element),
				$xml,
				1
			);
		}

		// A metadata part can carry a SELF-CLOSING root — `<cp:coreProperties/>` is
		// what several writers emit for a document with no properties set. There is
		// no closing tag to insert before, so expand it into an open/close pair
		// first. Without this, the ordinary "document has an empty metadata part"
		// case reports the part as corrupt.
		$xml = (string)preg_replace(
			'#<([A-Za-z0-9:_-]+)((?:\s[^>]*?)?)/>\s*$#',
			'<$1$2></$1>',
			$xml,
			1
		);

		// Absent: insert before the root's closing tag.
		if (preg_match('#</([A-Za-z0-9:_-]+)>\s*$#', $xml, $matches) !== 1) {
			throw new RuntimeException('The metadata part has no closing root element; it may be corrupt.');
		}

		$closing = sprintf('</%s>', $matches[1]);

		return (string)preg_replace(
			'#' . preg_quote($closing, '#') . '\s*$#',
			sprintf('<%s>%s</%s>%s', $element, $escaped, $element, $closing),
			$xml,
			1
		);
	}//end writeElement()

	/**
	 * Resolve the package mapping for an extension.
	 *
	 * @param string $extension The file extension.
	 *
	 * @return array{part: string, root: string, ns: string, fields: array<string, string>}
	 *
	 * @throws RuntimeException When the extension is unsupported.
	 */
	private function packageFor(string $extension): array {
		$key = strtolower($extension);
		if (isset(self::PACKAGES[$key]) === false) {
			throw new RuntimeException(
				sprintf(
					'Metadata cannot be addressed in a "%s" file. Supported: %s.',
					$extension,
					implode(', ', array_keys(self::PACKAGES))
				)
			);
		}

		return self::PACKAGES[$key];
	}//end packageFor()
}//end class
