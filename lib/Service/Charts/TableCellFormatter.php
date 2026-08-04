<?php
/**
 * Table Cell Formatter
 *
 * Formats a single table cell value according to its column format. Missing or
 * malformed values fall back to their plain string form rather than erroring,
 * so a bad cell never breaks a document.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Charts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/template-charts/specs/template-charts/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Charts;

/**
 * Formats table cell values per column format.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/template-charts/tasks.md#task-1.2
 */
class TableCellFormatter
{

    /**
     * Number formatter used for the number and currency formats.
     *
     * @var ThousandsFormatter
     */
    private readonly ThousandsFormatter $thousands;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $this->thousands = new ThousandsFormatter();

    }//end __construct()

    /**
     * Format a single cell value according to its column format.
     *
     * @param mixed  $value  Raw cell value.
     * @param string $format 'text'|'number'|'date'|'currency'.
     *
     * @return string Formatted (unescaped) text — escaping happens by the caller.
     *
     * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-004
     */
    public function format($value, string $format): string
    {
        if ($value === null) {
            return '';
        }

        if ($format === 'number' || $format === 'currency') {
            return $this->formatNumeric(value: $value, format: $format);
        }

        if ($format === 'date') {
            return $this->formatDate(value: $value);
        }

        return (string) $value;

    }//end format()

    /**
     * Format a numeric or currency cell, falling back to the plain string
     * form when the value is not numeric.
     *
     * @param mixed  $value  Raw cell value (never null).
     * @param string $format 'number' or 'currency'.
     *
     * @return string Formatted text.
     */
    private function formatNumeric($value, string $format): string
    {
        if (is_numeric($value) === false) {
            return (string) $value;
        }

        $numeric = (float) $value;

        $decimals = 2;
        if (floor($numeric) === $numeric) {
            $decimals = 0;
        }

        $formatted = $this->thousands->format(value: $numeric, decimals: $decimals);

        if ($format === 'currency') {
            return '€ '.$formatted;
        }

        return $formatted;

    }//end formatNumeric()

    /**
     * Format a date cell from a timestamp or a parseable date string, falling
     * back to the plain string form when it cannot be interpreted.
     *
     * @param mixed $value Raw cell value (never null).
     *
     * @return string Formatted text.
     */
    private function formatDate($value): string
    {
        if (is_string($value) === false && is_numeric($value) === false) {
            return (string) $value;
        }

        $timestamp = strtotime((string) $value);
        if (is_numeric($value) === true) {
            $timestamp = (int) $value;
        }

        if ($timestamp === false) {
            return (string) $value;
        }

        return date('d-m-Y', $timestamp);

    }//end formatDate()
}//end class
