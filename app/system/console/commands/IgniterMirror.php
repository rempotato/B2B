<?php

namespace System\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use StdClass;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Console command to implement a "public" folder - heavily borrowed from wintercms
 *
 * This command will create symbolic links to files and directories
 * that are commonly required to be publicly available.
 */
class IgniterMirror extends Command
{
    /**
     * The console command name.
     */
    protected $name = 'igniter:mirror';

    /**
     * The console command description.
     */
    protected $description = 'Generates a mirrored public folder using symbolic links.';

    protected $files = [
        '.htaccess',
        'index.php',
        'robots.txt',
    ];

    protected $directories = [
        'storage/app/public',
        'storage/temp/public',
    ];

    protected $wildcards = [
        'app/*/assets',
        'app/*/actions/*/assets',
        'app/*/dashboardwidgets/*/assets',
        'app/*/formwidgets/*/assets',
        'app/*/widgets/*/assets',

        'extensions/*/*/assets',
        'extensions/*/*/actions/*/assets',
        'extensions/*/*/dashboardwidgets/*/assets',
        'extensions/*/*/formwidgets/*/assets',
        'extensions/*/*/widgets/*/assets',

        'themes/*/assets',
    ];

    protected $destinationPath;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->getDestinationPath();

        $paths = new StdClass();
        $paths->files = $this->files;
        $paths->directories = $this->directories;
        $paths->wildcards = $this->wildcards;

        Event::fire('system.console.mirror.extendPaths', [$paths]);

        foreach ($paths->files as $file) {
            $this->mirrorFile($file);
        }

        foreach ($paths->directories as $directory) {
            $this->mirrorDirectory($directory);
        }

        foreach ($paths->wildcards as $wildcard) {
            $this->mirrorWildcard($wildcard);
        }

        $this->output->writeln('<info>Mirror complete!</info>');
    }

    protected function mirrorFile($file)
    {
        $this->output->writeln(sprintf('<info> - Mirroring: %s</info>', $file));

        $src = base_path().'/'.$file;

        $dest = $this->getDestinationPath().'/'.$file;

        if (!File::isFile($src) || File::isFile($dest)) {
            return FALSE;
        }

        $this->mirror($src, $dest);
    }

    protected function mirrorDirectory($directory)
    {
        $this->output->writeln(sprintf('<info> - Mirroring: %s</info>', $directory));

        $src = base_path().'/'.$directory;

        $dest = $this->getDestinationPath().'/'.$directory;

        if (!File::isDirectory($src) || File::isDirectory($dest)) {
            return FALSE;
        }

        if (!File::isDirectory(dirname($dest))) {
            File::makeDirectory(dirname($dest), 0755, TRUE);
        }

        $this->mirror($src, $dest);
    }

    protected function mirrorWildcard($wildcard)
    {
        if (strpos($wildcard, '*') === FALSE) {
            return $this->mirrorDirectory($wildcard);
        }

        [$start, $end] = explode('*', $wildcard, 2);

        $startDir = base_path().'/'.$start;

        if (!File::isDirectory($startDir)) {
            return FALSE;
        }

        foreach (File::directories($startDir) as $directory) {
            $this->mirrorWildcard($start.basename($directory).$end);
        }
    }

    protected function mirror($src, $dest)
    {
        if ($this->option('relative')) {
            $src = $this->getRelativePath($dest, $src);

            if (strpos($src, '../') === 0) {
                $src = rtrim(substr($src, 3), '/');
            }
        }

        symlink($src, $dest);
    }

    protected function getDestinationPath()
    {
        if ($this->destinationPath !== null) {
            return $this->destinationPath;
        }

        $destPath = $this->argument('destination');
        if (realpath($destPath) === FALSE) {
            $destPath = base_path().'/'.$destPath;
        }

        if (!File::isDirectory($destPath)) {
            File::makeDirectory($destPath, 0755, TRUE);
        }

        $destPath = realpath($destPath);

        $this->output->writeln(sprintf('<info>Destination: %s</info>', $destPath));

        return $this->destinationPath = $destPath;
    }

    protected function getRelativePath($from, $to)
    {
        $from = str_replace('\\', '/', $from);
        $to = str_replace('\\', '/', $to);

        $dir = explode('/', is_file($from) ? dirname($from) : rtrim($from, '/'));
        $file = explode('/', $to);

        while ($dir && $file && ($dir[0] == $file[0])) {
            array_shift($dir);
            array_shift($file);
        }

        return str_repeat('../', count($dir)).implode('/', $file);
    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments()
    {
        return [
            ['destination', InputArgument::REQUIRED, 'The destination path relative to the current directory. Eg: public/'],
        ];
    }

    /**
     * Get the console command options.
     */
    protected function getOptions()
    {
        return [
            ['relative', null, InputOption::VALUE_NONE, 'Create symlinks relative to the public directory.'],
        ];
    }
}
