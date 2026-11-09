<?php

return [
    'login' => [
        'content' => "🔑 **New admin login**\nEmail: :email\nIP: :ip",
        'unknown_ip' => 'unknown',
    ],

    'error500' => [
        'content' => "🔴 **500 Error**\n`:message`\n:file::line\n:url",
    ],

    'suspicious_activity' => [
        'header' => '🚨 **Suspicious activity detected**',
        'by_email' => '**By email :**',
        'by_ip' => '**By IP :**',
        'line' => '• :identifier : :failures',
        'failure' => ':count failure|:count failures',
    ],

    'daily_security_report' => [
        'content' => "📊 **Daily security report**\n\n• 404 : :count404\n• 422 : :count422",
    ],
];
