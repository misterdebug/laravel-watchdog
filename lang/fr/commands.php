<?php

return [
    'analyze' => [
        'description' => 'Analyse les tentatives de connexion suspectes et alerte sur Discord',
        'disabled' => 'La surveillance des tentatives de connexion est désactivée.',
        'no_activity' => 'Aucune activité suspecte détectée.',
        'sent' => 'Alerte envoyée sur Discord.',
    ],

    'prune' => [
        'description' => 'Supprime les anciens enregistrements de Laravel Watchdog',
        'model_deleted' => ':model : :count supprimé.|:model : :count supprimés.',
        'total_deleted' => ':count enregistrement Watchdog supprimé.|:count enregistrements Watchdog supprimés.',
    ],

    'daily_security_report' => [
        'description' => 'Envoie un rapport quotidien des comptes 404 et 422 sur Discord',
        'disabled' => 'La surveillance de sécurité est désactivée.',
        'no_activity' => 'Aucune activité à signaler.',
        'sent' => 'Rapport envoyé sur Discord.',
    ],
];
