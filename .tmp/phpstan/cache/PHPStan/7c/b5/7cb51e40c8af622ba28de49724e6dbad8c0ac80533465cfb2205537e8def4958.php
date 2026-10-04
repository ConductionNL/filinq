<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/AppInfo/PdfConversionRegistrar.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\AppInfo\PdfConversionRegistrar
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-de7e730b21a320cace9162a885423856496e2c9f702c54eb3d8f1a95c554c73f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\AppInfo\\PdfConversionRegistrar',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/AppInfo/PdfConversionRegistrar.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\AppInfo',
    'name' => 'OCA\\Filinq\\AppInfo\\PdfConversionRegistrar',
    'shortName' => 'PdfConversionRegistrar',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Registers the PdfConversionService backend cascade.
 *
 * @category AppInfo
 * @package  OCA\\Filinq\\AppInfo
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 45,
    'endLine' => 90,
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
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'register' => 
      array (
        'name' => 'register',
        'parameters' => 
        array (
          'context' => 
          array (
            'name' => 'context',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\AppFramework\\Bootstrap\\IRegistrationContext',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 66,
            'endLine' => 66,
            'startColumn' => 27,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Wire the PDF-conversion cascade.
 *
 * PdfConversionService takes an ordered array of backends in its
 * constructor; Nextcloud\'s DI cannot autowire an `array` parameter, so
 * without this explicit registration every service that depends on
 * PdfConversionService (e.g. AnonymizationService → AnonymizationController)
 * fails to construct and the request 500s with a "Could not resolve
 * backends!" QueryException before the controller body ever runs. Order =
 * OfficeApp → LibreOffice → PhpWord → mPDF → EML (first success wins).
 * LibreOfficeHeadlessBackend (pdf-conversion-service) shells out to
 * `soffice --headless` with a lock + timeout as a high-fidelity fallback
 * when the NC IConversionManager providers are unavailable.
 *
 * @param IRegistrationContext $context The registration context.
 *
 * @return void
 *
 * @spec openspec/specs/pdf-conversion/spec.md
 */',
        'startLine' => 66,
        'endLine' => 89,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\AppInfo',
        'declaringClassName' => 'OCA\\Filinq\\AppInfo\\PdfConversionRegistrar',
        'implementingClassName' => 'OCA\\Filinq\\AppInfo\\PdfConversionRegistrar',
        'currentClassName' => 'OCA\\Filinq\\AppInfo\\PdfConversionRegistrar',
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