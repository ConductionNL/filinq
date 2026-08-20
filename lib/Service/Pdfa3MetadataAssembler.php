<?php

/**
 * PDF/A-3 Metadata Assembler
 *
 * Owns the MDTO/archival metadata half of PDF/A-3 assembly: applying the
 * standard document properties through mPDF's setters, folding every other
 * caller-supplied key into an XMP RDF sidecar block, and turning the
 * caller's attachments (plus an auto-generated MDTO XML sidecar) into the
 * associated-file records mPDF embeds.
 *
 * Extracted from Pdfa3ConversionService so that service keeps to converter
 * orchestration (mPDF configuration, rendering, guardrails) while metadata
 * serialisation lives in one cohesive place.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use DOMDocument;
use Mpdf\Mpdf;
use OCA\DocuDesk\Exception\Pdfa3ConversionException;
use OCP\IAppConfig;

/**
 * Serialises MDTO/archival metadata into a PDF/A-3 document's XMP packet
 * and embedded-attachment set.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 */
class Pdfa3MetadataAssembler {

	/**
	 * App identifier used for IAppConfig reads.
	 */
	private const APP_ID = 'docudesk';

	/**
	 * App config key: maximum size of a single embedded attachment, in bytes.
	 */
	private const CFG_MAX_ATTACHMENT_BYTES = 'docudesk.pdfa3.max_attachment_bytes';

	/**
	 * Default cap: 20 MiB per attachment.
	 */
	private const DEFAULT_MAX_ATTACHMENT_BYTES = 20971520;

	/**
	 * Metadata keys handled by dedicated mPDF setters; every other key
	 * in the caller-supplied metadata array is folded into the MDTO
	 * XMP sidecar block instead of being silently dropped.
	 *
	 * @var array<int, string>
	 */
	private const STANDARD_METADATA_KEYS = [
		'title',
		'author',
		'creator',
		'subject',
		'keywords',
	];

	/**
	 * Constructor.
	 *
	 * @param IAppConfig $appConfig Tenant configuration provider.
	 */
	public function __construct(
		private readonly IAppConfig $appConfig,
	) {

	}//end __construct()

	/**
	 * Apply title/author/subject/keywords via mPDF's dedicated setters
	 * and fold every other metadata key into an MDTO XMP sidecar block
	 * via SetAdditionalXmpRdf() so archival fields (identifier,
	 * caseReference, archiefvormer, aggregatieniveau, ...) are
	 * genuinely part of the PDF/A-3's XMP packet, not just crammed
	 * into /Keywords.
	 *
	 * @param Mpdf $mpdf Target document.
	 * @param array<string,mixed> $metadata Caller-supplied metadata.
	 * @param string $defaultTitle Fallback title.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/pdfa3-conversion/spec.md
	 */
	public function applyMetadata(Mpdf $mpdf, array $metadata, string $defaultTitle): void {
		$title = (string)($metadata['title'] ?? $defaultTitle);
		if ($title !== '') {
			$mpdf->SetTitle($title);
		}

		$author = (string)($metadata['author'] ?? $metadata['creator'] ?? 'DocuDesk');
		$mpdf->SetAuthor($author);
		$mpdf->SetCreator('DocuDesk PDF/A-3 Conversion');

		if (isset($metadata['subject']) === true) {
			$mpdf->SetSubject((string)$metadata['subject']);
		}

		if (isset($metadata['keywords']) === true) {
			$keywords = $metadata['keywords'];
			if (is_array($keywords) === true) {
				$keywords = implode(', ', $keywords);
			}

			$mpdf->SetKeywords((string)$keywords);
		}

		$xmp = $this->buildMdtoXmpRdf(metadata: $metadata);
		if ($xmp !== '') {
			$mpdf->SetAdditionalXmpRdf($xmp);
		}

	}//end applyMetadata()

	/**
	 * Validate and normalise caller-supplied attachments, and append an
	 * auto-generated MDTO metadata sidecar (XML) when metadata was
	 * given and the caller did not already supply one — this is the
	 * concrete realisation of PDF/A-3's embedded-attachment feature.
	 *
	 * @param array<int,array<string,mixed>> $attachments Caller-supplied attachments.
	 * @param array<string,mixed> $metadata MDTO/archival metadata.
	 *
	 * @return array<int,array<string,mixed>> mPDF-shaped associated-file records.
	 *
	 * @throws Pdfa3ConversionException REASON_ATTACHMENT_TOO_LARGE.
	 *
	 * @spec openspec/specs/pdfa3-conversion/spec.md
	 */
	public function buildAssociatedFiles(array $attachments, array $metadata): array {
		$maxAttachmentBytes = $this->resolveMaxAttachmentBytes();
		$files = [];
		$hasXmlSidecar = false;

		foreach ($attachments as $attachment) {
			$content = (string)($attachment['content'] ?? '');
			if (strlen($content) > $maxAttachmentBytes) {
				throw new Pdfa3ConversionException(
					reason: Pdfa3ConversionException::REASON_ATTACHMENT_TOO_LARGE,
					message: sprintf(
						'Attachment "%s" (%d bytes) exceeds the configured cap (%d bytes).',
						($attachment['name'] ?? '(unnamed)'),
						strlen($content),
						$maxAttachmentBytes
					),
					adminHint: sprintf(
						'Increase %s in app config, or omit this attachment.',
						self::CFG_MAX_ATTACHMENT_BYTES
					),
					code: 413
				);
			}

			$name = (string)($attachment['name'] ?? 'attachment.bin');
			if (str_ends_with(strtolower($name), '.xml') === true) {
				$hasXmlSidecar = true;
			}

			$files[] = [
				'content' => $content,
				'name' => $name,
				'mime' => (string)($attachment['mime'] ?? 'application/octet-stream'),
				'description' => (string)($attachment['description'] ?? ''),
				'AFRelationship' => (string)($attachment['AFRelationship'] ?? 'Supplement'),
			];
		}//end foreach

		if (empty($metadata) === false && $hasXmlSidecar === false) {
			$files[] = [
				'content' => $this->buildMetadataSidecarXml(metadata: $metadata),
				'name' => 'mdto-metadata.xml',
				'mime' => 'text/xml',
				'description' => 'MDTO/archival metadata for this document',
				'AFRelationship' => 'Source',
			];
		}

		return $files;
	}//end buildAssociatedFiles()

