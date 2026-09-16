<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Bootstrap dos testes PHPUnit
 * ==========================================================
 * Carrega só as classes "puras" do app/Core que não dependem de
 * banco de dados (Auth, Url, Flash) — para testar a lógica delas
 * isoladamente, sem precisar de um MySQL rodando.
 *
 * Os testes de integração com banco de dados continuam em
 * tests/run_all.php (TestKit), que já existia antes desta fase.
 */

define('APP_ROOT', dirname(__DIR__, 2));

require_once APP_ROOT . '/app/Core/Auth.php';
require_once APP_ROOT . '/app/Core/Url.php';
require_once APP_ROOT . '/app/Core/Flash.php';
