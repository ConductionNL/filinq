<?php

/**
 * Huisstijl Profile Mapper
 *
 * The one place that knows the shape of thematiq's document house style
 * profile, and what a house style adds to the PDF options.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/templates-house-style-from-thematiq/tasks.md#task-1.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * Maps a thematiq profile onto `huisstijl` fields, and a house style onto PDF options.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class HuisstijlProfileMapper {

	/**
	 * filinq's own font, used for a role the house style gives no family.
	 *
	 * @var string
	 */
	public const FALLBACK_FONT = 'DejaVu Sans';

	/**
	 * Map a thematiq profile onto the `huisstijl` fields.
	 *
	 * The only place that knows the profile's shape:
	 * `{tokenSet: {id, name}, organisation, logo: {url, mime}|null, cover|null,
	 * colours: {primary, ...}, fonts: {heading, body: {family, url}},
	 * footer: {lines, accessibilityUrl, privacyUrl}}`.
	 *
	 * @param array<string, mixed> $profile The profile.
	 *
	 * @return array<string, mixed> The house style, `profileHash` included.
	 *
	 * @spec openspec/changes/templates-house-style-from-thematiq/tasks.md#task-1.2
	 */
	public function mapProfile(array $profile): array {
		$tokenSet = $this->arrayOf(value: ($profile['tokenSet'] ?? null));
		$footer = $this->arrayOf(value: ($profile['footer'] ?? null));
		$fonts = $this->arrayOf(value: ($profile['fonts'] ?? null));
		$lines = $this->footerLines(footer: $footer);
		$tokenSetId = $this->stringOf(value: ($tokenSet['id'] ?? null));

		$name = $this->stringOf(value: ($tokenSet['name'] ?? null));
		if ($name === '') {
			$name = $this->stringOf(value: ($profile['organisation'] ?? null));
		}

		if ($name === '') {
			$name = $tokenSetId;
		}

		return [
			'name' => $name,
			'managedBy' => HuisstijlProfileSync::MANAGED_BY,
			'tokenSetId' => $tokenSetId,
			'profileHash' => $this->profileHash(profile: $profile),
			'logo' => $this->urlOf(asset: ($profile['logo'] ?? null)),
			'primaryColor' => $this->stringOrNull(value: ($this->arrayOf(value: ($profile['colours'] ?? null))['primary'] ?? null)),
			'fonts' => [
				'heading' => $this->fontOf(font: ($fonts['heading'] ?? null)),
				'body' => $this->fontOf(font: ($fonts['body'] ?? null)),
			],
			'coverImage' => $this->urlOf(asset: ($profile['cover'] ?? null)),
			'footerLines' => $lines,
			'footerHtml' => $this->footerHtml(
				lines: $lines,
				links: [
					$this->stringOf(value: ($footer['accessibilityUrl'] ?? null)),
					$this->stringOf(value: ($footer['privacyUrl'] ?? null)),
				]
			),
		];

	}//end mapProfile()

	/**
	 * Whether a house style follows thematiq.
	 *
	 * @param array<string, mixed> $huisstijl The house style.
	 *
	 * @return bool Whether it is managed.
	 *
	 * @spec openspec/changes/templates-house-style-from-thematiq/tasks.md#task-2.2
	 */
	public function isManaged(array $huisstijl): bool {
		return ($huisstijl['managedBy'] ?? null) === HuisstijlProfileSync::MANAGED_BY;

	}//end isManaged()

	/**
	 * The PDF options a house style adds: its fonts, and its cover when the template asks for one.
	 *
	 * A role without a family of its own falls back to filinq's bundled font.
	 *
	 * @param array<string, mixed> $template The template.
	 * @param array<string, mixed>|null $huisstijl The house style, or null.
	 *
	 * @return array<string, mixed> `fonts` and `coverImage` where they apply; [] otherwise.
	 *
	 * @spec openspec/changes/templates-house-style-from-thematiq/tasks.md#task-2.1
	 */
	public function pdfAssets(array $template, ?array $huisstijl): array {
		if ($huisstijl === null) {
			return [];
		}

		$options = [];
		$fonts = $huisstijl['fonts'] ?? null;
		if (is_array($fonts) === true) {
			$options['fonts'] = [
				'heading' => $this->pdfFont(font: ($fonts['heading'] ?? null)),
				'body' => $this->pdfFont(font: ($fonts['body'] ?? null)),
			];
		}

		$cover = $huisstijl['coverImage'] ?? null;
		if (($template['coverPage'] ?? false) === true && is_string($cover) === true && $cover !== '') {
			$options['coverImage'] = $cover;
		}

		return $options;

	}//end pdfAssets()

	/**
	 * One font role for the PDF: its family, else filinq's own, and its file URL.
	 *
	 * @param mixed $font The house style font, `{family, url}`.
	 *
	 * @return array{family: string, url: string|null} The font.
	 */
	private function pdfFont(mixed $font): array {
		$resolved = ['family' => self::FALLBACK_FONT, 'url' => null];
		if (is_array($font) === false) {
			return $resolved;
		}

		$family = ($font['family'] ?? null);
		if (is_string($family) === true && trim($family) !== '') {
			$resolved['family'] = trim($family);
		}

		$url = ($font['url'] ?? null);
		if (is_string($url) === true && $url !== '') {
			$resolved['url'] = $url;
		}

		return $resolved;

	}//end pdfFont()

	/**
	 * The hash of the profile, its warnings left out.
	 *
	 * @param array<string, mixed> $profile The profile.
	 *
	 * @return string The sha256 hash.
	 */
	private function profileHash(array $profile): string {
		unset($profile['warnings']);

		return hash('sha256', (string) json_encode($profile, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

	}//end profileHash()

	/**
	 * The non-empty footer lines.
	 *
	 * @param array<string, mixed> $footer The profile footer.
	 *
	 * @return list<string> The lines.
	 */
	private function footerLines(array $footer): array {
		$lines = [];
		foreach ($this->arrayOf(value: ($footer['lines'] ?? null)) as $line) {
			$text = $this->stringOf(value: $line);
			if ($text !== '') {
				$lines[] = $text;
			}
		}

		return $lines;

	}//end footerLines()

	/**
	 * The footer markup: one paragraph per line, then the statement links.
	 *
	 * The footer is rendered as a Twig template, so profile text is escaped for
	 * HTML and its braces are written as entities: a footer line can never be
	 * read as a Twig expression.
	 *
	 * @param list<string> $lines The footer lines.
	 * @param list<string> $links The statement URLs, '' when not set.
	 *
	 * @return string The markup, or '' when there is nothing to show.
	 */
	private function footerHtml(array $lines, array $links): string {
		$parts = [];
		foreach ($lines as $line) {
			$parts[] = '<p>' . $this->escape(text: $line) . '</p>';
		}

		$anchors = [];
		foreach ($links as $link) {
			if ($link !== '') {
				$anchors[] = '<a href="' . $this->escape(text: $link) . '">' . $this->escape(text: $link) . '</a>';
			}
		}

		if ($anchors !== []) {
			$parts[] = '<p>' . implode(' | ', $anchors) . '</p>';
		}

		if ($parts === []) {
			return '';
		}

		return '<div class="huisstijl-footer">' . implode('', $parts) . '</div>';

	}//end footerHtml()

	/**
	 * Escape text for HTML and for Twig.
	 *
	 * @param string $text The text.
	 *
	 * @return string The escaped text.
	 */
	private function escape(string $text): string {
		return str_replace(
			['{', '}'],
			['&#123;', '&#125;'],
			htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')
		);

	}//end escape()

	/**
	 * A font role as `{family, url}`.
	 *
	 * @param mixed $font The profile font.
	 *
	 * @return array{family: string|null, url: string|null} The font.
	 */
	private function fontOf(mixed $font): array {
		$font = $this->arrayOf(value: $font);

		return [
			'family' => $this->stringOrNull(value: ($font['family'] ?? null)),
			'url' => $this->stringOrNull(value: ($font['url'] ?? null)),
		];

	}//end fontOf()

	/**
	 * The URL of a profile asset, or null.
	 *
	 * @param mixed $asset The asset, `{url, mime}`.
	 *
	 * @return string|null The URL.
	 */
	private function urlOf(mixed $asset): ?string {
		return $this->stringOrNull(value: ($this->arrayOf(value: $asset)['url'] ?? null));

	}//end urlOf()

	/**
	 * A value as an array, [] when it is not one.
	 *
	 * @param mixed $value The value.
	 *
	 * @return array<mixed> The array.
	 */
	private function arrayOf(mixed $value): array {
		if (is_array($value) === true) {
			return $value;
		}

		return [];

	}//end arrayOf()

	/**
	 * A value as a trimmed string, '' when it is not a string.
	 *
	 * @param mixed $value The value.
	 *
	 * @return string The string.
	 */
	private function stringOf(mixed $value): string {
		if (is_string($value) === true) {
			return trim($value);
		}

		return '';

	}//end stringOf()

	/**
	 * A value as a non-empty string, or null.
	 *
	 * @param mixed $value The value.
	 *
	 * @return string|null The string.
	 */
	private function stringOrNull(mixed $value): ?string {
		$text = $this->stringOf(value: $value);
		if ($text === '') {
			return null;
		}

		return $text;

	}//end stringOrNull()
}//end class