	/**
	 * Serialise every non-standard metadata key into a custom XMP RDF
	 * description block under the `docudesk` namespace. Values are
	 * HTML/XML-escaped; keys are sanitised to valid XML local names.
	 *
	 * @param array<string,mixed> $metadata Caller-supplied metadata.
	 *
	 * @return string RDF/XML fragment, or '' when no archival fields were given.
	 */
	private function buildMdtoXmpRdf(array $metadata): string {
		$archivalFields = array_diff_key($metadata, array_flip(self::STANDARD_METADATA_KEYS));
		if (empty($archivalFields) === true) {
			return '';
		}

		$xml = '   <rdf:Description rdf:about="" xmlns:docudesk="https://www.docudesk.app/ns/mdto/1.0/">' . "\n";
		foreach ($archivalFields as $key => $value) {
			if (is_array($value) === true || is_object($value) === true) {
				$value = json_encode($value);
				if ($value === false) {
					continue;
				}
			}

			$tag = $this->sanitiseXmlLocalName(name: (string)$key);
			if ($tag === '') {
				continue;
			}

			$escaped = htmlspecialchars((string)$value, (ENT_QUOTES | ENT_XML1), 'UTF-8');
			$xml .= '    <docudesk:' . $tag . '>' . $escaped . '</docudesk:' . $tag . '>' . "\n";
		}

		$xml .= '   </rdf:Description>' . "\n";

		return $xml;
	}//end buildMdtoXmpRdf()

	/**
	 * Serialise the MDTO/archival metadata array into a small XML
	 * document for embedding as a PDF/A-3 attachment (the "source/XML
	 * alongside" pattern the A-3 conformance level exists for).
	 *
	 * @param array<string,mixed> $metadata Caller-supplied metadata.
	 *
	 * @return string UTF-8 XML document.
	 */
	private function buildMetadataSidecarXml(array $metadata): string {
		$doc = new DOMDocument(version: '1.0', encoding: 'UTF-8');
		$doc->formatOutput = true;

		$root = $doc->createElement('docudeskMetadata');
		$doc->appendChild($root);

		foreach ($metadata as $key => $value) {
			$tag = $this->sanitiseXmlLocalName(name: (string)$key);
			if ($tag === '') {
				continue;
			}

			if (is_array($value) === true || is_object($value) === true) {
				$value = json_encode($value);
				if ($value === false) {
					continue;
				}
			}

			$element = $doc->createElement($tag);
			$element->appendChild($doc->createTextNode((string)$value));
			$root->appendChild($element);
		}

		$xml = $doc->saveXML();
		if ($xml === false) {
			return '';
		}

		return $xml;
	}//end buildMetadataSidecarXml()

	/**
	 * Sanitise a metadata key into a valid XML local name (letters,
	 * digits, underscore, hyphen; must not start with a digit).
	 *
	 * @param string $name Raw metadata key.
	 *
	 * @return string Valid XML local name, or '' when nothing usable remains.
	 */
	private function sanitiseXmlLocalName(string $name): string {
		$clean = preg_replace('/[^A-Za-z0-9_-]/', '', $name);
		if ($clean === null || $clean === '') {
			return '';
		}

		if (preg_match('/^[0-9-]/', $clean) === 1) {
			$clean = 'f_' . $clean;
		}

		return $clean;
	}//end sanitiseXmlLocalName()

	/**
	 * Read the max-attachment-bytes tenant config. Defaults to 20 MiB.
	 *
	 * @return int Positive byte cap.
	 */
	private function resolveMaxAttachmentBytes(): int {
		$raw = $this->appConfig->getValueString(
			self::APP_ID,
			self::CFG_MAX_ATTACHMENT_BYTES,
			(string)self::DEFAULT_MAX_ATTACHMENT_BYTES
		);
		$parsed = (int)$raw;
		if ($parsed <= 0) {
			return self::DEFAULT_MAX_ATTACHMENT_BYTES;
		}

		return $parsed;
	}//end resolveMaxAttachmentBytes()
}//end class
