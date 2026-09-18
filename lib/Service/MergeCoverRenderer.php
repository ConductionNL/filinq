<?php

/**
 * Merge Cover Renderer
 *
 * Renders the cover page a merge asks for: the named template, over what the
 * merge knows about itself, as PDF bytes.
 *
 * It fails LOUDLY. A bundle whose cover silently did not render is a bundle
 * that says nothing about what it is, handed to somebody who has to work out
 * from thirty unlabelled pages what they are looking at.
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

use RuntimeException;
use Throwable;

/**
 * Turns a cover template into the first page of a merge.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */
class MergeCoverRenderer {

	/**
	 * Constructor.
	 *
	 * @param TemplateService $templates The template store.
	 * @param PdfService $pdf The renderer.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly TemplateService $templates,
		private readonly PdfService $pdf,
	) {

	}//end __construct()

	/**
	 * Render the cover page of one merge.
	 *
	 * @param string $templateRef The template the merge names.
	 * @param array<string, mixed> $data What the cover may say: the inputs, who asked, the host object.
	 *
	 * @return string The cover page as PDF bytes.
	 *
	 * @throws RuntimeException When the template is gone or does not render.
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	public function render(string $templateRef, array $data = []): string {
		if ($templateRef === '') {
			throw new RuntimeException(message: 'A cover page names the template it renders.');
		}

		try {
			$template = $this->templates->getTemplate($templateRef);
		} catch (Throwable $e) {
			throw new RuntimeException(
				message: 'The cover template "' . $templateRef . '" could not be read: ' . $e->getMessage(),
				code: 0,
				previous: $e
			);
		}

		$content = (string)($template['content'] ?? '');
		if ($content === '') {
			throw new RuntimeException(
				message: 'The cover template "' . $templateRef . '" holds no content.'
			);
		}

		try {
			$bytes = $this->pdf->renderPdf($content, $data);
		} catch (Throwable $e) {
			throw new RuntimeException(
				message: 'The cover page did not render: ' . $e->getMessage(),
				code: 0,
				previous: $e
			);
		}

		if ($bytes === '') {
			throw new RuntimeException(message: 'The cover page rendered to nothing.');
		}

		return $bytes;

	}//end render()
}//end class
