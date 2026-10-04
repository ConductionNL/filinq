<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Exception/AgreementStoreUnreadableException.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Exception\AgreementStoreUnreadableException
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-a090664959dd68ace049f6887687ff2910bdc2f02e05e0a3582fcec363bf67eb',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Exception\\AgreementStoreUnreadableException',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Exception/AgreementStoreUnreadableException.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Exception',
    'name' => 'OCA\\Filinq\\Exception\\AgreementStoreUnreadableException',
    'shortName' => 'AgreementStoreUnreadableException',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * A read of the agreement store that failed, rather than one that found nothing.
 *
 * 🔴 THIS EXCEPTION EXISTS BECAUSE THE TWO ANSWERS LOOKED THE SAME. The store
 * used to swallow a read failure into an empty list, and an empty list means
 * "no agreement was declared", which means "this file is not gated". So a
 * register that was down, or a schema that did not resolve, read as permission
 * to download. Raising instead of returning keeps the failure a failure, and
 * the gate turns it into a refusal.
 *
 * 🔑 IT IS NOT THROWN WHEN THE SCHEMA IS SIMPLY ABSENT. An instance that never
 * imported `downloadAgreement` gates nothing, which is a real answer rather
 * than a failed read, and refusing every download there would take the whole
 * download surface down over a feature nobody enabled.
 *
 * @category Exception
 * @package  OCA\\Filinq\\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 48,
    'endLine' => 50,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'RuntimeException',
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