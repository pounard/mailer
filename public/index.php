<?php
/**
 * Mailer bootstrap.
 */

use Mailer\Core\Bootstrap;
use Mailer\Dispatch\Dispatcher;
use Mailer\Dispatch\Http\HttpRequest;

// Prepare minimal environement
chdir(dirname(__DIR__));
require_once __DIR__ . '/../vendor/autoload.php';

// This where magic will happen
$request = HttpRequest::createFromGlobals();
$dispatcher = new Dispatcher();

$config = require_once __DIR__ . '/../etc/config.php';
Bootstrap::bootstrap($dispatcher, $request, $config);
$dispatcher->dispatch($request);
