<?php

/**
 * DocuDesk BlockStyleFamilyCodec
 *
 * One package family's answer to "apply this style to this block".
 *
 * OOXML and ODF do not merely spell style differently — they locate it
 * differently. OOXML writes properties INSIDE the paragraph; ODF has no direct
 * formatting at all and can only point a block at an automatic style defined
 * elsewhere in `content.xml`. A single class holding both grew to a complexity
 * of 65 against a threshold of 50, which was the measurement saying these are
 * two implementations wearing one name.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @link https://docudesk.app
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Editing;

/**
 * Applies block style for one package family.
 */
interface BlockStyleFamilyCodec {

	/**
	 * Whether this codec handles the given package family.
	 *
	 * @param string $format The package family constant.
	 *
	 * @return bool True when handled.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function supports(string $format): bool;

	/**
	 * Apply style properties to one block's markup.
	 *
	 * Returns the rewritten markup and, for families that cannot express style
	 * inline, a document-level definition the caller must inject. A family that
	 * needs no definition returns null for it — never an empty string, which a
	 * caller could inject and produce malformed XML.
	 *
	 * @param string $markup    The block markup.
	 * @param array  $style     The style properties.
	 * @param string $styleName A unique name the codec may mint a style under.
	 *
	 * @return array{markup: string, automaticStyle: string|null} The rewritten block and any style to inject.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function applyStyle(string $markup, array $style, string $styleName): array;
}//end interface
