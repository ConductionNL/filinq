<?php

/**
 * Merged PDF Document
 *
 * FPDI with an outline. FPDF writes no bookmarks at all, and FPDI inherits that
 * gap, so a merged bundle of thirty documents opens as thirty untitled pages
 * and the reader has to scroll to find anything.
 *
 * The outline written here is the classic FPDF bookmark implementation: one
 * entry per merged input, pointing at the page that input starts on. Nothing
 * more: a nested outline would need the inputs' own outlines, and FPDI does not
 * import those.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use setasign\Fpdi\Fpdi;

/**
 * An FPDI document that can carry bookmarks.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */
class MergedPdfDocument extends Fpdi {
	// 🔴 TWO SNIFFS ARE OFF FOR THIS FILE, AND ONLY FOR THIS FILE.
	//
	// PSR2.Methods.MethodDeclaration.Underscore: `_putresources()` and
	// `_putcatalog()` are FPDF's own hook names. The whole point of this class is
	// that FPDF calls them during the write pass, so renaming them does not
	// rename anything - it stops the override happening and the merged file loses
	// its bookmarks, silently, with no error and a valid PDF.
	//
	// CustomSniffs.Functions.NamedParameters.RequireNamedParameters: `_put()`,
	// `_newobj()` and `_textstring()` are undocumented internals of
	// setasign/fpdf, and their parameter is called `$s`. A named argument would
	// bind this app to that name, so a vendor release that renames it turns every
	// merge into an "Unknown named parameter" fatal. Positional is the stable
	// call here, and the sniff's intent - readable call sites for OUR code - is
	// not served by pinning someone else's private signature.
	//
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable CustomSniffs.Functions.NamedParameters.RequireNamedParameters


	/**
	 * The outline entries, in document order.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $outlines = [];

	/**
	 * The object number the outline root got, once it is written.
	 *
	 * @var int
	 */
	private int $outlineRoot = 0;

	/**
	 * Add one bookmark, pointing at a page.
	 *
	 * @param string $label What the entry says.
	 * @param int $page The page it points at, from 1.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	public function addBookmark(string $label, int $page): void {
		$this->outlines[] = ['t' => $label, 'p' => max(1, $page), 'y' => 0];

	}//end addBookmark()

	/**
	 * The bookmarks this document carries.
	 *
	 * @return array<int, array<string, mixed>> The entries.
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	public function bookmarks(): array {
		return $this->outlines;

	}//end bookmarks()

	/**
	 * Write the outline objects.
	 *
	 * One flat level: every entry is a child of the root, in order, with the
	 * previous and next links FPDF's own bookmark implementation writes.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	private function putOutlines(): void {
		$count = count($this->outlines);
		if ($count === 0) {
			return;
		}

		$firstObject = ($this->n + 1);
		foreach ($this->outlines as $index => $entry) {
			$this->_newobj();
			$this->_put('<</Title ' . $this->_textstring($entry['t']));
			$this->_put('/Parent ' . ($firstObject + $count) . ' 0 R');
			if ($index > 0) {
				$this->_put('/Prev ' . ($firstObject + $index - 1) . ' 0 R');
			}

			if ($index < ($count - 1)) {
				$this->_put('/Next ' . ($firstObject + $index + 1) . ' 0 R');
			}

			$this->_put(
				sprintf(
					'/Dest [%d 0 R /XYZ 0 %.2F null]',
					(1 + (2 * $entry['p'])),
					(($this->h - $entry['y']) * $this->k)
				)
			);
			$this->_put('/Count 0>>');
			$this->_put('endobj');
		}//end foreach

		$this->_newobj();
		$this->outlineRoot = $this->n;
		$this->_put('<</Type /Outlines /First ' . $firstObject . ' 0 R');
		$this->_put('/Last ' . ($firstObject + $count - 1) . ' 0 R>>');
		$this->_put('endobj');

	}//end putOutlines()

	/**
	 * Hook the outline objects into the document's resource pass.
	 *
	 * @SuppressWarnings(PHPMD.CamelCaseMethodName) The name is FPDF's, not ours.
	 * `_putresources` is the method FPDF calls during output, and this class
	 * exists to override it. Renaming it to `putResources` does not rename the
	 * call inside the parent: FPDF would go on calling its own `_putresources`,
	 * this body would never run, and every merged bundle would come out with no
	 * bookmarks and no error. The lowercase name IS the contract.
	 *
	 * @return void
	 */
	protected function _putresources(): void {
		parent::_putresources();
		$this->putOutlines();

	}//end _putresources()

	/**
	 * Name the outline root in the catalog, so a reader opens the bookmarks.
	 *
	 * Writing the outline objects without this line produces a file that HAS
	 * bookmarks and shows none: the objects exist and nothing points at them.
	 *
	 * @SuppressWarnings(PHPMD.CamelCaseMethodName) The name is FPDF's, not ours.
	 * See `_putresources()` above: renaming an override FPDF calls by name
	 * silently stops it being called.
	 *
	 * @return void
	 */
	protected function _putcatalog(): void {
		parent::_putcatalog();
		if ($this->outlines === []) {
			return;
		}

		$this->_put('/Outlines ' . $this->outlineRoot . ' 0 R');
		$this->_put('/PageMode /UseOutlines');

	}//end _putcatalog()
}//end class
