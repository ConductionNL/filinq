<?php

/**
 * Package Codec
 *
 * Reads and edits ODF/OOXML word-processing packages in place: it locates the
 * paragraph elements inside the single body part, exposes them as anchored
 * blocks, and applies edits by rewriting only the byte range of a targeted
 * paragraph. Every other byte of the part -- and every other entry of the ZIP
 * package -- is left untouched.
 *
 * This is deliberately NOT a parse-to-model-and-re-serialise codec. A general
 * document library round-trips through its own object model and silently drops
 * comments, tracked changes, headers, styles and embedded objects. Those losses
 * are invisible in a diff of the visible text, which is exactly why they are
 * made structurally impossible here rather than guarded by a test.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Editing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/document-editing-tools/tasks.md#task-2-4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Editing;

use RuntimeException;

/**
 * Byte-surgical reader/editor for ODF and OOXML word-processing packages.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-untouched-parts-of-a-document-package-survive-an-edit-unchanged
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class PackageCodec {

	/**
	 * OOXML word-processing package (`.docx`).
	 *
	 * @var string
	 */
	public const FORMAT_OOXML = 'ooxml';

	/**
	 * ODF text package (`.odt`).
	 *
	 * @var string
	 */
	public const FORMAT_ODF = 'odf';

	/**
	 * Supported extensions mapped to their package family, body part and
	 * paragraph element name.
	 *
	 * Spreadsheets and presentations are deliberately absent: their block model
	 * is a cell/slide, not a paragraph, and giving them a paragraph anchor would
	 * produce anchors that resolve to nothing. They are specified separately in
	 * `multi-format-editing-tools`.
	 *
	 * @var array<string, array{format: string, part: string, tag: string}>
	 */
	private const PACKAGES = [
		'docx' => [
			'format' => self::FORMAT_OOXML,
			'part' => 'word/document.xml',
			'tag' => 'w:p',
		],
		'odt' => [
			'format' => self::FORMAT_ODF,
			'part' => 'content.xml',
			'tag' => 'text:p',
		],
	];

	/**
	 * Edit action: replace the anchored paragraph's visible text.
	 *
	 * @var string
	 */
	public const ACTION_REPLACE = 'replace';

	/**
	 * Edit action: insert a new paragraph after the anchored one, inheriting its markup.
	 *
	 * @var string
	 */
	public const ACTION_INSERT_AFTER = 'insertAfter';

	/**
	 * Edit action: remove the anchored paragraph.
	 *
	 * @var string
	 */
	public const ACTION_DELETE = 'delete';

	/**
	 * Edit action: change the anchored paragraph's style or layout, not its text.
	 *
	 * Separate from `replace` on purpose. Restyling and rewording are different
	 * intentions, and folding them together would mean a caller that wanted bold
	 * had to resend the text — which, when the caller is a language model, is an
	 * invitation to paraphrase the paragraph while "only" making it bold.
	 *
	 * @var string
	 */
	public const ACTION_STYLE = 'style';

	/**
	 * Every action this codec understands.
	 *
	 * @var array<int, string>
	 */
	public const ACTIONS = [
		self::ACTION_REPLACE,
		self::ACTION_INSERT_AFTER,
		self::ACTION_DELETE,
		self::ACTION_STYLE,
	];

	/**
	 * Constructor.
	 *
	 * @param XmlBlockScanner $scanner The element-span scanner.
	 * @param BlockStyleCodec $styles  The paragraph style/layout codec.
	 * @param PackagePartIo   $io      The package part reader/writer.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly XmlBlockScanner $scanner = new XmlBlockScanner(),
		private readonly BlockStyleCodec $styles = new BlockStyleCodec(),
		private readonly PackagePartIo $io = new PackagePartIo(),
	) {

	}//end __construct()

	/**
	 * Whether this codec can address the given file extension.
	 *
	 * @param string $extension The file extension, without a leading dot, any case.
	 *
	 * @return bool True when the extension names a supported package.
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-editing-session-availability-is-probed-never-inferred
	 */
	public function supports(string $extension): bool {
		return isset(self::PACKAGES[strtolower($extension)]);

	}//end supports()

	/**
	 * Every extension this codec can address.
	 *
	 * @return array<int, string> The supported extensions.
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-editing-session-availability-is-probed-never-inferred
	 */
	public function supportedExtensions(): array {
		return array_keys(self::PACKAGES);

	}//end supportedExtensions()

	/**
	 * Read the anchored blocks of a package.
	 *
	 * Anchors are derived from block CONTENT, not from position: a human
	 * inserting a paragraph shifts every index, and an index-addressed edit
	 * would then land in the wrong place with no error. They are recomputed on
	 * every read, so an anchor is only valid for as long as the block's text is
	 * unchanged -- which is the property that makes a stale edit fail loudly.
	 *
	 * @param string $packageBytes The raw package bytes.
	 * @param string $extension The file extension, without a leading dot.
	 *
	 * @return array{format: string, blocks: array<int, array{anchor: string, text: string}>}
	 *
	 * @throws RuntimeException When the extension is unsupported or the package is unreadable.
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-edits-address-stable-anchors-never-positional-indexes
	 */
	public function readBlocks(string $packageBytes, string $extension): array {
		$package = $this->packageFor(extension: $extension);
		$xml = $this->io->readPart(packageBytes: $packageBytes, part: $package['part']);

		$blocks = [];
		foreach ($this->scanner->spans(xml:$xml, tag: $package['tag']) as $span) {
			$markup = substr($xml, $span[0], $span[1]);
			$blocks[] = $this->extractText(markup: $markup, format: $package['format']);
		}

		return [
			'format' => $package['format'],
			'blocks' => $this->anchor(texts: $blocks),
		];

	}//end readBlocks()

	/**
	 * Apply anchored edits and return the new package bytes.
	 *
	 * Edits are applied from the LAST matched span backwards, so the byte
	 * offsets of the spans still to be edited are never invalidated by an
	 * earlier rewrite.
	 *
	 * @param string $packageBytes The raw package bytes.
	 * @param string $extension The file extension, without a leading dot.
	 * @param array<int, array<string, mixed>> $edits The edits to apply, each
	 *        `{anchor, action?, text?}`. Loosely typed because they arrive as
	 *        decoded JSON from a model; `resolveEdits()` validates them.
	 *
	 * @return array{bytes: string, applied: array<int, string>}
	 *
	 * @throws RuntimeException When an anchor does not resolve, an action is unknown,
	 *                          or the package cannot be rewritten.
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-untouched-parts-of-a-document-package-survive-an-edit-unchanged
	 */
	public function applyEdits(string $packageBytes, string $extension, array $edits): array {
		if ($edits === []) {
			throw new RuntimeException('At least one edit is required.');
		}

		$package = $this->packageFor(extension: $extension);
		$xml = $this->io->readPart(packageBytes: $packageBytes, part: $package['part']);

		$spans = $this->scanner->spans(xml:$xml, tag: $package['tag']);
		$texts = [];
		foreach ($spans as $span) {
			$texts[] = $this->extractText(markup: substr($xml, $span[0], $span[1]), format: $package['format']);
		}

		$anchors = array_column($this->anchor(texts: $texts), 'anchor');
		$resolved = $this->resolveEdits(edits: $edits, anchors: $anchors);

		// Descending by span index: rewriting a later span cannot move an earlier one.
		usort($resolved, static fn (array $a, array $b): int => ($b['index'] <=> $a['index']));

		$applied = [];
		foreach ($resolved as $edit) {
			$span = $spans[$edit['index']];
			$xml = $this->rewriteSpan(
				xml: $xml,
				span: $span,
				edit: $edit,
				format: $package['format']
			);
			$applied[] = $edit['anchor'];
		}

		return [
			'bytes' => $this->io->writePart(
				packageBytes: $packageBytes,
				part: $package['part'],
				xml: $xml
			),
			'applied' => array_reverse($applied),
		];

	}//end applyEdits()

	/**
	 * Resolve every edit's anchor to a block index, failing on the first miss.
	 *
	 * Failing the WHOLE edit set on one unresolvable anchor is deliberate: a
	 * partially applied edit set leaves a document in a state neither the user
	 * nor the agent asked for, and no caller can tell which half landed.
	 *
	 * The declared shape is deliberately loose: these arrive as decoded JSON from
	 * a language model, so `anchor` and `action` may be absent, and this method
	 * is the thing that finds out. Typing them as present would make the checks
	 * below look redundant to a static analyser and invite their removal.
	 *
	 * @param array<int, array<string, mixed>> $edits The requested edits.
	 * @param array<int, string> $anchors The anchors of the current blocks, in document order.
	 *
	 * @return array<int, array{index: int, anchor: string, action: string, text: string, style: array<string, mixed>}>
	 *
	 * @throws RuntimeException When an anchor is unknown or an action is not supported.
	 */
	private function resolveEdits(array $edits, array $anchors): array {
		$byAnchor = array_flip($anchors);
		$resolved = [];

		foreach ($edits as $position => $edit) {
			$anchor = (string)($edit['anchor'] ?? '');
			$action = (string)($edit['action'] ?? self::ACTION_REPLACE);

			if (in_array($action, self::ACTIONS, true) === false) {
				throw new RuntimeException(
					sprintf('Edit %d: unknown action "%s".', ((int)$position + 1), $action)
				);
			}

			if (isset($byAnchor[$anchor]) === false) {
				throw new RuntimeException(
					sprintf(
						'Edit %d: anchor "%s" does not match any block in this document. '
						. 'Anchors are derived from block content, so re-read the document and use current anchors.',
						((int)$position + 1),
						$anchor
					)
				);
			}

			$text = (string)($edit['text'] ?? '');
			$textless = [self::ACTION_DELETE, self::ACTION_STYLE];
			if (in_array($action, $textless, true) === false && $text === '') {
				throw new RuntimeException(
					sprintf('Edit %d: action "%s" requires non-empty text.', ((int)$position + 1), $action)
				);
			}

			// A style edit carries no text by design — see ACTION_STYLE. It must
			// carry style properties instead, or it is a request to change nothing
			// that would otherwise report success.
			$style = ($edit['style'] ?? []);
			if ($action === self::ACTION_STYLE && (is_array($style) === false || $style === [])) {
				throw new RuntimeException(
					sprintf(
						'Edit %d: action "style" requires a non-empty "style" object. Supported properties: %s.',
						((int)$position + 1),
						implode(', ', BlockStyleCodec::STYLE_KEYS)
					)
				);
			}

			$resolved[] = [
				'index' => (int)$byAnchor[$anchor],
				'anchor' => $anchor,
				'action' => $action,
				'text' => $text,
				'style' => (array)$style,
			];
		}//end foreach

		return $resolved;

	}//end resolveEdits()

	/**
	 * Rewrite one span according to one edit.
	 *
	 * @param string $xml The part XML.
	 * @param array{0: int, 1: int} $span The span offset and length.
	 * @param array{action: string, text: string, style: array<string, mixed>} $edit The edit to apply.
	 * @param string $format The package family.
	 *
	 * @return string The rewritten part XML.
	 */
	private function rewriteSpan(string $xml, array $span, array $edit, string $format): string {
		$markup = substr($xml, $span[0], $span[1]);

		$replacement = match ($edit['action']) {
			self::ACTION_DELETE => '',
			self::ACTION_INSERT_AFTER => $markup . $this->setText(
				markup: $markup,
				text: $edit['text'],
				format: $format
			),
			self::ACTION_STYLE => $this->styles->applyStyle(
				markup: $markup,
				style: $edit['style'],
				format: $format
			),
			default => $this->setText(markup: $markup, text: $edit['text'], format: $format),
		};

		return substr_replace($xml, $replacement, $span[0], $span[1]);

	}//end rewriteSpan()

	/**
	 * Resolve the package descriptor for an extension.
	 *
	 * @param string $extension The file extension, without a leading dot.
	 *
	 * @return array{format: string, part: string, tag: string}
	 *
	 * @throws RuntimeException When the extension names no supported package.
	 */
	private function packageFor(string $extension): array {
		$key = strtolower($extension);
		if (isset(self::PACKAGES[$key]) === false) {
			throw new RuntimeException(
				sprintf(
					'Unsupported document format ".%s". Editable formats are: %s.',
					$key,
					implode(', ', array_keys(self::PACKAGES))
				)
			);
		}

		return self::PACKAGES[$key];

	}//end packageFor()

	/**
	 * Package part IO moved to {@see PackagePartIo}.
	 *
	 * Metadata lives in a different part from the body, so the choice was one
	 * shared reader or two divergent copies of the same ZipArchive dance. The
	 * property that untouched parts survive an edit byte-identical is a property
	 * of exactly that code, and it should exist once.
	 */

	/**
	 * Extract a block's visible text from its markup.
	 *
	 * @param string $markup The block markup.
	 * @param string $format The package family.
	 *
	 * @return string The visible text.
	 */
	private function extractText(string $markup, string $format): string {
		if ($format === self::FORMAT_OOXML) {
			$text = '';
			foreach ($this->scanner->spans(xml:$markup, tag: 'w:t') as $span) {
				$run = substr($markup, $span[0], $span[1]);
				$inner = strstr($run, '>');
				if ($inner === false) {
					continue;
				}

				$text .= substr($inner, 1, -1 * strlen('</w:t>'));
			}

			return $this->decode(raw: $text);
		}

		$inner = strstr($markup, '>');
		if ($inner === false) {
			return '';
		}

		return $this->decode(raw: strip_tags(substr($inner, 1)));

	}//end extractText()

	/**
	 * Replace a block's visible text, keeping the block's own markup.
	 *
	 * OOXML: the first `w:t` carries the new text and every later one is
	 * emptied, so the paragraph keeps its properties and its first run's
	 * formatting. ODF: the paragraph's children are replaced wholesale, so
	 * paragraph style survives but intra-paragraph spans do not.
	 *
	 * @param string $markup The block markup.
	 * @param string $text The new visible text.
	 * @param string $format The package family.
	 *
	 * @return string The rewritten block markup.
	 */
	private function setText(string $markup, string $text, string $format): string {
		$escaped = htmlspecialchars($text, (ENT_XML1 | ENT_QUOTES), 'UTF-8');

		if ($format === self::FORMAT_ODF) {
			$openEnd = strpos($markup, '>');
			if ($openEnd === false || str_ends_with($markup, '/>') === true) {
				return $markup;
			}

			$closeStart = strrpos($markup, '</');

			return substr($markup, 0, ($openEnd + 1)) . $escaped . substr($markup, (int)$closeStart);
		}

		$spans = $this->scanner->spans(xml:$markup, tag: 'w:t');
		if ($spans === []) {
			return $this->injectRun(markup: $markup, escaped: $escaped);
		}

		// Descending, so an earlier span's offset stays valid after a later
		// rewrite. Only the FIRST run carries the new text; the rest are emptied,
		// which keeps the paragraph's run structure and its first run's
		// formatting instead of collapsing it into one plain run.
		foreach (array_reverse($spans, true) as $ordinal => $span) {
			$replacement = '';
			if ($ordinal === 0) {
				$replacement = $escaped;
			}

			$markup = substr_replace(
				$markup,
				$this->rewriteTextRun(run: substr($markup, $span[0], $span[1]), escaped: $replacement),
				$span[0],
				$span[1]
			);
		}

		return $markup;

	}//end setText()

	/**
	 * Set the contents of a single `w:t` element, preserving significant whitespace.
	 *
	 * @param string $run The `w:t` element markup.
	 * @param string $escaped The already XML-escaped replacement text.
	 *
	 * @return string The rewritten element.
	 */
	private function rewriteTextRun(string $run, string $escaped): string {
		$openEnd = strpos($run, '>');
		if ($openEnd === false) {
			return $run;
		}

		$openTag = substr($run, 0, ($openEnd + 1));
		if (str_contains($openTag, 'xml:space') === false) {
			$openTag = substr($openTag, 0, -1) . ' xml:space="preserve">';
		}

		if (str_ends_with($run, '/>') === true) {
			return substr($openTag, 0, -1) . '>' . $escaped . '</w:t>';
		}

		return $openTag . $escaped . '</w:t>';

	}//end rewriteTextRun()

	/**
	 * Give a run-less paragraph a run to carry text.
	 *
	 * An empty paragraph has no `w:t` to rewrite, so replacing its text means
	 * adding the run that holds it.
	 *
	 * @param string $markup The paragraph markup.
	 * @param string $escaped The already XML-escaped text.
	 *
	 * @return string The rewritten paragraph.
	 */
	private function injectRun(string $markup, string $escaped): string {
		$run = '<w:r><w:t xml:space="preserve">' . $escaped . '</w:t></w:r>';

		if (str_ends_with($markup, '/>') === true) {
			return substr($markup, 0, -2) . '>' . $run . '</w:p>';
		}

		$closeStart = strrpos($markup, '</w:p>');
		if ($closeStart === false) {
			return $markup;
		}

		return substr($markup, 0, $closeStart) . $run . substr($markup, $closeStart);

	}//end injectRun()

	/**
	 * Decode XML entities and collapse whitespace to a comparable form.
	 *
	 * @param string $raw The raw text.
	 *
	 * @return string The decoded text.
	 */
	private function decode(string $raw): string {
		return trim((string)preg_replace('/\s+/u', ' ', html_entity_decode($raw, (ENT_XML1 | ENT_QUOTES), 'UTF-8')));

	}//end decode()

	/**
	 * Give each block a content-derived anchor, disambiguated by occurrence.
	 *
	 * Two paragraphs with identical text are common (empty ones especially), so
	 * the hash alone is not an address. The occurrence ordinal makes it one, and
	 * keeps it stable as long as the identical blocks stay in the same order.
	 *
	 * @param array<int, string> $texts The block texts, in document order.
	 *
	 * @return array<int, array{anchor: string, text: string}>
	 */
	private function anchor(array $texts): array {
		$seen = [];
		$blocks = [];

		foreach ($texts as $text) {
			$hash = substr(sha1($text), 0, 8);
			$seen[$hash] = (($seen[$hash] ?? 0) + 1);
			$blocks[] = [
				'anchor' => sprintf('b%s-%d', $hash, $seen[$hash]),
				'text' => $text,
			];
		}

		return $blocks;

	}//end anchor()
}//end class
