<?php

return [
    'formats' => [
        'datetime' => 'd/m/Y H:i:s',
        'decimal_separator' => ',',
        'thousands_separator' => ' ',
    ],

    'nav' => [
        'stats' => 'Statistiques',
        'activity' => 'Activité',
        'security' => 'Sécurité',
        'login_attempts' => 'Tentatives de connexion',
        'sent_emails' => 'Emails envoyés',
        'laravel_logs' => 'Logs Laravel',
    ],

    'columns' => [
        'id' => 'ID',
        'user' => 'Utilisateur',
        'route' => 'Route',
        'method' => 'Méthode',
        'url' => 'URL',
        'code' => 'Code',
        'duration' => 'Durée',
        'ip' => 'IP',
        'params' => 'Paramètres',
        'date' => 'Date',
        'actions' => 'Actions',
        'email' => 'Email',
        'result' => 'Résultat',
        'user_agent' => 'Agent utilisateur',
        'level' => 'Niveau',
        'timestamp' => 'Horodatage',
        'message' => 'Message',
        'from' => 'Expéditeur',
        'recipients' => 'Destinataires',
        'subject' => 'Objet',
        'content' => 'Contenu',
        'attachments' => 'Pièces jointes',
    ],

    'actions' => [
        'view' => 'Voir',
        'view_content' => 'Voir le contenu',
        'view_full_entry' => 'Voir l’entrée complète',
        'delete' => 'Supprimer',
        'confirm_delete_row' => 'Supprimer cette ligne ?',
        'confirm_delete_entry' => 'Supprimer cette entrée du fichier de log ?',
        'show_images' => 'Afficher les images',
        'hide_images' => 'Masquer les images',
        'html_preview' => 'Aperçu HTML',
    ],

    'stats' => [
        'title' => 'Logs statistiques',
        'description' => 'Requêtes enregistrées par le middleware <code>:middleware</code>.',
    ],

    'activity' => [
        'title' => 'Logs d’activité',
        'description' => 'Requêtes authentifiées enregistrées par le middleware <code>:middleware</code>.',
    ],

    'security' => [
        'title' => 'Logs de sécurité',
        'description' => 'Réponses 404 et 422 enregistrées par le package.',
    ],

    'login_attempts' => [
        'title' => 'Tentatives de connexion',
        'description' => 'Connexions réussies et échouées enregistrées par le package.',
        'success' => 'Succès',
        'failure' => 'Échec',
    ],

    'sent_emails' => [
        'title' => 'Emails envoyés',
        'description' => 'Emails capturés par le package. L’aperçu HTML est isolé dans une iframe sandboxée.',
        'cc' => 'CC',
        'bcc' => 'BCC',
        'text' => 'Texte',
        'headers' => 'En-têtes',
        'no_content' => 'Aucun contenu enregistré.',
        'unnamed_attachment' => 'Sans nom',
        'iframe_title' => 'Aperçu HTML de l’email',
    ],

    'laravel_logs' => [
        'title' => 'Logs Laravel',
        'description' => 'Les fichiers <code>:files</code>. La suppression retire une entrée complète, y compris sa pile d’appels.',
        'files' => 'laravel*.log',
        'no_files' => 'Aucun fichier <code>:files</code> trouvé dans le répertoire configuré.',
        'no_entries' => 'Aucune entrée dans ce fichier.',
        'unit_kb' => 'Ko',
    ],

    'messages' => [
        'no_records' => 'Aucun enregistrement.',
        'row_deleted' => 'Ligne supprimée.',
        'entry_deleted' => 'Entrée supprimée.',
        'delete_failed' => 'Impossible de supprimer cette entrée. Vérifiez que le fichier de log existe et est accessible en écriture.',
    ],

    'validation' => [
        'file_required' => 'Le fichier de log est obligatoire.',
        'entry_required' => 'L’entrée de log est obligatoire.',
    ],
];
