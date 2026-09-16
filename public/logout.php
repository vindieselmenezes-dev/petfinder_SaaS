<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

Auth::logout();

header('Location: login.php');
exit;
