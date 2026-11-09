<?php

return [
    'configuration' => 'laravel-watchdog: configuration must be an array.',

    'message' => 'laravel-watchdog: [:key] :message',

    'must_be_array' => 'must be an array.',
    'must_be_boolean' => 'must be a boolean.',
    'must_be_https_url' => 'must be null, an empty string, or a valid https URL.',
    'must_be_instantiable' => 'must be an instantiable class.',
    'must_be_non_empty_string' => 'must be a non-empty string.',
    'must_be_non_empty_array' => 'must be a non-empty array.',
    'must_be_positive_integer' => 'must be a positive integer.',
    'must_contain_scalars' => 'must only contain scalar values.',
    'must_contain_strings' => 'must only contain strings.',

    'must_implement' => 'must be a class implementing :class.',
    'must_extend_notification' => 'must be a class extending :class and defining toDiscord().',
];
