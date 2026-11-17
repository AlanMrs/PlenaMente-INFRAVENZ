<?php
/**
 * Configuracion local de correo (NO se versiona: esta en .gitignore).
 *
 * Copia este archivo como app/Config/Correo.local.php y coloca la clave del
 * buzon de salida. Ejemplo:
 *
 *   return [
 *       'remitente' => 'psicologia@dsoftin.net',
 *       'smtp' => [
 *           'host'    => 'smtp.hostinger.com',
 *           'puerto'  => 587,
 *           'cifrado' => 'tls',
 *           'usuario' => 'psicologia@dsoftin.net',
 *           'clave'   => 'CLAVE_DEL_BUZON'
 *       ]
 *   ];
 *
 * Este archivo no existe todavia: el sistema funciona con la configuracion base
 * (app/Config/Correo.php) y con la variable de entorno PLENAMENTE_SMTP_CLAVE.
 */
return [];
