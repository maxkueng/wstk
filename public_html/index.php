<?php

require_once('../config.php');
require_once($conf['we_dir'] .'/iobserver.php');
require_once($conf['we_dir'] .'/iobservable.php');
require_once($conf['we_dir'] .'/application.php');
require_once($conf['we_dir'] .'/util.php');
require_once($conf['we_dir'] .'/request.php');
require_once($conf['we_dir'] .'/abstractplugin.php');
require_once($conf['we_dir'] .'/page.php');
require_once($conf['we_dir'] .'/parser.php');
require_once($conf['we_dir'] .'/config.php');
require_once($conf['we_dir'] .'/data.php');
require_once($conf['we_dir'] .'/storage.php');
require_once($conf['lib_dir'] .'/spyc/spyc.php');

$app = WeApplication::instance($conf);
$app->start();

$app->output();
