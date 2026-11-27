<?php

require_once __DIR__.'/../Nucleo/helpers.php';

/**
 * Entra al sistema del personal como el usuario master de seedNucleo() (tienda 1), con el login
 * real de S1 (guard admin, session('shop_cd') = 1). Lo usan las pruebas de pantallas de S4.
 */
function s4Login(array $staff = []): void
{
    seedNucleo($staff);
    loginAs('master', 'secreto1');
}
