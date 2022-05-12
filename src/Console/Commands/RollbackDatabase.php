<?php
 
namespace Winterpk\LaravelStagingSync\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use File;
use Log;
use DB;

class RollbackDatabase extends Command
{
    /**
     * Staging sync command signature.
     *
     * @var string
     */
    protected $signature = 'laravel-staging-sync:rollback';
 
    /**
     * Description
     *
     * @var string
     */
    protected $description = 'Rolls back the database to the most recent dumpFilePath';
 
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
        return 1;
    }
}
