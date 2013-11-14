<?php
/**
 * Mailer bootstrap.
 */

use Mailer\Dispatch\Dispatcher;
use Mailer\Dispatch\Http\HttpRequest;

chdir(dirname(__DIR__));
require_once __DIR__ . '/../vendor/autoload.php';

$dispatcher = new Dispatcher();
$dispatcher->dispatch(HttpRequest::createFromGlobals());
