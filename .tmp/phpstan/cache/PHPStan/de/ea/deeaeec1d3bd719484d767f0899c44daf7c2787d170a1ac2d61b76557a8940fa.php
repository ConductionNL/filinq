<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Event/IntakeDocumentReceivedEvent.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Event\IntakeDocumentReceivedEvent
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-4d836e81774c1f5dc68c512f21302b710fc97836539c1b2343297bdcc29ed693',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Event/IntakeDocumentReceivedEvent.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Event',
    'name' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
    'shortName' => 'IntakeDocumentReceivedEvent',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * One document arrived through one channel.
 *
 * @category Event
 * @package  OCA\\Filinq\\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 43,
    'endLine' => 189,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'OCP\\EventDispatcher\\Event',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'CHANNEL_SCAN' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'name' => 'CHANNEL_SCAN',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'scan\'',
          'attributes' => 
          array (
            'startLine' => 50,
            'endLine' => 50,
            'startTokenPos' => 44,
            'startFilePos' => 1472,
            'endTokenPos' => 44,
            'endFilePos' => 1477,
          ),
        ),
        'docComment' => '/**
 * The scanner channel.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 50,
        'endLine' => 50,
        'startColumn' => 2,
        'endColumn' => 36,
      ),
      'CHANNEL_MAIL' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'name' => 'CHANNEL_MAIL',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'mail\'',
          'attributes' => 
          array (
            'startLine' => 57,
            'endLine' => 57,
            'startTokenPos' => 57,
            'startFilePos' => 1572,
            'endTokenPos' => 57,
            'endFilePos' => 1577,
          ),
        ),
        'docComment' => '/**
 * The shared mailbox channel.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 2,
        'endColumn' => 36,
      ),
      'CHANNEL_DIGITAL_POST' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'name' => 'CHANNEL_DIGITAL_POST',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'digitalPost\'',
          'attributes' => 
          array (
            'startLine' => 64,
            'endLine' => 64,
            'startTokenPos' => 70,
            'startFilePos' => 1678,
            'endTokenPos' => 70,
            'endFilePos' => 1690,
          ),
        ),
        'docComment' => '/**
 * The digital post channel.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 64,
        'endLine' => 64,
        'startColumn' => 2,
        'endColumn' => 51,
      ),
      'CHANNELS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'name' => 'CHANNELS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[self::CHANNEL_SCAN, self::CHANNEL_MAIL, self::CHANNEL_DIGITAL_POST]',
          'attributes' => 
          array (
            'startLine' => 71,
            'endLine' => 75,
            'startTokenPos' => 83,
            'startFilePos' => 1797,
            'endTokenPos' => 100,
            'endFilePos' => 1874,
          ),
        ),
        'docComment' => '/**
 * Every channel this app accepts.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 71,
        'endLine' => 75,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'channel' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'name' => 'channel',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 92,
        'endLine' => 92,
        'startColumn' => 3,
        'endColumn' => 34,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'fileId' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'name' => 'fileId',
        'modifiers' => 132,
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
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 93,
            'endLine' => 93,
            'startTokenPos' => 132,
            'startFilePos' => 2578,
            'endTokenPos' => 132,
            'endFilePos' => 2581,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 93,
        'endLine' => 93,
        'startColumn' => 3,
        'endColumn' => 38,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'fileName' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'name' => 'fileName',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '\'\'',
          'attributes' => 
          array (
            'startLine' => 94,
            'endLine' => 94,
            'startTokenPos' => 145,
            'startFilePos' => 2622,
            'endTokenPos' => 145,
            'endFilePos' => 2623,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 94,
        'endLine' => 94,
        'startColumn' => 3,
        'endColumn' => 40,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'subject' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'name' => 'subject',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '\'\'',
          'attributes' => 
          array (
            'startLine' => 95,
            'endLine' => 95,
            'startTokenPos' => 158,
            'startFilePos' => 2663,
            'endTokenPos' => 158,
            'endFilePos' => 2664,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 95,
        'endLine' => 95,
        'startColumn' => 3,
        'endColumn' => 39,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'sender' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'name' => 'sender',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '\'\'',
          'attributes' => 
          array (
            'startLine' => 96,
            'endLine' => 96,
            'startTokenPos' => 171,
            'startFilePos' => 2703,
            'endTokenPos' => 171,
            'endFilePos' => 2704,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 96,
        'endLine' => 96,
        'startColumn' => 3,
        'endColumn' => 38,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'sourceRef' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'name' => 'sourceRef',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '\'\'',
          'attributes' => 
          array (
            'startLine' => 97,
            'endLine' => 97,
            'startTokenPos' => 184,
            'startFilePos' => 2746,
            'endTokenPos' => 184,
            'endFilePos' => 2747,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 97,
        'endLine' => 97,
        'startColumn' => 3,
        'endColumn' => 41,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'receivedAt' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'name' => 'receivedAt',
        'modifiers' => 132,
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
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 98,
            'endLine' => 98,
            'startTokenPos' => 198,
            'startFilePos' => 2791,
            'endTokenPos' => 198,
            'endFilePos' => 2794,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 98,
        'endLine' => 98,
        'startColumn' => 3,
        'endColumn' => 45,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'arrivedWith' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'name' => 'arrivedWith',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '\'\'',
          'attributes' => 
          array (
            'startLine' => 99,
            'endLine' => 99,
            'startTokenPos' => 211,
            'startFilePos' => 2838,
            'endTokenPos' => 211,
            'endFilePos' => 2839,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 99,
        'endLine' => 99,
        'startColumn' => 3,
        'endColumn' => 43,
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
          'channel' => 
          array (
            'name' => 'channel',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 92,
            'endLine' => 92,
            'startColumn' => 3,
            'endColumn' => 34,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'fileId' => 
          array (
            'name' => 'fileId',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 93,
                'endLine' => 93,
                'startTokenPos' => 132,
                'startFilePos' => 2578,
                'endTokenPos' => 132,
                'endFilePos' => 2581,
              ),
            ),
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 93,
            'endLine' => 93,
            'startColumn' => 3,
            'endColumn' => 38,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'fileName' => 
          array (
            'name' => 'fileName',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 94,
                'endLine' => 94,
                'startTokenPos' => 145,
                'startFilePos' => 2622,
                'endTokenPos' => 145,
                'endFilePos' => 2623,
              ),
            ),
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 94,
            'endLine' => 94,
            'startColumn' => 3,
            'endColumn' => 40,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'subject' => 
          array (
            'name' => 'subject',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 95,
                'endLine' => 95,
                'startTokenPos' => 158,
                'startFilePos' => 2663,
                'endTokenPos' => 158,
                'endFilePos' => 2664,
              ),
            ),
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 95,
            'endLine' => 95,
            'startColumn' => 3,
            'endColumn' => 39,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'sender' => 
          array (
            'name' => 'sender',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 96,
                'endLine' => 96,
                'startTokenPos' => 171,
                'startFilePos' => 2703,
                'endTokenPos' => 171,
                'endFilePos' => 2704,
              ),
            ),
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 96,
            'endLine' => 96,
            'startColumn' => 3,
            'endColumn' => 38,
            'parameterIndex' => 4,
            'isOptional' => true,
          ),
          'sourceRef' => 
          array (
            'name' => 'sourceRef',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 97,
                'endLine' => 97,
                'startTokenPos' => 184,
                'startFilePos' => 2746,
                'endTokenPos' => 184,
                'endFilePos' => 2747,
              ),
            ),
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 97,
            'endLine' => 97,
            'startColumn' => 3,
            'endColumn' => 41,
            'parameterIndex' => 5,
            'isOptional' => true,
          ),
          'receivedAt' => 
          array (
            'name' => 'receivedAt',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 98,
                'endLine' => 98,
                'startTokenPos' => 198,
                'startFilePos' => 2791,
                'endTokenPos' => 198,
                'endFilePos' => 2794,
              ),
            ),
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 98,
            'endLine' => 98,
            'startColumn' => 3,
            'endColumn' => 45,
            'parameterIndex' => 6,
            'isOptional' => true,
          ),
          'arrivedWith' => 
          array (
            'name' => 'arrivedWith',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 99,
                'endLine' => 99,
                'startTokenPos' => 211,
                'startFilePos' => 2838,
                'endTokenPos' => 211,
                'endFilePos' => 2839,
              ),
            ),
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 3,
            'endColumn' => 43,
            'parameterIndex' => 7,
            'isOptional' => true,
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
 * @param string $channel The channel that delivered the document.
 * @param int|null $fileId Nextcloud file id of the delivered file.
 * @param string $fileName Name of the delivered file.
 * @param string $subject Subject line or document title.
 * @param string $sender Who sent it.
 * @param string $sourceRef The channel\'s own reference for this delivery.
 * @param string|null $receivedAt When it arrived, ISO 8601, or null for now.
 * @param string $arrivedWith The intake document of the message this file came as an attachment of.
 *
 * @return void
 */',
        'startLine' => 91,
        'endLine' => 103,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'aliasName' => NULL,
      ),
      'getChannel' => 
      array (
        'name' => 'getChannel',
        'parameters' => 
        array (
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
 * The channel that delivered the document.
 *
 * @return string The channel.
 */',
        'startLine' => 110,
        'endLine' => 113,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'aliasName' => NULL,
      ),
      'getFileId' => 
      array (
        'name' => 'getFileId',
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
 * The Nextcloud file id, when the channel stored a file.
 *
 * @return int|null The file id.
 */',
        'startLine' => 120,
        'endLine' => 123,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'aliasName' => NULL,
      ),
      'getFileName' => 
      array (
        'name' => 'getFileName',
        'parameters' => 
        array (
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
 * The file name as delivered.
 *
 * @return string The file name.
 */',
        'startLine' => 130,
        'endLine' => 133,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'aliasName' => NULL,
      ),
      'getSubject' => 
      array (
        'name' => 'getSubject',
        'parameters' => 
        array (
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
 * The subject line or document title.
 *
 * @return string The subject.
 */',
        'startLine' => 140,
        'endLine' => 143,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'aliasName' => NULL,
      ),
      'getSender' => 
      array (
        'name' => 'getSender',
        'parameters' => 
        array (
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
 * Who sent the document.
 *
 * @return string The sender.
 */',
        'startLine' => 150,
        'endLine' => 153,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'aliasName' => NULL,
      ),
      'getSourceRef' => 
      array (
        'name' => 'getSourceRef',
        'parameters' => 
        array (
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
 * The channel\'s own reference for this delivery.
 *
 * @return string The reference.
 */',
        'startLine' => 160,
        'endLine' => 163,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'aliasName' => NULL,
      ),
      'getReceivedAt' => 
      array (
        'name' => 'getReceivedAt',
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * When the document arrived.
 *
 * @return string|null The ISO 8601 timestamp, or null when the channel did not say.
 */',
        'startLine' => 170,
        'endLine' => 173,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'aliasName' => NULL,
      ),
      'getArrivedWith' => 
      array (
        'name' => 'getArrivedWith',
        'parameters' => 
        array (
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
 * The message this file arrived as an attachment of.
 *
 * An attachment is a record of its own, and it names its message rather
 * than being folded into it: one attachment often belongs to a different
 * case from the letter it came with, and a folded attachment has no way to
 * say so.
 *
 * @return string The intake document uuid of the message, or an empty string.
 */',
        'startLine' => 185,
        'endLine' => 188,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\IntakeDocumentReceivedEvent',
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