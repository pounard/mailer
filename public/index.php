<?php
/**
 * Mailer bootstrap.
 */

use Mailer\Dispatch\Dispatcher;
use Mailer\Dispatch\Http\Request;

chdir(dirname(__DIR__));
require_once __DIR__ . '/../vendor/autoload.php';

$dispatcher = new Dispatcher();
$dispatcher->dispatch(Request::createFromGlobals());
