# Session Cache

Allows the setup of a dedicated session cache distinct from the normal XF cache

Defines a "sessionCache" section in addition to of a "cache" section. Takes all the same options, except `$config['cache']['sessions']`


Note; 
- if no 'sessionCache' section is defined or it is disabled, falls back on existing cache/MySQL storage
- Xenforo Session handling does not interact with php sessions
- Does not require a particular caching solution
- Only affects public sessions. Installer/Admin sessions are hard coded in XenForo 2 to use MySQL storage

Provided scripts:

- For copying MySQL sessions to a separate cache: copy_sessions_to_cache.php
 - Edit the line:
  ```
  $dir = __DIR__ . '/html';
  ```
  To point to the webroot. 
 - Install add-on, configure sessionCache but disable.
 - Run migration script.
 - Configure sessionCache to be enabled
 - Run migration script again (it will only copy older sessions).