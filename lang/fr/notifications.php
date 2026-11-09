<?php

return [
    'login' => [
        'content' => "🔑 **Nouvelle connexion administrateur**\nEmail : :email\nIP : :ip",
        'unknown_ip' => 'inconnue',
    ],

    'error500' => [
        'content' => "🔴 **Erreur 500**\n`:message`\n:file::line\n:url",
    ],

    'suspicious_activity' => [
        'header' => '🚨 **Activité suspecte détectée**',
        'by_email' => '**Par email :**',
        'by_ip' => '**Par IP :**',
        'line' => '• :identifier : :failures',
        'failure' => ':count échec|:count échecs',
    ],

    'daily_security_report' => [
        'content' => "📊 **Rapport de sécurité quotidien**\n\n• 404 : :count404\n• 422 : :count422",
    ],
];
