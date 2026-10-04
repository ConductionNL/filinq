<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/EventListener/IntakeDocumentReceivedListener.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\EventListener\IntakeDocumentReceivedListener
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-2d5af81ea022a16239d174524dd2947189e507938c22b537f241d0d5561d688d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\EventListener\\IntakeDocumentReceivedListener',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/EventListener/IntakeDocumentReceivedListener.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\EventListener',
    'name' => 'OCA\\Filinq\\EventListener\\IntakeDocumentReceivedListener',
    'shortName' => 'IntakeDocumentReceivedListener',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Creates one waiting intake document per delivered document.
 *
 * @category EventListener
 * @package  OCA\\Filinq\\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @template-implements IEventListener<Event>
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 50,
    'endLine' => 109,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'OCP\\EventDispatcher\\IEventListener',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'intake' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\IntakeDocumentReceivedListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\IntakeDocumentReceivedListener',
        'name' => 'intake',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\IntakeService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 61,
        'endLine' => 61,
        'startColumn' => 3,
        'endColumn' => 40,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\IntakeDocumentReceivedListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\IntakeDocumentReceivedListener',
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
        'startLine' => 62,
        'endLine' => 62,
        'startColumn' => 3,
        'endColumn' => 42,
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
          'intake' => 
          array (
            'name' => 'intake',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\IntakeService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 61,
            'endLine' => 61,
            'startColumn' => 3,
            'endColumn' => 40,
            'parameterIndex' => 0,
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
            'startLine' => 62,
            'endLine' => 62,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 1,
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
 * @param IntakeService $intake The intake inbox.
 * @param LoggerInterface $logger Logger for diagnostics.
 *
 * @return void
 */',
        'startLine' => 60,
        'endLine' => 65,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\IntakeDocumentReceivedListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\IntakeDocumentReceivedListener',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\IntakeDocumentReceivedListener',
        'aliasName' => NULL,
      ),
      'handle' => 
      array (
        'name' => 'handle',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\EventDispatcher\\Event',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 79,
            'endLine' => 79,
            'startColumn' => 25,
            'endColumn' => 36,
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
 * Handle one delivery.
 *
 * A feeder is usually a background job, so a refusal here is logged rather
 * than thrown: a malformed delivery must not stop the rest of the batch.
 *
 * @param Event $event The dispatched event.
 *
 * @return void
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */',
        'startLine' => 79,
        'endLine' => 108,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\IntakeDocumentReceivedListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\IntakeDocumentReceivedListener',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\IntakeDocumentReceivedListener',
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