<?php

declare(strict_types=1);

namespace MrDebug\LaravelWatchdog\Tests\Unit;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use MrDebug\LaravelWatchdog\Support\LaravelLogParser;
use MrDebug\LaravelWatchdog\Tests\TestCase;

final class LaravelLogParserTest extends TestCase
{
    private string $directory;

    private LaravelLogParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = storage_path('framework/testing/watchdog-logs-'.Str::random(8));
        File::ensureDirectoryExists($this->directory);
        $this->parser = new LaravelLogParser();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    public function test_it_lists_laravel_log_files_only(): void
    {
        File::put($this->directory.'/laravel.log', 'first');
        File::put($this->directory.'/laravel-2026-01-01.log', 'second');
        File::put($this->directory.'/other.log', 'ignored');

        $files = $this->parser->files($this->directory);

        $this->assertCount(2, $files);
        $this->assertEqualsCanonicalizing(
            ['laravel.log', 'laravel-2026-01-01.log'],
            array_column($files, 'name')
        );
    }

    public function test_it_parses_complete_entries_with_stack_traces(): void
    {
        $content = <<<'LOG'
[2026-01-01 10:00:00] production.ERROR: Something failed
#0 /app/Service.php(42): boom()
#1 {main}
[2026-01-01 10:01:00] production.INFO: Everything is fine
LOG;

        File::put($this->directory.'/laravel.log', $content);

        $entries = $this->parser->entries($this->directory, 'laravel.log');

        $this->assertCount(2, $entries);
        $this->assertSame('ERROR', $entries[0]['level']);
        $this->assertSame('production', $entries[0]['environment']);
        $this->assertStringContainsString('Something failed', $entries[0]['message']);
        $this->assertStringContainsString('#0 /app/Service.php', $entries[0]['raw']);
        $this->assertSame('INFO', $entries[1]['level']);
    }

    public function test_it_deletes_a_complete_entry_and_keeps_the_others(): void
    {
        $content = <<<'LOG'
[2026-01-01 10:00:00] production.ERROR: First
#0 trace of the first entry
[2026-01-01 10:01:00] production.INFO: Second
LOG;

        File::put($this->directory.'/laravel.log', $content);

        $entries = $this->parser->entries($this->directory, 'laravel.log');

        $this->assertTrue($this->parser->deleteEntry($this->directory, 'laravel.log', $entries[0]['id']));

        $remaining = File::get($this->directory.'/laravel.log');

        $this->assertStringNotContainsString('First', $remaining);
        $this->assertStringNotContainsString('#0 trace', $remaining);
        $this->assertStringContainsString('Second', $remaining);
    }

    public function test_it_refuses_path_traversal_and_unknown_files(): void
    {
        File::put($this->directory.'/laravel.log', '[2026-01-01 10:00:00] production.INFO: Hello');

        $this->assertSame([], $this->parser->entries($this->directory, '../laravel.log'));
        $this->assertFalse($this->parser->deleteEntry($this->directory, '../laravel.log', 'anything'));
        $this->assertFalse($this->parser->deleteEntry($this->directory, 'other.log', 'anything'));
    }
}
