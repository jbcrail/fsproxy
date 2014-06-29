<?php

use League\Flysystem\Adapter\Ftp as FtpAdapter;
use League\Flysystem\Adapter\Local as LocalAdapter;

return array(
  'proxy.title' => 'fsproxy',
  'proxy.sites' => array(
    'local' => new LocalAdapter('.'),

    'remote' => new FtpAdapter(array(
      'host' => 'test.talia.net',
      'username' => 'anonymous',
      'password' => 'ignore@me.com',
      'port' => 21,
      'root' => '.',
      'passive' => true,
      'ssl' => false,
      'timeout' => 30,
    )),
  ),
);
