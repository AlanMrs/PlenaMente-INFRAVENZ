<?php
/**
 * Configuracion del envio de correo del sistema.
 *
 * Este archivo se versiona (no contiene secretos). La clave del buzon debe
 * lives en app/Config/Correo.local.php (ignorado por git) o en la variable de
 * entorno PLENAMENTE_SMTP_CLAVE.
 *
 * Reglas importantes en hosting compartido (Hostinger):
 *  - El remitente DEBE ser un buzon real del dominio (si no existe, el servidor
 *    descarta el mensaje aunque mail() devuelva true).
 *  - El envio por SMTP es lo recomendado: entrega la respuesta real del servidor
 *    y permite saber si el mensaje fue aceptado o rechazado.
 */
return [
    // Remitente visible en los correos (debe existir como buzon en el hosting).
    // El buzon pertenece al dominio de la cuenta (dsoftin.net); la aplicacion
    // puede vivir en un subdominio (plenamente.dsoftin.net) sin que eso afecte al envio.
    'remitente'       => 'psicologia@dsoftin.net',
    'nombre_remitente' => 'PlenaMente - Instituto de Psicologia INFRAVENZ',

    // Servidor SMTP del hosting
    'smtp' => [
        'host'     => 'smtp.hostinger.com',
        'puerto'   => 587,
        'cifrado'  => 'tls',      // tls (STARTTLS, 587) | ssl (465) | none
        'usuario'  => 'psicologia@dsoftin.net',
        'clave'    => '',         // se define en Correo.local.php o en la variable de entorno
        'timeout'  => 20
    ],

    // Si es true se intenta SMTP y, si falla, se usa mail() como alternativa
    'usar_smtp'  => true,
    'usar_mail'  => true
];
