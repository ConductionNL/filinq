<?php

/**
 * Svg Text Style
 *
 * The text styles the chart renderers draw labels in.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Charts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/template-charts/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Charts;

/**
 * A text style: its anchor, font size, fill color and font weight.
 *
 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
 */
enum SvgTextStyle {
	case Title;
	case Axis;
	case LabelStart;
	case LabelMiddle;
	case LabelEnd;
	case Placeholder;

	/**
	 * The SVG presentation attributes of this style.
	 *
	 * @return string The font-size, font-weight, fill and text-anchor attributes.
	 *
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function attributes(): string {
		return match ($this) {
			self::Title => 'font-size="13" font-weight="bold" fill="#000000" text-anchor="middle"',
			self::Axis => 'font-size="9" font-weight="normal" fill="#666666" text-anchor="end"',
			self::LabelStart => 'font-size="9" font-weight="normal" fill="#333333" text-anchor="start"',
			self::LabelMiddle => 'font-size="9" font-weight="normal" fill="#333333" text-anchor="middle"',
			self::LabelEnd => 'font-size="9" font-weight="normal" fill="#333333" text-anchor="end"',
			self::Placeholder => 'font-size="11" font-weight="normal" fill="#888888" text-anchor="middle"',
		};
	}//end attributes()
}//end enum
