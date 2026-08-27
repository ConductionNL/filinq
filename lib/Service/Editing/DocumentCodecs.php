<?php

/**
 * Filinq DocumentCodecs
 *
 * The set of per-document-kind codecs an editing session can reach for.
 *
 * One object rather than three constructor arguments, because a session that
 * takes a growing list of codecs makes every new document kind a change to
 * every construction site — and there are more kinds coming (Draw, and
 * whatever the suite probe turns up next). Grouping them also says the true
 * thing: the session does not care WHICH codec runs, only that exactly one
 * owns the file in front of it.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @link https://filinq.app
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Editing;

/**
 * Holds the text, spreadsheet and presentation codecs.
 */
class DocumentCodecs {

	/**
	 * Constructor.
	 *
	 * @param PackageCodec $text The anchored-block text codec.
	 * @param SpreadsheetCodec $spreadsheet The cell-addressed spreadsheet codec.
	 * @param PresentationCodec $presentation The id-addressed presentation codec.
	 */
	public function __construct(
		public readonly PackageCodec $text,
		public readonly SpreadsheetCodec $spreadsheet,
		public readonly PresentationCodec $presentation,
	) {
	}//end __construct()
}//end class
