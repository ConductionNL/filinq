<?php

/**
 * Filinq Generate Document Flow Node
 *
 * `filinq.generate-document`: renders a Filinq template over each flow item
 * and files the result against the item's object. Filinq owns document
 * generation for the fleet (hydra ADR-075), and OpenRegister owns the flow
 * engine (ADR-065), so this is how a flow makes a document: by asking the app
 * that owns documents, through the node catalogue, in-process. It replaces
 * the private `{{...}}` renderers dossiq's createDocument and mergeTemplate
 * nodes carried.
 *
 * The node is a thin adapter. Every decision about a request is made in
 * DocumentGenerationRequestService, which the DocumentGenerationRequestedEvent
 * listener calls too, so the two entry points behave identically.
 *
 * @category Flow
 * @package  OCA\Filinq\Flow
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-a-flow-step-generates-a-document-per-item
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Flow;

use OCA\Filinq\Service\DocumentGenerationRequestService;
use OCA\OpenRegister\Service\Flow\IFlowNode;
use OCA\OpenRegister\Service\Flow\IFlowNodeConfigForm;
use OCA\OpenRegister\Service\Flow\IFlowNodeConfigKeys;
use OCA\OpenRegister\Service\Flow\IFlowNodeTaxonomy;
use OCP\IL10N;
use OCP\IURLGenerator;

/**
 * Generates one document per flow item.
 *
 * @category Flow
 * @package  OCA\Filinq\Flow
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-a-flow-step-generates-a-document-per-item
 */
class GenerateDocumentNode implements IFlowNode, IFlowNodeConfigKeys, IFlowNodeConfigForm, IFlowNodeTaxonomy {

	/**
	 * The node type identifier.
	 *
	 * @var string
	 */
	public const NODE_ID = 'filinq.generate-document';

	/**
	 * The item field the document lands on when `output` is not set.
	 *
	 * @var string
	 */
	public const DEFAULT_OUTPUT = 'document';

	/**
	 * The requesting app recorded when the step does not name one.
	 *
	 * @var string
	 */
	public const DEFAULT_REQUESTING_APP = 'flow';

	/**
	 * Every configuration key this node reads.
	 *
	 * @var array<int, string>
	 */
	public const CONFIG_KEYS = [
		'templateId',
		'templateSlug',
		'templateNamespace',
		'templateTenantId',
		'template',
		'templateName',
		'format',
		'storeFile',
		'targetField',
		'targetPath',
		'filename',
		'huisstijlId',
		'register',
		'schema',
		'objectId',
		'metadata',
		'requestingApp',
		'output',
	];

	/**
	 * Constructor.
	 *
	 * @param DocumentGenerationRequestService $generator The shared generation service.
	 * @param IURLGenerator                    $urls      Builds the palette icon URL.
	 * @param IL10N                            $l10n      Translates what the editor shows.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentGenerationRequestService $generator,
		private readonly IURLGenerator $urls,
		private readonly IL10N $l10n,
	) {

	}//end __construct()

	/**
	 * The type identifier.
	 *
	 * @return string The type identifier.
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-a-flow-step-generates-a-document-per-item
	 */
	public function getId(): string {
		return self::NODE_ID;
	}//end getId()

	/**
	 * Name shown in the flow builder's palette.
	 *
	 * @return string The display name.
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-a-flow-step-generates-a-document-per-item
	 */
	public function getDisplayName(): string {
		return $this->l10n->t('Generate document');
	}//end getDisplayName()

	/**
	 * What the step does, in the builder.
	 *
	 * @return string The description.
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-a-flow-step-generates-a-document-per-item
	 */
	public function getDescription(): string {
		return $this->l10n->t('Render a Filinq template for each item and file the document with its object.');
	}//end getDescription()

	/**
	 * The palette icon.
	 *
	 * @return string The icon URL.
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-a-flow-step-generates-a-document-per-item
	 */
	public function getIcon(): string {
		return $this->urls->getAbsoluteURL($this->urls->imagePath('filinq', 'app.svg'));
	}//end getIcon()

	/**
	 * Available in every scope: the document is filed as the acting user.
	 *
	 * @param int $scope The scope constant.
	 *
	 * @return bool Whether it is available.
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-a-flow-step-generates-a-document-per-item
	 */
	public function isAvailableForScope(int $scope): bool {
		unset($scope);

		return true;
	}//end isAvailableForScope()

	/**
	 * BPMN kind: the step calls a service.
	 *
	 * @return string The kind.
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-a-flow-step-generates-a-document-per-item
	 */
	public function getKind(): string {
		return IFlowNodeTaxonomy::KIND_SERVICE_TASK;
	}//end getKind()

	/**
	 * Palette category: it produces something for a registered object.
	 *
	 * @return string The category.
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-a-flow-step-generates-a-document-per-item
	 */
	public function getCategory(): string {
		return IFlowNodeTaxonomy::CATEGORY_OBJECTS;
	}//end getCategory()

	/**
	 * The configuration keys this node reads.
	 *
	 * @return array<int, string> The accepted keys.
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-a-flow-step-generates-a-document-per-item
	 */
	public function configKeys(): array {
		return self::CONFIG_KEYS;
	}//end configKeys()

