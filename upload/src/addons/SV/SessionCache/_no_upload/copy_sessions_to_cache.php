<?php

$dir = __DIR__;
/** @noinspection PhpIncludeInspection */
require ($dir . '/src/XF.php');

XF::start($dir);
$app = XF::setupApp('XF\Pub\App');

$addOns = \XF::app()->container('addon.cache');
if (empty($addOns['SV/SessionCache']) || ($addOns['SV/SessionCache'] < 2000000))
{
    echo "Please install SessionCache add-on\n";

    return;
}

$db = \XF::db();
$session = \XF::app()->session();
$cache = \XF::app()->cache();
if (!$cache)
{
    echo "no app cache object\n";

    return;
}

class SessionCracker extends \XF\Session\Session
{
    /**
     * @param \XF\Session\Session $session
     * @return \XF\Session\StorageInterface
     */
    public static function getCacheObject(\XF\Session\Session $session)
    {
        return $session->storage;
    }
}


$sessionStorage = SessionCracker::getCacheObject($session);
if (!$sessionStorage || !($sessionStorage instanceof \SV\SessionCache\SessionStorage))
{
    echo "No session cache object to copy to\n";
    return;
}

$credis = false;
$config = \XF::app()->config();
if ($config->cache->enabled && $config->cache->cacheSessions)
{
    if ($cache instanceof \SV\RedisCache\Redis)
    {
        $credis = $cache->getCredis(false);
        echo "Found redis session cache to copy from\n";
    }
}

if ($credis)
{
    $sessions = array();
    $escaped = $pattern = $cache->getNamespacedId('session_');
    $escaped = str_replace('[', '\[', $escaped);
    $escaped = str_replace(']', '\]', $escaped);


    // indicate to the redis instance would like to process X items at a time.
    $count = 100;
    // prevent looping forever
    $loopGuard = 10000;
    // find indexes matching the pattern
    $cursor = null;
    do
    {
        $keys = $credis->scan($cursor, $escaped ."*", $count);
        $loopGuard--;
        if ($keys === false)
        {
            break;
        }
        foreach($keys as $key)
        {
           $session = array();
           $session['session_id'] = str_replace($pattern, '', $key);
           $session['session_data'] = $credis->hget($key,"d");
           $session['expiry_date'] = \XF::$time + $credis->ttl($key);
           $sessions[] = $session;
        }
    }
    while($loopGuard > 0 && !empty($cursor));

}
else
{
    $sessions = $db->fetchAll("
    select *
    from xf_session;
    ");
}

echo "Found ".count($sessions)." sessions to migrate\n";
$sessionCount = 0;
foreach($sessions as $session)
{
    if ($session['expiry_date'] > \XF::$time)
    {
        $sessionStorage->writeSession($session['session_id'], $session['session_data'], $session['expiry_date'], true);
        $sessionCount += 1;
   }
}
echo "Migrated {$sessionCount} sessions\n";


