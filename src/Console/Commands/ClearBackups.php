<?php

namespace Winterpk\LaravelStagingSync\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Log;

class ClearBackups extends Command
{
    /**
     * Staging sync command signature.
     *
     * @var string
     */
    protected $signature = 'laravel-staging-sync:clear';

    /**
     * Description
     *
     * @var string
     */
    protected $description = 'Clears out the dumpFilePath folder';

    protected $dumpFilePath;

    protected $currentDumpFile;

    protected $sourceDumpFile;

    public function __construct()
    {
        parent::__construct();
        $this->dumpFilePath = config('laravel-staging-sync.dumpFilePath');
    }

    /**
     * Handler
     *
     * @return mixed
     */
    public function handle()
    {
        $file = new Filesystem;
        $file->cleanDirectory($this->dumpFilePath);
        $this->info($this->dumpFilePath . ' path cleared');
        return 1;
    }
}
