<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitPublishPressStatuses
{
    public static $files = array (
        '3b1e1688e889525de91ac2456aba9efd' => __DIR__ . '/..' . '/publishpress/psr-container/lib/include.php',
        '24b27b1b9a32bf58eda571c3e5ae3480' => __DIR__ . '/..' . '/publishpress/pimple-pimple/lib/include.php',
        '0078757fbd019a5f202f2be6585c3626' => __DIR__ . '/..' . '/publishpress/wordpress-banners/BannersMain.php',
        '41c664bd04a95c2d6a2f2a3e00f06593' => __DIR__ . '/..' . '/publishpress/wordpress-reviews/ReviewsController.php',
        'a61bc28a742b9f9f2fd5ef4d2d1e2037' => __DIR__ . '/..' . '/publishpress/wordpress-version-notices/src/include.php',
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInitPublishPressStatuses::$classMap;

        }, null, ClassLoader::class);
    }
}
