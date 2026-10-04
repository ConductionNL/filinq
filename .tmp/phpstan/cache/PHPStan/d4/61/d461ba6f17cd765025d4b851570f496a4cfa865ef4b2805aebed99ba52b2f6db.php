<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/FinancialExtractionService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\FinancialExtractionService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-d6736a742afc761ed11f137ca52364daa07efd4969825fc63fe27c2a4cac7eae',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/FinancialExtractionService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
    'shortName' => 'FinancialExtractionService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Orchestrates financial-document field extraction.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 69,
    'endLine' => 1153,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'VALID_DOC_TYPES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'name' => 'VALID_DOC_TYPES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'receipt\', \'supplier-invoice\']',
          'attributes' => 
          array (
            'startLine' => 76,
            'endLine' => 76,
            'startTokenPos' => 148,
            'startFilePos' => 2542,
            'endTokenPos' => 153,
            'endFilePos' => 2572,
          ),
        ),
        'docComment' => '/**
 * Valid `docType` values accepted by the extraction endpoint.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 76,
        'endLine' => 76,
        'startColumn' => 2,
        'endColumn' => 65,
      ),
      'FIELD_DEFAULTS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'name' => 'FIELD_DEFAULTS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'supplierName\' => null, \'supplierIban\' => null, \'supplierKvk\' => null, \'supplierVatId\' => null, \'invoiceNumber\' => null, \'issueDate\' => null, \'dueDate\' => null, \'currency\' => null, \'totalExcl\' => null, \'totalVat\' => null, \'totalIncl\' => null, \'vatBreakdown\' => [], \'lines\' => []]',
          'attributes' => 
          array (
            'startLine' => 84,
            'endLine' => 98,
            'startTokenPos' => 166,
            'startFilePos' => 2757,
            'endTokenPos' => 261,
            'endFilePos' => 3066,
          ),
        ),
        'docComment' => '/**
 * Field keys that always exist in the shaped result (REQ-FIN-03), and
 * their "empty" default value.
 *
 * @var array<string, mixed>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 84,
        'endLine' => 98,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
      'ISSUE_DATE_LABELS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'name' => 'ISSUE_DATE_LABELS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'factuurdatum\', \'invoice date\', \'datum\']',
          'attributes' => 
          array (
            'startLine' => 105,
            'endLine' => 105,
            'startTokenPos' => 274,
            'startFilePos' => 3197,
            'endTokenPos' => 282,
            'endFilePos' => 3237,
          ),
        ),
        'docComment' => '/**
 * Labels used to locate the invoice/issue date.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 105,
        'endLine' => 105,
        'startColumn' => 2,
        'endColumn' => 77,
      ),
      'DUE_DATE_LABELS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'name' => 'DUE_DATE_LABELS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'vervaldatum\', \'betaaldatum\', \'due date\', \'uiterste betaaldatum\']',
          'attributes' => 
          array (
            'startLine' => 112,
            'endLine' => 112,
            'startTokenPos' => 295,
            'startFilePos' => 3356,
            'endTokenPos' => 306,
            'endFilePos' => 3421,
          ),
        ),
        'docComment' => '/**
 * Labels used to locate the due date.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 112,
        'endLine' => 112,
        'startColumn' => 2,
        'endColumn' => 100,
      ),
      'TOTAL_EXCL_LABELS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'name' => 'TOTAL_EXCL_LABELS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'subtotaal\', \'totaal excl. btw\', \'totaal exclusief btw\', \'exclusief btw\']',
          'attributes' => 
          array (
            'startLine' => 119,
            'endLine' => 119,
            'startTokenPos' => 319,
            'startFilePos' => 3554,
            'endTokenPos' => 330,
            'endFilePos' => 3627,
          ),
        ),
        'docComment' => '/**
 * Labels used to locate the amount excluding VAT.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 119,
        'endLine' => 119,
        'startColumn' => 2,
        'endColumn' => 110,
      ),
      'TOTAL_VAT_LABELS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'name' => 'TOTAL_VAT_LABELS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'btw\', \'vat\', \'omzetbelasting\']',
          'attributes' => 
          array (
            'startLine' => 126,
            'endLine' => 126,
            'startTokenPos' => 343,
            'startFilePos' => 3749,
            'endTokenPos' => 351,
            'endFilePos' => 3780,
          ),
        ),
        'docComment' => '/**
 * Labels used to locate the VAT amount.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 126,
        'endLine' => 126,
        'startColumn' => 2,
        'endColumn' => 67,
      ),
      'TOTAL_INCL_LABELS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'name' => 'TOTAL_INCL_LABELS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'totaal\', \'totaalbedrag\', \'te betalen\', \'total\']',
          'attributes' => 
          array (
            'startLine' => 133,
            'endLine' => 133,
            'startTokenPos' => 364,
            'startFilePos' => 3913,
            'endTokenPos' => 375,
            'endFilePos' => 3961,
          ),
        ),
        'docComment' => '/**
 * Labels used to locate the amount including VAT.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 133,
        'endLine' => 133,
        'startColumn' => 2,
        'endColumn' => 85,
      ),
      'INVOICE_NUMBER_LABELS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'name' => 'INVOICE_NUMBER_LABELS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'factuurnummer\', \'factuur nr\', \'invoice number\', \'invoice no\']',
          'attributes' => 
          array (
            'startLine' => 140,
            'endLine' => 140,
            'startTokenPos' => 388,
            'startFilePos' => 4092,
            'endTokenPos' => 399,
            'endFilePos' => 4154,
          ),
        ),
        'docComment' => '/**
 * Labels used to locate the invoice number.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 140,
        'endLine' => 140,
        'startColumn' => 2,
        'endColumn' => 103,
      ),
      'SUPPLIER_NAME_CONFIDENCE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'name' => 'SUPPLIER_NAME_CONFIDENCE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0.55',
          'attributes' => 
          array (
            'startLine' => 147,
            'endLine' => 147,
            'startTokenPos' => 412,
            'startFilePos' => 4289,
            'endTokenPos' => 412,
            'endFilePos' => 4292,
          ),
        ),
        'docComment' => '/**
 * Confidence assigned to a heuristic supplier-name match.
 *
 * @var float
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 147,
        'endLine' => 147,
        'startColumn' => 2,
        'endColumn' => 47,
      ),
      'INVOICE_NUMBER_CONFIDENCE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'name' => 'INVOICE_NUMBER_CONFIDENCE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0.7',
          'attributes' => 
          array (
            'startLine' => 154,
            'endLine' => 154,
            'startTokenPos' => 425,
            'startFilePos' => 4429,
            'endTokenPos' => 425,
            'endFilePos' => 4431,
          ),
        ),
        'docComment' => '/**
 * Confidence assigned to a heuristic invoice-number match.
 *
 * @var float
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 154,
        'endLine' => 154,
        'startColumn' => 2,
        'endColumn' => 47,
      ),
      'CURRENCY_CONFIDENCE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'name' => 'CURRENCY_CONFIDENCE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0.8',
          'attributes' => 
          array (
            'startLine' => 161,
            'endLine' => 161,
            'startTokenPos' => 438,
            'startFilePos' => 4553,
            'endTokenPos' => 438,
            'endFilePos' => 4555,
          ),
        ),
        'docComment' => '/**
 * Confidence assigned to a currency-marker match.
 *
 * @var float
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 161,
        'endLine' => 161,
        'startColumn' => 2,
        'endColumn' => 41,
      ),
      'RECONCILIATION_BOOST' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'name' => 'RECONCILIATION_BOOST',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0.2',
          'attributes' => 
          array (
            'startLine' => 168,
            'endLine' => 168,
            'startTokenPos' => 451,
            'startFilePos' => 4708,
            'endTokenPos' => 451,
            'endFilePos' => 4710,
          ),
        ),
        'docComment' => '/**
 * Confidence boost applied to totalExcl/totalVat/totalIncl when they reconcile.
 *
 * @var float
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 168,
        'endLine' => 168,
        'startColumn' => 2,
        'endColumn' => 42,
      ),
      'AI_FILL_CONFIDENCE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'name' => 'AI_FILL_CONFIDENCE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0.55',
          'attributes' => 
          array (
            'startLine' => 175,
            'endLine' => 175,
            'startTokenPos' => 464,
            'startFilePos' => 4858,
            'endTokenPos' => 464,
            'endFilePos' => 4861,
          ),
        ),
        'docComment' => '/**
 * Confidence assigned to a field filled by the optional AI enhancement step.
 *
 * @var float
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 175,
        'endLine' => 175,
        'startColumn' => 2,
        'endColumn' => 41,
      ),
      'LOW_CONFIDENCE_THRESHOLD' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'name' => 'LOW_CONFIDENCE_THRESHOLD',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0.6',
          'attributes' => 
          array (
            'startLine' => 183,
            'endLine' => 183,
            'startTokenPos' => 477,
            'startFilePos' => 5046,
            'endTokenPos' => 477,
            'endFilePos' => 5048,
          ),
        ),
        'docComment' => '/**
 * Fields at or below this confidence (or null) are eligible for AI
 * enhancement, unless checksum-locked.
 *
 * @var float
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 183,
        'endLine' => 183,
        'startColumn' => 2,
        'endColumn' => 46,
      ),
    ),
    'immediateProperties' => 
    array (
      'settingsService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'name' => 'settingsService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\SettingsService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 206,
        'endLine' => 206,
        'startColumn' => 3,
        'endColumn' => 51,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'registerResolver' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'name' => 'registerResolver',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 207,
        'endLine' => 207,
        'startColumn' => 3,
        'endColumn' => 57,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'userSession' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'name' => 'userSession',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IUserSession',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 208,
        'endLine' => 208,
        'startColumn' => 3,
        'endColumn' => 44,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'rootFolder' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'name' => 'rootFolder',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\Files\\IRootFolder',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 209,
        'endLine' => 209,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'ocrService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'name' => 'ocrService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\OcrService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 210,
        'endLine' => 210,
        'startColumn' => 3,
        'endColumn' => 41,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'eventDispatcher' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'name' => 'eventDispatcher',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 211,
        'endLine' => 211,
        'startColumn' => 3,
        'endColumn' => 52,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'container' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'name' => 'container',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Psr\\Container\\ContainerInterface',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 212,
        'endLine' => 212,
        'startColumn' => 3,
        'endColumn' => 48,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'name' => 'logger',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Psr\\Log\\LoggerInterface',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 213,
        'endLine' => 213,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'ibanExtractor' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'name' => 'ibanExtractor',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 214,
        'endLine' => 214,
        'startColumn' => 3,
        'endColumn' => 47,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'kvkExtractor' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'name' => 'kvkExtractor',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 215,
        'endLine' => 215,
        'startColumn' => 3,
        'endColumn' => 45,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'vatIdExtractor' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'name' => 'vatIdExtractor',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 216,
        'endLine' => 216,
        'startColumn' => 3,
        'endColumn' => 49,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'dateExtractor' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'name' => 'dateExtractor',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 217,
        'endLine' => 217,
        'startColumn' => 3,
        'endColumn' => 47,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'amountExtractor' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'name' => 'amountExtractor',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 218,
        'endLine' => 218,
        'startColumn' => 3,
        'endColumn' => 51,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'totalsReconciler' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'name' => 'totalsReconciler',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 219,
        'endLine' => 219,
        'startColumn' => 3,
        'endColumn' => 53,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'settingsService' => 
          array (
            'name' => 'settingsService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\SettingsService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 206,
            'endLine' => 206,
            'startColumn' => 3,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'registerResolver' => 
          array (
            'name' => 'registerResolver',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 207,
            'endLine' => 207,
            'startColumn' => 3,
            'endColumn' => 57,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'userSession' => 
          array (
            'name' => 'userSession',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IUserSession',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 208,
            'endLine' => 208,
            'startColumn' => 3,
            'endColumn' => 44,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'rootFolder' => 
          array (
            'name' => 'rootFolder',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Files\\IRootFolder',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 209,
            'endLine' => 209,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'ocrService' => 
          array (
            'name' => 'ocrService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\OcrService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 210,
            'endLine' => 210,
            'startColumn' => 3,
            'endColumn' => 41,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'eventDispatcher' => 
          array (
            'name' => 'eventDispatcher',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\EventDispatcher\\IEventDispatcher',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 211,
            'endLine' => 211,
            'startColumn' => 3,
            'endColumn' => 52,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
          'container' => 
          array (
            'name' => 'container',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Psr\\Container\\ContainerInterface',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 212,
            'endLine' => 212,
            'startColumn' => 3,
            'endColumn' => 48,
            'parameterIndex' => 6,
            'isOptional' => false,
          ),
          'logger' => 
          array (
            'name' => 'logger',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Psr\\Log\\LoggerInterface',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 213,
            'endLine' => 213,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 7,
            'isOptional' => false,
          ),
          'ibanExtractor' => 
          array (
            'name' => 'ibanExtractor',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 214,
            'endLine' => 214,
            'startColumn' => 3,
            'endColumn' => 47,
            'parameterIndex' => 8,
            'isOptional' => false,
          ),
          'kvkExtractor' => 
          array (
            'name' => 'kvkExtractor',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 215,
            'endLine' => 215,
            'startColumn' => 3,
            'endColumn' => 45,
            'parameterIndex' => 9,
            'isOptional' => false,
          ),
          'vatIdExtractor' => 
          array (
            'name' => 'vatIdExtractor',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 216,
            'endLine' => 216,
            'startColumn' => 3,
            'endColumn' => 49,
            'parameterIndex' => 10,
            'isOptional' => false,
          ),
          'dateExtractor' => 
          array (
            'name' => 'dateExtractor',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 217,
            'endLine' => 217,
            'startColumn' => 3,
            'endColumn' => 47,
            'parameterIndex' => 11,
            'isOptional' => false,
          ),
          'amountExtractor' => 
          array (
            'name' => 'amountExtractor',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 218,
            'endLine' => 218,
            'startColumn' => 3,
            'endColumn' => 51,
            'parameterIndex' => 12,
            'isOptional' => false,
          ),
          'totalsReconciler' => 
          array (
            'name' => 'totalsReconciler',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 219,
            'endLine' => 219,
            'startColumn' => 3,
            'endColumn' => 53,
            'parameterIndex' => 13,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor.
 *
 * @param SettingsService $settingsService Resolves OpenRegister\'s ObjectService.
 * @param OpenRegisterResolver $registerResolver Resolves register/schema bindings, failing closed.
 * @param IUserSession $userSession User session, for file resolution.
 * @param IRootFolder $rootFolder Root folder, for file resolution.
 * @param OcrService $ocrService OCR service (text acquisition seam).
 * @param IEventDispatcher $eventDispatcher Dispatches the completion event.
 * @param ContainerInterface $container DI container, for the optional AI provider.
 * @param LoggerInterface $logger Logger.
 * @param IbanExtractor $ibanExtractor Pure IBAN extractor.
 * @param KvkExtractor $kvkExtractor Pure KvK extractor.
 * @param VatIdExtractor $vatIdExtractor Pure BTW-nummer extractor.
 * @param DateExtractor $dateExtractor Pure date extractor.
 * @param AmountExtractor $amountExtractor Pure amount extractor.
 * @param TotalsReconciler $totalsReconciler Pure totals reconciler.
 *
 * @return void
 */',
        'startLine' => 205,
        'endLine' => 222,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
      'extractFinancial' => 
      array (
        'name' => 'extractFinancial',
        'parameters' => 
        array (
          'data' => 
          array (
            'name' => 'data',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 236,
            'endLine' => 236,
            'startColumn' => 35,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'requestedBy' => 
          array (
            'name' => 'requestedBy',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 236,
            'endLine' => 236,
            'startColumn' => 48,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Run the full extraction pipeline for a request and persist the result.
 *
 * @param array<string, mixed> $data Request body: `fileId|documentUri`, `docType`, `callbackEvent`.
 * @param string $requestedBy Nextcloud user id that initiated the extraction.
 *
 * @return array<string, mixed> The persisted `financialExtraction` object.
 *
 * @throws RuntimeException (code 400) On missing file reference or invalid docType.
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 */',
        'startLine' => 236,
        'endLine' => 278,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
      'resolveRequestParams' => 
      array (
        'name' => 'resolveRequestParams',
        'parameters' => 
        array (
          'data' => 
          array (
            'name' => 'data',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 292,
            'endLine' => 292,
            'startColumn' => 40,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Validate and normalise the extraction request body: docType, the
 * fileId/documentUri file reference (with a best-effort fallback that
 * derives a fileId from a trailing numeric documentUri segment), and the
 * provenance flags.
 *
 * @param array<string, mixed> $data Request body.
 *
 * @return array{docType: string, sourceApp: string, callbackEvent: bool, resolvedFileId: int|null, effectiveDocumentUri: string}
 *
 * @throws RuntimeException (code 400) On missing file reference or invalid docType.
 */',
        'startLine' => 292,
        'endLine' => 314,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
      'extractFileReference' => 
      array (
        'name' => 'extractFileReference',
        'parameters' => 
        array (
          'data' => 
          array (
            'name' => 'data',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 323,
            'endLine' => 323,
            'startColumn' => 40,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Read the `fileId`/`documentUri` file reference off the request body.
 *
 * @param array<string, mixed> $data Request body.
 *
 * @return array{0: int|null, 1: string|null} `[fileId, documentUri]`.
 */',
        'startLine' => 323,
        'endLine' => 335,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
      'resolveFileIdFallback' => 
      array (
        'name' => 'resolveFileIdFallback',
        'parameters' => 
        array (
          'fileId' => 
          array (
            'name' => 'fileId',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'int',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 346,
            'endLine' => 346,
            'startColumn' => 41,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'documentUri' => 
          array (
            'name' => 'documentUri',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 346,
            'endLine' => 346,
            'startColumn' => 55,
            'endColumn' => 74,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
          'data' => 
          array (
            'types' => 
            array (
              0 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'int',
                  'isIdentifier' => true,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'null',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Best-effort fallback: when only a documentUri was supplied, derive a
 * fileId from its trailing numeric path segment.
 *
 * @param int|null $fileId The explicit fileId, if any.
 * @param string|null $documentUri The explicit documentUri, if any.
 *
 * @return int|null The resolved fileId, or null when unresolvable.
 */',
        'startLine' => 346,
        'endLine' => 356,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
      'addCorrection' => 
      array (
        'name' => 'addCorrection',
        'parameters' => 
        array (
          'id' => 
          array (
            'name' => 'id',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 374,
            'endLine' => 374,
            'startColumn' => 32,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'correctedFields' => 
          array (
            'name' => 'correctedFields',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 374,
            'endLine' => 374,
            'startColumn' => 44,
            'endColumn' => 65,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'correctedBy' => 
          array (
            'name' => 'correctedBy',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 374,
            'endLine' => 374,
            'startColumn' => 68,
            'endColumn' => 86,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Store human-corrected field values against an existing extraction.
 *
 * Additive/non-destructive: the original `fields` object is never
 * mutated, corrections are appended to `corrections[]` (REQ-FIN-07).
 *
 * @param string $id The `financialExtraction` object id.
 * @param array<string, mixed> $correctedFields Map of field name to corrected value.
 * @param string $correctedBy Nextcloud user id submitting the corrections.
 *
 * @return array<string, mixed> The updated `financialExtraction` object.
 *
 * @throws RuntimeException (code 404) When no extraction exists for the given id.
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 */',
        'startLine' => 374,
        'endLine' => 409,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
      'runExtraction' => 
      array (
        'name' => 'runExtraction',
        'parameters' => 
        array (
          'text' => 
          array (
            'name' => 'text',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 430,
            'endLine' => 430,
            'startColumn' => 32,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'docType' => 
          array (
            'name' => 'docType',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 430,
            'endLine' => 430,
            'startColumn' => 46,
            'endColumn' => 60,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Run the deterministic heuristic pipeline against extracted text.
 *
 * Pure given its input (no I/O): shapes the full REQ-FIN-03 field set
 * (missing fields null, never omitted), assigns per-field confidence,
 * and reconciles totals.
 *
 * @param string $text The extracted document text.
 * @param string $docType `receipt` or `supplier-invoice`; part of the
 *                        REQ-FIN-01 pipeline signature and reserved for
 *                        a future docType-specific heuristic tuning pass
 *                        — the current heuristics apply uniformly.
 *
 * @return array{fields: array<string, mixed>, fieldConfidence: array<string, float>, overallConfidence: float, reconciled: bool}
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter) $docType reserved for future per-type heuristic tuning
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 */',
        'startLine' => 430,
        'endLine' => 491,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
      'applyAiEnhancement' => 
      array (
        'name' => 'applyAiEnhancement',
        'parameters' => 
        array (
          'text' => 
          array (
            'name' => 'text',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 509,
            'endLine' => 509,
            'startColumn' => 37,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'result' => 
          array (
            'name' => 'result',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 509,
            'endLine' => 509,
            'startColumn' => 51,
            'endColumn' => 63,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'requestedBy' => 
          array (
            'name' => 'requestedBy',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 509,
            'endLine' => 509,
            'startColumn' => 66,
            'endColumn' => 84,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Apply an optional AI-backend enhancement pass to fill null/low-confidence
 * fields (REQ-FIN-06). Absent-safe: returns the input unchanged when no
 * provider is available or the AI call fails for any reason.
 *
 * @param string $text The extracted document text.
 * @param array<string, mixed> $result The heuristic-only pipeline result
 *                                     (`{fields, fieldConfidence,
 *                                     overallConfidence, reconciled}`,
 *                                     see {@see runExtraction()}).
 * @param string $requestedBy Nextcloud user id (task quota attribution).
 *
 * @return array<string, mixed> The (possibly AI-enhanced) pipeline result, same shape as the input.
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 */',
        'startLine' => 509,
        'endLine' => 535,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
      'mergeAiFields' => 
      array (
        'name' => 'mergeAiFields',
        'parameters' => 
        array (
          'result' => 
          array (
            'name' => 'result',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 548,
            'endLine' => 548,
            'startColumn' => 33,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'fillable' => 
          array (
            'name' => 'fillable',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 548,
            'endLine' => 548,
            'startColumn' => 48,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'decoded' => 
          array (
            'name' => 'decoded',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 548,
            'endLine' => 548,
            'startColumn' => 65,
            'endColumn' => 78,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Merge the AI\'s decoded field values onto the pipeline result: only
 * fillable fields with a non-empty decoded value are written, each at
 * the fixed AI-fill confidence, and the aggregate is recomputed.
 *
 * @param array<string, mixed> $result Pipeline result to merge onto.
 * @param array<int, string> $fillable Field names the AI was allowed to fill.
 * @param array<string, mixed> $decoded Decoded AI JSON response.
 *
 * @return array<string, mixed> The merged pipeline result.
 */',
        'startLine' => 548,
        'endLine' => 566,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
      'applyExtraction' => 
      array (
        'name' => 'applyExtraction',
        'parameters' => 
        array (
          'fields' => 
          array (
            'name' => 'fields',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => true,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 579,
            'endLine' => 579,
            'startColumn' => 35,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'confidence' => 
          array (
            'name' => 'confidence',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => true,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 579,
            'endLine' => 579,
            'startColumn' => 51,
            'endColumn' => 68,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'field' => 
          array (
            'name' => 'field',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 579,
            'endLine' => 579,
            'startColumn' => 71,
            'endColumn' => 83,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'extracted' => 
          array (
            'name' => 'extracted',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 579,
            'endLine' => 579,
            'startColumn' => 86,
            'endColumn' => 101,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Apply one extractor\'s `{value, confidence}` result onto the shaped
 * field set, only when a value was found.
 *
 * @param array<string, mixed> $fields Field set (by reference).
 * @param array<string, float> $confidence Confidence map (by reference).
 * @param string $field Field key to write.
 * @param array{value: mixed, confidence: float} $extracted Extractor result.
 *
 * @return void
 */',
        'startLine' => 579,
        'endLine' => 587,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
      'extractSupplierName' => 
      array (
        'name' => 'extractSupplierName',
        'parameters' => 
        array (
          'text' => 
          array (
            'name' => 'text',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 597,
            'endLine' => 597,
            'startColumn' => 39,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Heuristic supplier-name match: a capitalised phrase ending in a Dutch
 * legal-entity suffix (B.V. / N.V.).
 *
 * @param string $text The text to search.
 *
 * @return array{value: string|null, confidence: float}
 */',
        'startLine' => 597,
        'endLine' => 612,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
      'extractInvoiceNumber' => 
      array (
        'name' => 'extractInvoiceNumber',
        'parameters' => 
        array (
          'text' => 
          array (
            'name' => 'text',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 621,
            'endLine' => 621,
            'startColumn' => 40,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Heuristic invoice-number match: a labelled alphanumeric token.
 *
 * @param string $text The text to search.
 *
 * @return array{value: string|null, confidence: float}
 */',
        'startLine' => 621,
        'endLine' => 641,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
      'extractCurrency' => 
      array (
        'name' => 'extractCurrency',
        'parameters' => 
        array (
          'text' => 
          array (
            'name' => 'text',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 650,
            'endLine' => 650,
            'startColumn' => 35,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Heuristic currency marker: `€` or `EUR` anywhere in the text.
 *
 * @param string $text The text to search.
 *
 * @return array{value: string|null, confidence: float}
 */',
        'startLine' => 650,
        'endLine' => 659,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
      'extractVatBreakdown' => 
      array (
        'name' => 'extractVatBreakdown',
        'parameters' => 
        array (
          'text' => 
          array (
            'name' => 'text',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 671,
            'endLine' => 671,
            'startColumn' => 39,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Extract a VAT breakdown (rate/base/amount) per distinct VAT rate
 * mentioned in the text. For each `NN%` occurrence, the amount tokens on
 * the same line are inspected: the larger is treated as the base, the
 * smaller as the VAT amount (REQ-FIN-03).
 *
 * @param string $text The text to search.
 *
 * @return array<int, array{rate: int, base: float|null, amount: float|null}>
 */',
        'startLine' => 671,
        'endLine' => 703,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
      'splitBaseAndAmount' => 
      array (
        'name' => 'splitBaseAndAmount',
        'parameters' => 
        array (
          'values' => 
          array (
            'name' => 'values',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 715,
            'endLine' => 715,
            'startColumn' => 38,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Split VAT-line amount tokens into a (base, amount) pair: with two or
 * more values the larger is the base and the smaller the VAT amount
 * (valid for rates under 100%); with a single value it is treated as
 * the VAT amount only.
 *
 * @param array<int, float> $values Amount values found on the VAT-rate line.
 *
 * @return array{0: float|null, 1: float|null} `[base, amount]`.
 */',
        'startLine' => 715,
        'endLine' => 725,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
      'lineContaining' => 
      array (
        'name' => 'lineContaining',
        'parameters' => 
        array (
          'lines' => 
          array (
            'name' => 'lines',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 735,
            'endLine' => 735,
            'startColumn' => 34,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'offset' => 
          array (
            'name' => 'offset',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 735,
            'endLine' => 735,
            'startColumn' => 48,
            'endColumn' => 58,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Find the line of text containing a given byte offset.
 *
 * @param array<int, string> $lines Pre-split lines of the full text.
 * @param int $offset Byte offset within the original text.
 *
 * @return string The line containing the offset.
 */',
        'startLine' => 735,
        'endLine' => 752,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
      'aggregateConfidence' => 
      array (
        'name' => 'aggregateConfidence',
        'parameters' => 
        array (
          'confidence' => 
          array (
            'name' => 'confidence',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 761,
            'endLine' => 761,
            'startColumn' => 39,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'float',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Aggregate populated field confidences into a single overall score.
 *
 * @param array<string, float> $confidence Per-field confidence map.
 *
 * @return float The aggregate confidence (0..1), or 0 when empty.
 */',
        'startLine' => 761,
        'endLine' => 769,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
      'fillableFields' => 
      array (
        'name' => 'fillableFields',
        'parameters' => 
        array (
          'result' => 
          array (
            'name' => 'result',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 779,
            'endLine' => 779,
            'startColumn' => 34,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine which scalar fields are eligible for AI enhancement: null or
 * below the low-confidence threshold, and not checksum-locked.
 *
 * @param array{fields: array<string, mixed>, fieldConfidence: array<string, float>, reconciled: bool} $result Pipeline result.
 *
 * @return array<int, string> Fillable field names.
 */',
        'startLine' => 779,
        'endLine' => 800,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
      'lockedFields' => 
      array (
        'name' => 'lockedFields',
        'parameters' => 
        array (
          'result' => 
          array (
            'name' => 'result',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 810,
            'endLine' => 810,
            'startColumn' => 32,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine which fields are checksum-locked and must never be
 * overwritten by the AI enhancement step (REQ-FIN-06).
 *
 * @param array{fields: array<string, mixed>, reconciled: bool} $result Pipeline result.
 *
 * @return array<int, string> Locked field names.
 */',
        'startLine' => 810,
        'endLine' => 821,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
      'resolveAiManager' => 
      array (
        'name' => 'resolveAiManager',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
          'data' => 
          array (
            'types' => 
            array (
              0 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'array',
                  'isIdentifier' => true,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'null',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resolve the preferred available local AI text-processing manager.
 *
 * Prefers `OCP\\TaskProcessing\\IManager` (NC 30+), falls back to the
 * deprecated `OCP\\TextProcessing\\IManager`. Both are resolved lazily and
 * guarded so this class loads cleanly when neither namespace exists.
 *
 * @return array{type: string, manager: object}|null Null when unavailable.
 */',
        'startLine' => 832,
        'endLine' => 856,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
      'runAiTask' => 
      array (
        'name' => 'runAiTask',
        'parameters' => 
        array (
          'manager' => 
          array (
            'name' => 'manager',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 869,
            'endLine' => 869,
            'startColumn' => 29,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'text' => 
          array (
            'name' => 'text',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 869,
            'endLine' => 869,
            'startColumn' => 45,
            'endColumn' => 56,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'fillable' => 
          array (
            'name' => 'fillable',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 869,
            'endLine' => 869,
            'startColumn' => 59,
            'endColumn' => 73,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'requestedBy' => 
          array (
            'name' => 'requestedBy',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 869,
            'endLine' => 869,
            'startColumn' => 76,
            'endColumn' => 94,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Run a single structured-extraction prompt through the resolved local
 * AI manager and return its raw text output.
 *
 * @param array{type: string, manager: object} $manager Resolved AI manager.
 * @param string $text Document text (prompt context).
 * @param array<int, string> $fillable Field names the AI may fill.
 * @param string $requestedBy Nextcloud user id (task attribution).
 *
 * @return string Raw model output.
 */',
        'startLine' => 869,
        'endLine' => 897,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
      'buildPrompt' => 
      array (
        'name' => 'buildPrompt',
        'parameters' => 
        array (
          'text' => 
          array (
            'name' => 'text',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 907,
            'endLine' => 907,
            'startColumn' => 31,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'fillable' => 
          array (
            'name' => 'fillable',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 907,
            'endLine' => 907,
            'startColumn' => 45,
            'endColumn' => 59,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Build the structured-extraction prompt for the AI enhancement step.
 *
 * @param string $text Document text (truncated for prompt size).
 * @param array<int, string> $fillable Field names the AI should attempt to fill.
 *
 * @return string The prompt.
 */',
        'startLine' => 907,
        'endLine' => 916,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
      'stripCodeFences' => 
      array (
        'name' => 'stripCodeFences',
        'parameters' => 
        array (
          'text' => 
          array (
            'name' => 'text',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 925,
            'endLine' => 925,
            'startColumn' => 35,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Strip ```json ... ``` / ``` ... ``` code fences from a model response, if present.
 *
 * @param string $text Raw model output.
 *
 * @return string
 */',
        'startLine' => 925,
        'endLine' => 932,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
      'dispatchCompletionEvent' => 
      array (
        'name' => 'dispatchCompletionEvent',
        'parameters' => 
        array (
          'documentUri' => 
          array (
            'name' => 'documentUri',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 950,
            'endLine' => 950,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'requestedBy' => 
          array (
            'name' => 'requestedBy',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 951,
            'endLine' => 951,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'sourceApp' => 
          array (
            'name' => 'sourceApp',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 952,
            'endLine' => 952,
            'startColumn' => 3,
            'endColumn' => 19,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'docType' => 
          array (
            'name' => 'docType',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 953,
            'endLine' => 953,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'pipeline' => 
          array (
            'name' => 'pipeline',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 954,
            'endLine' => 954,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Dispatch the canonical `nl.conduction.filinq.extraction.completed`
 * event. Fail-soft: the already-persisted result is never rolled back on
 * a dispatch failure (mirrors SigningService::emitConclusionIfDelegated).
 *
 * @param string $documentUri Source document URI.
 * @param string $requestedBy Requesting user id.
 * @param string $sourceApp Requesting app id.
 * @param string $docType `receipt` or `supplier-invoice`.
 * @param array<string, mixed> $pipeline Pipeline result (`{fields, fieldConfidence, overallConfidence}`).
 *
 * @return void
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 */',
        'startLine' => 949,
        'endLine' => 975,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
      'resolveText' => 
      array (
        'name' => 'resolveText',
        'parameters' => 
        array (
          'fileId' => 
          array (
            'name' => 'fileId',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'int',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 985,
            'endLine' => 985,
            'startColumn' => 31,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resolve document text for a Nextcloud file id: reuse embedded PDF text
 * when present, otherwise fall back to OCR via OcrService (REQ-FIN-01).
 *
 * @param int|null $fileId The Nextcloud file id, or null when unresolvable.
 *
 * @return array{text: string, ocrConfidence: float}
 */',
        'startLine' => 985,
        'endLine' => 1011,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
      'runOcr' => 
      array (
        'name' => 'runOcr',
        'parameters' => 
        array (
          'file' => 
          array (
            'name' => 'file',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Files\\File',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1024,
            'endLine' => 1024,
            'startColumn' => 26,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'mimeType' => 
          array (
            'name' => 'mimeType',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1024,
            'endLine' => 1024,
            'startColumn' => 38,
            'endColumn' => 53,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'embedded' => 
          array (
            'name' => 'embedded',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1024,
            'endLine' => 1024,
            'startColumn' => 56,
            'endColumn' => 71,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Stage the file to a temp path and run Tesseract OCR on it. Absent-safe:
 * any failure at any stage falls back to the embedded text (possibly
 * empty) with zero OCR confidence.
 *
 * @param File $file The Nextcloud file to OCR.
 * @param string $mimeType The file MIME type.
 * @param string $embedded Embedded text already extracted (fallback value).
 *
 * @return array{text: string, ocrConfidence: float}
 */',
        'startLine' => 1024,
        'endLine' => 1055,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
      'extractEmbeddedPdfText' => 
      array (
        'name' => 'extractEmbeddedPdfText',
        'parameters' => 
        array (
          'file' => 
          array (
            'name' => 'file',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Files\\File',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1066,
            'endLine' => 1066,
            'startColumn' => 42,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Extract embedded PDF text via the `pdftotext` CLI (poppler-utils), when
 * available. Absent-safe: any failure yields an empty string so the
 * caller falls through to OCR.
 *
 * @param File $file The PDF file.
 *
 * @return string The embedded text, or \'\' when unavailable/unextractable.
 */',
        'startLine' => 1066,
        'endLine' => 1088,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
      'resolveFile' => 
      array (
        'name' => 'resolveFile',
        'parameters' => 
        array (
          'fileId' => 
          array (
            'name' => 'fileId',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1097,
            'endLine' => 1097,
            'startColumn' => 31,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
          'data' => 
          array (
            'types' => 
            array (
              0 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'OCP\\Files\\File',
                  'isIdentifier' => false,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'null',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resolve a Nextcloud file by id, scoped to the current user\'s folder.
 *
 * @param int $fileId The Nextcloud file id.
 *
 * @return File|null The file, or null when not found/not accessible.
 */',
        'startLine' => 1097,
        'endLine' => 1115,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
      'writeToTemp' => 
      array (
        'name' => 'writeToTemp',
        'parameters' => 
        array (
          'file' => 
          array (
            'name' => 'file',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Files\\File',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1126,
            'endLine' => 1126,
            'startColumn' => 31,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Write a Nextcloud file to a temporary location for processing.
 *
 * @param File $file The Nextcloud file.
 *
 * @return string Path to the temporary file.
 *
 * @throws RuntimeException If writing fails.
 */',
        'startLine' => 1126,
        'endLine' => 1136,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
      'toArray' => 
      array (
        'name' => 'toArray',
        'parameters' => 
        array (
          'object' => 
          array (
            'name' => 'object',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1146,
            'endLine' => 1146,
            'startColumn' => 27,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Normalise an ObjectService result to an array (mirrors
 * SigningService::toArray()).
 *
 * @param mixed $object The ObjectEntity (or array) to normalise.
 *
 * @return array<string, mixed> The serialized object.
 */',
        'startLine' => 1146,
        'endLine' => 1152,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
        'aliasName' => NULL,
      ),
    ),
    'traitsData' => 
    array (
      'aliases' => 
      array (
      ),
      'modifiers' => 
      array (
      ),
      'precedences' => 
      array (
      ),
      'hashes' => 
      array (
      ),
    ),
  ),
));