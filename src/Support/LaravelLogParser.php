<?php

namespace MrDebug\LaravelWatchdog\Support;

use Throwable;

/**
 * Reads Laravel log files and splits them into complete entries.
 *
 * A Laravel entry is not a single line: it starts with a timestamp and may
 * contain a stack trace on the following lines. Deleting a physical line would
 * corrupt the entry, so the backoffice only ever removes a whole entry.
 */
class LaravelLogParser
{
    /**
     * List the Laravel log files of a directory, newest first.
     *
     * @return array<int, array{name: string, size: int, modified_at: int|null}>
     */
    public function files(string $directory): array
    {
        $pattern = rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'laravel*.log';
        $paths = glob($pattern) ?: [];
        $files = [];

        foreach ($paths as $path) {
            $safePath = $this->safePath($directory, basename($path));

            if ($safePath === null || ! is_file($safePath)) {
                continue;
            }

            $files[] = [
                'name' => basename($safePath),
                'size' => (int) (filesize($safePath) ?: 0),
                'modified_at' => filemtime($safePath) ?: null,
            ];
        }

        usort($files, function (array $a, array $b): int {
            return [$b['modified_at'] ?? 0, $b['name']] <=> [$a['modified_at'] ?? 0, $a['name']];
        });

        return $files;
    }

    /**
     * Parse every entry of a log file, oldest first.
     *
     * @return array<int, array{
     *     id: string,
     *     timestamp: string|null,
     *     environment: string|null,
     *     level: string,
     *     message: string,
     *     raw: string
     * }>
     */
    public function entries(string $directory, string $file): array
    {
        $path = $this->safePath($directory, $file);

        if ($path === null) {
            return [];
        }

        $content = @file_get_contents($path);

        if ($content === false || $content === '') {
            return [];
        }

        return $this->parse($content);
    }

    /**
     * Remove one complete entry from a log file.
     *
     * The file is locked during the whole read/modify/write cycle so two
     * concurrent deletions cannot overwrite each other.
     */
    public function deleteEntry(string $directory, string $file, string $id): bool
    {
        $path = $this->safePath($directory, $file);

        if ($path === null || ! is_file($path) || ! is_writable($path)) {
            return false;
        }

        $handle = @fopen($path, 'c+');

        if ($handle === false) {
            return false;
        }

        try {
            if (! flock($handle, LOCK_EX)) {
                return false;
            }

            rewind($handle);
            $content = stream_get_contents($handle);

            if ($content === false) {
                return false;
            }

            $entries = $this->parse($content);
            $found = false;
            $remaining = [];

            foreach ($entries as $entry) {
                if (! $found && $entry['id'] === $id) {
                    $found = true;

                    continue;
                }

                $remaining[] = $entry;
            }

            if (! $found) {
                return false;
            }

            $newContent = $this->serialize($remaining);

            rewind($handle);
            ftruncate($handle, 0);
            $written = fwrite($handle, $newContent);
            fflush($handle);

            return $written !== false && $written === strlen($newContent);
        } catch (Throwable) {
            return false;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * @return array<int, array{
     *     id: string,
     *     timestamp: string|null,
     *     environment: string|null,
     *     level: string,
     *     message: string,
     *     raw: string
     * }>
     */
    protected function parse(string $content): array
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        $parts = preg_split(
            '/(?=^\[\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:[+-]\d{2}:\d{2})?\])/m',
            $content,
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        if ($parts === false) {
            return [];
        }

        $entries = [];
        $occurrences = [];

        foreach ($parts as $raw) {
            $raw = rtrim($raw, "\n");

            if (trim($raw) === '') {
                continue;
            }

            $hash = sha1($raw);
            $occurrence = $occurrences[$hash] ?? 0;
            $occurrences[$hash] = $occurrence + 1;

            $timestamp = null;
            $environment = null;
            $level = 'UNKNOWN';
            $message = $raw;

            if (preg_match('/^\[([^\]]+)\]\s+(.+?)\.([A-Z]+):\s?(.*)$/s', $raw, $matches)) {
                $timestamp = $matches[1];
                $environment = $matches[2];
                $level = strtoupper($matches[3]);
                $message = $matches[4];
            }

            $entries[] = [
                'id' => sha1($raw.'#'.$occurrence),
                'timestamp' => $timestamp,
                'environment' => $environment,
                'level' => $level,
                'message' => $message,
                'raw' => $raw,
            ];
        }

        return $entries;
    }

    /**
     * @param array<int, array{raw: string}> $entries
     */
    protected function serialize(array $entries): string
    {
        if ($entries === []) {
            return '';
        }

        return implode("\n", array_map(fn (array $entry): string => $entry['raw'], $entries))."\n";
    }

    protected function safePath(string $directory, string $file): ?string
    {
        if ($file !== basename($file) || ! preg_match('/^laravel.*\.log$/', $file)) {
            return null;
        }

        $directory = rtrim($directory, DIRECTORY_SEPARATOR);

        if (! is_dir($directory)) {
            return null;
        }

        $realDirectory = realpath($directory);
        $realPath = realpath($directory.DIRECTORY_SEPARATOR.$file);

        if ($realDirectory === false || $realPath === false) {
            return null;
        }

        if (dirname($realPath) !== $realDirectory) {
            return null;
        }

        return $realPath;
    }
}
