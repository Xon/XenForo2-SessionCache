<?php

namespace SV\SessionCache;

use Doctrine\Common\Cache\CacheProvider;
use XF\CacheFactory;
use XF\Session\CacheStorage;
use XF\Session\StorageInterface;

class SessionStorage extends CacheStorage implements StorageInterface
{
    /**
     * @param \XF\Container      $container
     * @param CacheProvider|null $cache
     * @param                    $storage
     */
    public static function publicSessionSetup(
        /** @noinspection PhpOptionalBeforeRequiredParametersInspection */
        \XF\Container $container, CacheProvider $cache = null, &$storage)
    {
        $config = $container['config'];
        if (empty($config['sessionCache']))
        {
            return;
        }

        $cacheConfig = array_merge([
            'enabled'   => false,
            'namespace' => 'xf',
            'provider'  => 'Void',
            'config'    => []
        ], $config['sessionCache']);

        if (!$cacheConfig['enabled'])
        {
            return;
        }

        /** @var CacheFactory $factory */
        $factory = $container['cache.factory'];
        $sessionCache = $factory->create($cacheConfig['provider'], $cacheConfig['config']);

        if ($sessionCache)
        {
            $class = \XF::app()->extendClass('SV\SessionCache\SessionStorage');
            /** @var SessionStorage $storage */
            $storage = new $class($sessionCache, 'session_');
        }
    }
}