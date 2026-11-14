<?php

/**
 * What to do about a failed PDF/A verdict, read from the shape of the
 * failure.
 *
 * Filinq embeds fonts when it renders, so a font failure on a document it
 * rendered is fixed by rendering it again. A page imported whole (an
 * uploaded PDF, or one wrapped by convertExistingPdf) keeps the fonts its
 * source had: Filinq cannot embed them afterwards, and the guidance says
 * so instead of promising a repair.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\VeraPdf
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\VeraPdf;

/**
 * Pure: verdict and origin in, guidance key out.
 */
class ConformanceGuidance {

	/**
	 * Nothing to do.
	 */
	public const NONE = 'none';

	/**
	 * Fonts missing in a document Filinq rendered: render it again.
	 */
	public const REGENERATE = 'regenerate';

	/**
	 * Fonts missing in imported pages: convert again from the source file.
	 */
	public const RECONVERT_FROM_SOURCE = 'reconvertFromSource';

	/**
	 * Rules other than font embedding failed: see the clause references.
	 */
	public const RULE_REFERENCES = 'ruleReferences';

	/**
	 * Filinq rendered the pages itself.
	 */
	public const ORIGIN_RENDERED = 'rendered';

	/**
	 * The pages came from elsewhere: uploaded, or imported whole.
	 */
	public const ORIGIN_IMPORTED = 'imported';

	/**
	 * The creator Filinq's renderers stamp into every PDF they write.
	 */
	private const FILINQ_CREATOR = '/(CreatorTool>|\/Creator\s*\()\s*Filinq /';

	/**
	 * Where the pages of a validated PDF came from, when the caller does
	 * not know: rendered only when Filinq wrote it and no failing font sits
	 * on a page imported whole.
	 *
	 * @param string               $bytes   The PDF.
	 * @param array<string, mixed> $verdict The verdict.
	 *
	 * @return string One of the ORIGIN_* constants.
	 *
	 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.2
	 */
	public function originOf(string $bytes, array $verdict): string {
		if (($verdict['fontsInImportedPages'] ?? false) === true || preg_match(self::FILINQ_CREATOR, $bytes) !== 1) {
			return self::ORIGIN_IMPORTED;
		}

		return self::ORIGIN_RENDERED;

	}//end originOf()

	/**
	 * The guidance for a verdict.
	 *
	 * @param array<string, mixed> $verdict The verdict.
	 * @param string               $origin  One of the ORIGIN_* constants.
	 *
	 * @return string One of the guidance constants.
	 *
	 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.2
	 */
	public function for(array $verdict, string $origin): string {
		if (($verdict['compliant'] ?? false) === true) {
			return self::NONE;
		}

		// A font failure decides the advice even beside other failures: it is
		// the one an e-depot rejects on and the one Filinq may not fix.
		$fontFailed = (($verdict['onlyFontRulesFailed'] ?? false) === true || ($verdict['fontsNotEmbedded'] ?? []) !== []);
		if ($fontFailed === false) {
			return self::RULE_REFERENCES;
		}

		if ($origin === self::ORIGIN_RENDERED) {
			return self::REGENERATE;
		}

		return self::RECONVERT_FROM_SOURCE;

	}//end for()
}//end class