	/**
	 * The fields the editor shows. The rest stay editable as raw JSON.
	 *
	 * @return array<int, array<string, mixed>> The field descriptions.
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-a-flow-step-generates-a-document-per-item
	 */
	public function configForm(): array {
		return [
			[
				'key' => 'templateId',
				'label' => $this->l10n->t('Template'),
				'type' => 'text',
				'help' => $this->l10n->t('The id of a Filinq template. Leave empty when you use a slug or inline text.'),
			],
			[
				'key' => 'templateSlug',
				'label' => $this->l10n->t('Template slug'),
				'type' => 'text',
				'help' => $this->l10n->t('Find the template by its slug instead of its id. Needs the template namespace.'),
			],
			[
				'key' => 'templateNamespace',
				'label' => $this->l10n->t('Template namespace'),
				'type' => 'text',
				'help' => $this->l10n->t('The app the template belongs to, for example dossiq.'),
			],
			[
				'key' => 'template',
				'label' => $this->l10n->t('Inline template'),
				'type' => 'textarea',
				'help' => $this->l10n->t('Template text to render instead of a stored template. Fields of the item are available by name.'),
			],
			[
				'key' => 'format',
				'label' => $this->l10n->t('Format'),
				'type' => 'text',
				'help' => $this->l10n->t('pdf, odf or html. Defaults to pdf.'),
			],
			[
				'key' => 'filename',
				'label' => $this->l10n->t('File name'),
				'type' => 'text',
				'help' => $this->l10n->t('Without extension. May use item fields, for example {{ identifier }}.'),
			],
			[
				'key' => 'storeFile',
				'label' => $this->l10n->t('Store as a file'),
				'type' => 'boolean',
				'help' => $this->l10n->t('Turn off to only write the rendered text into a field.'),
			],
			[
				'key' => 'targetField',
				'label' => $this->l10n->t('Write text to field'),
				'type' => 'text',
				'help' => $this->l10n->t('Also write the rendered text into this field of the object. Only that field changes.'),
			],
			[
				'key' => 'output',
				'label' => $this->l10n->t('Output field'),
				'type' => 'text',
				'help' => $this->l10n->t('The item field that receives the document details. Defaults to document.'),
			],
		];
	}//end configForm()

	/**
	 * Refuse a configuration this node cannot act on.
	 *
	 * @param array<string, mixed> $config The step's authored configuration.
	 *
	 * @return void
	 *
	 * @throws \UnexpectedValueException When the configuration is unusable.
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-a-configuration-that-cannot-run-is-refused-when-the-flow-is-saved
	 */
	public function validateConfig(array $config): void {
		$this->generator->validate(request: $config);
	}//end validateConfig()

	/**
	 * Generate one document per item.
	 *
	 * Each item leaves with the document's details under the output key and,
	 * when `targetField` is set, the rendered text under that field too, so
	 * the next step sees what this step just stored. Throws on the first
	 * failure: the engine's `onError` policy decides what happens next.
	 *
	 * @param array<int, array<string, mixed>> $items   The incoming items.
	 * @param array<string, mixed>             $config  The step configuration.
	 * @param array<string, mixed>             $context The run context.
	 *
	 * @return array<int, array<string, mixed>> The items, each carrying its document.
	 *
	 * @throws \Exception When a document cannot be generated.
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-a-flow-step-generates-a-document-per-item
	 */
	public function execute(array $items, array $config, array $context): array {
		unset($context);

		$outputKey = trim((string)($config['output'] ?? self::DEFAULT_OUTPUT));
		if ($outputKey === '') {
			$outputKey = self::DEFAULT_OUTPUT;
		}

		$requestingApp = trim((string)($config['requestingApp'] ?? ''));
		if ($requestingApp === '') {
			$requestingApp = self::DEFAULT_REQUESTING_APP;
		}

		$out = [];
		foreach ($items as $item) {
			$json = (array)($item['json'] ?? []);

			$request = $config;
			$request['data'] = $json;
			$request['object'] = $this->objectOf(json: $json, config: $config);

			$document = $this->generator->generate(request: $request, requestingApp: $requestingApp);

			$targetField = (string)($document['targetField'] ?? '');
			if ($targetField !== '') {
				$json[$targetField] = ($document['text'] ?? '');
			}

			unset($document['text']);
			$json[$outputKey] = $document;
			$item['json'] = $json;
			$out[] = $item;
		}

		return $out;
	}//end execute()

	/**
	 * The object an item stands for: configuration first, then the item.
	 *
	 * An OpenRegister object on a flow item carries its identity under
	 * `@self`; a bare record may carry `id` or `uuid` at the top level.
	 *
	 * @param array<string, mixed> $json   The item's record.
	 * @param array<string, mixed> $config The step configuration.
	 *
	 * @return array{register: string, schema: string, id: string} The reference, parts possibly empty.
	 */
	private function objectOf(array $json, array $config): array {
		$self = (array)($json['@self'] ?? []);

		return [
			'register' => $this->first(values: [$config['register'] ?? null, $self['register'] ?? null]),
			'schema' => $this->first(values: [$config['schema'] ?? null, $self['schema'] ?? null]),
			'id' => $this->first(
				values: [
					$config['objectId'] ?? null,
					$self['id'] ?? null,
					$self['uuid'] ?? null,
					$json['id'] ?? null,
					$json['uuid'] ?? null,
				]
			),
		];
	}//end objectOf()

	/**
	 * The first non-empty scalar, as text.
	 *
	 * @param array<int, mixed> $values The candidates, in order.
	 *
	 * @return string The value, or ''.
	 */
	private function first(array $values): string {
		foreach ($values as $value) {
			if (is_scalar($value) === true && trim((string)$value) !== '') {
				return trim((string)$value);
			}
		}

		return '';
	}//end first()
}//end class
