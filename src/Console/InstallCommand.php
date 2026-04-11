<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'pushbox:install';

    protected $description = 'Create routes/pushbox.php and publish optional config/views';

    public function handle(): int
    {
        $target = base_path('routes/pushbox.php');

        if (File::exists($target)) {
            $this->warn('routes/pushbox.php already exists — skipping.');
        } else {
            File::ensureDirectoryExists(dirname($target));
            File::copy(__DIR__.'/../../stubs/routes/pushbox.stub', $target);
            $this->info('Created routes/pushbox.php');
        }

        if ($this->confirm('Publish the Pushbox config file?', false)) {
            $this->callSilent('vendor:publish', ['--tag' => 'pushbox-config']);
            $this->info('Published config/pushbox.php');
        }

        if ($this->confirm('Publish Pushbox Blade views?', false)) {
            $this->callSilent('vendor:publish', ['--tag' => 'pushbox-views']);
            $this->info('Published resources/views/vendor/pushbox');
        }

        $this->line('Visit /'.config('pushbox.path', 'pushbox').' (when routes are enabled) to browse previews.');

        return self::SUCCESS;
    }
}
