<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

#[Description('Reinstall the installed shadcn-vue components and re-apply the starter kit changes')]
#[Signature('shadcn:update')]
class UpdateShadcnComponentsCommand extends Command
{
    /**
     * The starter kit's own changes to the shadcn-vue components, applied after every reinstall.
     */
    public const string STARTER_KIT_PATCH = 'resources/js/components/ui/starter-kit.patch';

    public function handle(): int
    {
        $components = collect(File::directories(resource_path('js/components/ui')))
            ->map(fn (string $directory): string => basename($directory))
            ->sort()
            ->values()
            ->all();

        if ($components === []) {
            $this->components->warn('No shadcn-vue components are installed in [resources/js/components/ui].');

            return self::SUCCESS;
        }

        $this->components->info('Reinstalling '.count($components).' components: '.implode(', ', $components));

        $reinstall = Process::path(base_path())
            ->forever()
            ->run(
                ['npx', 'shadcn-vue', 'add', ...$components, '--overwrite', '--yes'],
                fn (string $type, string $output) => $this->output->write($output),
            );

        if ($reinstall->failed()) {
            $this->components->error('Reinstalling the components failed. The starter kit patch was not applied.');

            return self::FAILURE;
        }

        if (! File::exists(base_path(self::STARTER_KIT_PATCH))) {
            $this->components->info('Components reinstalled. There is no starter kit patch to apply.');

            return self::SUCCESS;
        }

        $patch = Process::path(base_path())->run(['git', 'apply', self::STARTER_KIT_PATCH]);

        if ($patch->failed()) {
            $this->components->error(
                'The starter kit patch no longer applies, so shadcn-vue has changed the same lines.'
                .' The components are reinstalled without it. Re-apply the changes in ['.self::STARTER_KIT_PATCH.'] by hand, then regenerate the patch.',
            );
            $this->output->write($patch->errorOutput());

            return self::FAILURE;
        }

        $this->components->info('Components reinstalled and the starter kit patch applied. Review the changes with [git diff].');

        return self::SUCCESS;
    }
}
