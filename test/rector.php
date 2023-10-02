<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
	$rectorConfig->paths([__DIR__]);

	$rectorConfig->sets([
		LevelSetList::UP_TO_PHP_82,
		SetList::CODE_QUALITY,
		SetList::CODING_STYLE,
		SetList::DEAD_CODE,
		SetList::STRICT_BOOLEANS,
		SetList::GMAGICK_TO_IMAGICK,
		SetList::NAMING,
		SetList::PRIVATIZATION,
		// SetList::TYPE_DECLARATION,
		SetList::EARLY_RETURN,
		SetList::INSTANCEOF,
	]);

	$rectorConfig->phpVersion(PhpVersion::PHP_80);

	$rectorConfig->rules([
		
	]);

	$rectorConfig->skip([
		'**/tmp/*',
		'**/css/*',
		'**/js/*',
		'**/data/*',
		'**/doc/*',
		'**/guide/*',
		'**/images/*',
		'**/files/*',
		'**/sp/*',
		'**/tpl/*',
		'**/tests/*',
		'**/test/*',
		'**/smarty*',
		'**/vendor/*',
		'**/lp/**',
		'**/rector/**',
		'**/View/**',

		Rector\Php54\Rector\Array_\LongArrayToShortArrayRector::class,
		Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector::class,

		// // Decorate read-only property with readonly attribute
		// Rector\Php81\Rector\Property\ReadOnlyPropertyRector::class,

		// // Refactor Spatie enum class to native Enum
		// Rector\Php81\Rector\Class_\SpatieEnumClassToEnumRector::class,

		// // Refactor Spatie enum method calls
		// Rector\Php81\Rector\MethodCall\SpatieEnumMethodCallToEnumConstRector::class,

		// // Add SensitiveParameter attribute to method and function configured parameters
		// Rector\Php82\Rector\Param\AddSensitiveParameterAttributeRector::class,

		// // Decorate read-only class with readonly attribute
		// Rector\Php82\Rector\Class_\ReadOnlyClassRector::class,

		// // Upgrade array callable to first class callable
		// Rector\Php81\Rector\Array_\FirstClassCallableRector::class,
	]);
};
