<?php
declare(strict_types=1);
define('API_REQUEST',true);
require dirname(__DIR__).'/config/bootstrap.php';
(new App\Controllers\ApiController())->dispatch();
