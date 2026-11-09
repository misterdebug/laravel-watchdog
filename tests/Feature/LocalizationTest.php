<?php

declare(strict_types=1);

namespace MrDebug\LaravelWatchdog\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use InvalidArgumentException;
use MrDebug\LaravelWatchdog\LaravelWatchdogServiceProvider;
use MrDebug\LaravelWatchdog\Models\LoginAttempt;
use MrDebug\LaravelWatchdog\Models\StatsLog;
use MrDebug\LaravelWatchdog\Notifications\DailySecurityReportNotification;
use MrDebug\LaravelWatchdog\Notifications\Error500Notification;
use MrDebug\LaravelWatchdog\Notifications\LoginNotification;
use MrDebug\LaravelWatchdog\Notifications\SuspiciousActivityNotification;
use MrDebug\LaravelWatchdog\Support\ConfigValidator;
use MrDebug\LaravelWatchdog\Tests\TestCase;

final class LocalizationTest extends TestCase
{
    private string $logDirectory;

    protected function defineRoutes($router): void
    {
        parent::defineRoutes($router);

        $router->get('/login')->name('login');

        $router->middleware('web')->group(function () {
            require __DIR__.'/../../routes/watchdog-backoffice.php';
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->logDirectory = storage_path('framework/testing/watchdog-localization-'.Str::random(8));
        File::ensureDirectoryExists($this->logDirectory);
        config()->set('laravel-watchdog.backoffice.laravel_logs.directory', $this->logDirectory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->logDirectory);

        parent::tearDown();
    }

    public function test_english_and_french_translation_files_have_the_same_keys(): void
    {
        foreach (['backoffice', 'commands', 'notifications', 'validation'] as $file) {
            $english = $this->flatten(require __DIR__.'/../../lang/en/'.$file.'.php');
            $french = $this->flatten(require __DIR__.'/../../lang/fr/'.$file.'.php');

            $this->assertSame(
                $english,
                $french,
                "The {$file} translation files must expose the same keys."
            );
        }
    }

    public function test_the_backoffice_is_rendered_in_english_by_default(): void
    {
        $user = $this->createUser();

        $this->createStatsLogAt('2026-01-01 10:00:00');

        $this->actingAs($user)
            ->get(route('watchdog.stats-logs.index'))
            ->assertOk()
            ->assertSee('Stats logs')
            ->assertSee('2026-01-01 10:00:00')
            ->assertSee('Delete');
    }

    public function test_the_backoffice_is_rendered_in_french_with_french_dates(): void
    {
        $user = $this->createUser();

        $this->createStatsLogAt('2026-01-01 10:00:00');

        $loginAttempt = LoginAttempt::create([
            'email' => $user->email,
            'ip' => '127.0.0.1',
            'success' => true,
        ]);
        $loginAttempt->created_at = '2026-01-01 10:00:00';
        $loginAttempt->save();

        $this->app->setLocale('fr');

        $this->actingAs($user)
            ->get(route('watchdog.stats-logs.index'))
            ->assertOk()
            ->assertSee('Logs statistiques')
            ->assertSee('01/01/2026 10:00:00')
            ->assertSee('Supprimer');

        $this->actingAs($user)
            ->get(route('watchdog.login-attempts.index'))
            ->assertOk()
            ->assertSee('Tentatives de connexion')
            ->assertSee('Succès')
            ->assertSee('01/01/2026 10:00:00');
    }

    public function test_laravel_log_dates_are_formatted_for_the_active_locale(): void
    {
        $user = $this->createUser();

        File::put($this->logDirectory.'/laravel.log', '[2026-01-01 10:00:00] production.INFO: Hello from the log');

        $this->app->setLocale('fr');

        $this->actingAs($user)
            ->get(route('watchdog.laravel-logs.index', ['file' => 'laravel.log']))
            ->assertOk()
            ->assertSee('Logs Laravel')
            ->assertSee('01/01/2026 10:00:00');
    }

    public function test_flash_messages_are_translated(): void
    {
        $user = $this->createUser();
        $log = StatsLog::create([
            'method' => 'GET',
            'url' => 'https://example.test/stats',
            'response_code' => 200,
        ]);

        $this->app->setLocale('fr');

        $this->actingAs($user)
            ->delete(route('watchdog.stats-logs.destroy', $log->id))
            ->assertRedirect()
            ->assertSessionHas('status', 'Ligne supprimée.');
    }

    public function test_log_deletion_validation_messages_are_translated(): void
    {
        $user = $this->createUser();

        $this->app->setLocale('fr');

        $response = $this->actingAs($user)
            ->delete(route('watchdog.laravel-logs.destroy'), []);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['file', 'entry']);

        $errors = session('errors');

        $this->assertSame('Le fichier de log est obligatoire.', $errors->first('file'));
        $this->assertSame('L’entrée de log est obligatoire.', $errors->first('entry'));
    }

    public function test_commands_are_translated_and_keep_their_names(): void
    {
        $this->app->setLocale('fr');

        config()->set('laravel-watchdog.features.login_attempts', false);
        config()->set('laravel-watchdog.features.security_404', false);
        config()->set('laravel-watchdog.features.security_422', false);

        $this->assertSame(
            'Analyse les tentatives de connexion suspectes et alerte sur Discord',
            Artisan::all()['watchdog:analyze-logins']->getDescription()
        );

        $this->artisan('watchdog:analyze-logins')
            ->expectsOutput('La surveillance des tentatives de connexion est désactivée.')
            ->assertSuccessful();

        $this->artisan('watchdog:daily-security-report')
            ->expectsOutput('La surveillance de sécurité est désactivée.')
            ->assertSuccessful();
    }

    public function test_the_prune_command_output_is_translated(): void
    {
        $this->createOldStatsLog();

        $this->app->setLocale('fr');

        $this->artisan('watchdog:prune')
            ->expectsOutputToContain('StatsLog : 1 supprimé.')
            ->expectsOutputToContain('1 enregistrement Watchdog supprimé.')
            ->assertSuccessful();
    }

    public function test_discord_notifications_are_translated(): void
    {
        $this->app->setLocale('fr');

        $login = (new LoginNotification('10.0.0.1', 'admin@example.com'))->toDiscord()['content'];
        $this->assertSame(
            "🔑 **Nouvelle connexion administrateur**\nEmail : admin@example.com\nIP : 10.0.0.1",
            $login
        );

        $error = (new Error500Notification('Boom', '/app/File.php', 42))->toDiscord()['content'];
        $this->assertStringContainsString('🔴 **Erreur 500**', $error);
        $this->assertStringContainsString('`Boom`', $error);
        $this->assertStringContainsString('/app/File.php:42', $error);

        $report = (new DailySecurityReportNotification(3, 2))->toDiscord()['content'];
        $this->assertSame(
            "📊 **Rapport de sécurité quotidien**\n\n• 404 : 3\n• 422 : 2",
            $report
        );

        $suspicious = (new SuspiciousActivityNotification(
            collect([(object) ['email' => 'attacker@example.com', 'total' => 2]]),
            collect([(object) ['ip' => '10.0.0.66', 'total' => 1]]),
        ))->toDiscord()['content'];

        $this->assertStringContainsString('🚨 **Activité suspecte détectée**', $suspicious);
        $this->assertStringContainsString('**Par email :**', $suspicious);
        $this->assertStringContainsString('• attacker@example.com : 2 échecs', $suspicious);
        $this->assertStringContainsString('**Par IP :**', $suspicious);
        $this->assertStringContainsString('• 10.0.0.66 : 1 échec', $suspicious);
    }

    public function test_configuration_validation_messages_are_translated(): void
    {
        $this->app->setLocale('fr');

        config()->set('laravel-watchdog.features.stats', 'yes');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('[features.stats] doit être un booléen.');

        ConfigValidator::validate();
    }

    public function test_translations_can_be_resolved_per_locale(): void
    {
        $this->assertSame(
            'd/m/Y H:i:s',
            __('laravel-watchdog::backoffice.formats.datetime', [], 'fr')
        );

        $this->assertSame(
            'Y-m-d H:i:s',
            __('laravel-watchdog::backoffice.formats.datetime', [], 'en')
        );
    }

    public function test_the_language_files_have_a_publish_tag(): void
    {
        $paths = ServiceProvider::pathsToPublish(
            LaravelWatchdogServiceProvider::class,
            'laravel-watchdog-lang'
        );

        $this->assertNotEmpty($paths);

        $destination = str_replace('\\', '/', (string) reset($paths));

        $this->assertStringEndsWith('lang/vendor/laravel-watchdog', $destination);
        $this->assertStringEndsWith('/lang', str_replace('\\', '/', (string) array_key_first($paths)));
    }

    private function createStatsLogAt(string $createdAt): void
    {
        $record = new StatsLog([
            'method' => 'GET',
            'url' => 'https://example.test/stats',
            'response_code' => 200,
        ]);
        $record->created_at = $createdAt;
        $record->save();
    }

    private function createOldStatsLog(): void
    {
        $record = new StatsLog([
            'method' => 'GET',
            'url' => 'https://example.test/old',
            'response_code' => 200,
        ]);
        $record->created_at = now()->subDays(31);
        $record->save();
    }

    /**
     * @return array<int, string>
     */
    private function flatten(array $translations, string $prefix = ''): array
    {
        $keys = [];

        foreach ($translations as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                $keys = array_merge($keys, $this->flatten($value, $path));

                continue;
            }

            $keys[] = $path;
        }

        sort($keys);

        return $keys;
    }
}
