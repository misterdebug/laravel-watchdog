<?php

return [
    'formats' => [
        'datetime' => 'Y-m-d H:i:s',
        'decimal_separator' => '.',
        'thousands_separator' => ',',
    ],

    'nav' => [
        'stats' => 'Stats',
        'activity' => 'Activity',
        'security' => 'Security',
        'login_attempts' => 'Login attempts',
        'sent_emails' => 'Sent emails',
        'laravel_logs' => 'Laravel logs',
    ],

    'columns' => [
        'id' => 'ID',
        'user' => 'User',
        'route' => 'Route',
        'method' => 'Method',
        'url' => 'URL',
        'code' => 'Code',
        'duration' => 'Duration',
        'ip' => 'IP',
        'params' => 'Params',
        'date' => 'Date',
        'actions' => 'Actions',
        'email' => 'Email',
        'result' => 'Result',
        'user_agent' => 'User agent',
        'level' => 'Level',
        'timestamp' => 'Timestamp',
        'message' => 'Message',
        'from' => 'From',
        'recipients' => 'Recipients',
        'subject' => 'Subject',
        'content' => 'Content',
        'attachments' => 'Attachments',
    ],

    'actions' => [
        'view' => 'View',
        'view_content' => 'View content',
        'view_full_entry' => 'View full entry',
        'delete' => 'Delete',
        'confirm_delete_row' => 'Delete this row?',
        'confirm_delete_entry' => 'Delete this entry from the log file?',
        'show_images' => 'Show images',
        'hide_images' => 'Hide images',
        'html_preview' => 'HTML preview',
    ],

    'stats' => [
        'title' => 'Stats logs',
        'description' => 'Requests recorded by the <code>:middleware</code> middleware.',
    ],

    'activity' => [
        'title' => 'Activity logs',
        'description' => 'Authenticated requests recorded by the <code>:middleware</code> middleware.',
    ],

    'security' => [
        'title' => 'Security logs',
        'description' => '404 and 422 responses recorded by the package.',
    ],

    'login_attempts' => [
        'title' => 'Login attempts',
        'description' => 'Successful and failed logins recorded by the package.',
        'success' => 'Success',
        'failure' => 'Failure',
    ],

    'sent_emails' => [
        'title' => 'Sent emails',
        'description' => 'Emails captured by the package. The HTML preview is isolated in a sandboxed iframe.',
        'cc' => 'CC',
        'bcc' => 'BCC',
        'text' => 'Text',
        'headers' => 'Headers',
        'no_content' => 'No content recorded.',
        'unnamed_attachment' => 'Unnamed',
        'iframe_title' => 'Email HTML preview',
    ],

    'laravel_logs' => [
        'title' => 'Laravel logs',
        'description' => 'The <code>:files</code> files. Deletion removes a complete entry, including its stack trace.',
        'files' => 'laravel*.log',
        'no_files' => 'No <code>:files</code> file found in the configured directory.',
        'no_entries' => 'No entries in this file.',
        'unit_kb' => 'KB',
    ],

    'messages' => [
        'no_records' => 'No records.',
        'row_deleted' => 'Row deleted.',
        'entry_deleted' => 'Entry deleted.',
        'delete_failed' => 'Unable to delete this entry. Check that the log file exists and is writable.',
    ],

    'validation' => [
        'file_required' => 'The log file is required.',
        'entry_required' => 'The log entry is required.',
    ],
];
