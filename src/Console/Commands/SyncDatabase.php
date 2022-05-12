<?php
 
namespace Winterpk\LaravelStagingSync\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use File;
use Storage;
use Log;
use DB;
use Artisan;

class SyncDatabase extends Command
{
    /**
     * Staging sync command signature.
     *
     * @var string
     */
    protected $signature = 'laravel-staging-sync:sync
                            {db_name : Name of the database to sync with}
                            {db_user : Mysql admin user name}
                            {db_pass? : Mysql admin user password, only used if force option is true}
                            {db_host? : Optional database host, defaults to localhost}
                            {db_port? : Optional database port, defaults to 3306}
                            {--force : ignore the confirmation prompt and password prompt}';
 
    /**
     * Description
     *
     * @var string
     */
    protected $description = 'This is a destructive command will drop the current database and rebuilds 
                              it with the source database. STAGING_SYNC_ATCTIVE=true AND APP_ENV=staging 
                              for this command to work.';
 
    protected $dumpFilePath;

    protected $currentDumpFile;

    protected $sourceDumpFile;

    protected $currentDbCreds;

    public function __construct()
    {
        parent::__construct();
        $this->dumpFilePath = config('laravel-staging-sync.dumpFilePath');
        $this->currentDbCreds = [
            'db_name' => env('DB_DATABASE'),
            'db_user' => env('DB_USERNAME'),
            'db_pass' => env('DB_PASSWORD'),
            'db_host' => env('DB_HOST'),
            'db_port' => env('DB_PORT'),
        ];
    }

    private function buildMysqldumpString($params, $path)
    {
        $mysqldumpString = 'mysqldump --add-drop-table --skip-comments --user="${:USER}"';
        $processParams = [
            'USER' => $params['db_user'],
            'NAME' => $params['db_name'],
        ];
        if ($params['db_pass']) {
            $mysqldumpString .= ' --password="${:PASSWORD}"';
            $processParams['PASSWORD'] = $params['db_pass'];
        }
        if ($params['db_host']) {
            $mysqldumpString .= ' --host="${:HOST}"';
            $processParams['HOST'] = $params['db_host'];
        }
        if ($params['db_port']) {
            $mysqldumpString .= ' --port="${:PORT}"';
            $processParams['PORT'] = $params['db_port'];
        }
        $mysqldumpString .= ' "${:NAME}" > ' . $path;
        return [$mysqldumpString, $processParams];
    }

    /**
     * Dumps the source database
     *
     * @return bool
     */
    private function dumpSource($params)
    {
        $this->sourceDumpFile = $this->dumpFilePath . '/' . $params['db_name'] . '-source-' . time() . '.sql';
        $mysqldumpStringArr = $this->buildMysqldumpString($params, $this->sourceDumpFile);
        $process = Process::fromShellCommandline($mysqldumpStringArr[0]);
        $process->setTimeout(0);
        $process->run(null, $mysqldumpStringArr[1]);

        // Report errors
        if (! $process->isSuccessful()) {
            $this->error('An error occurred with mysqldump on the source database');
            $exception = new ProcessFailedException($process);
            $this->error($exception->getMessage());
            Log::error('Laravel Staging Sync: Source mysqldump error');
            Log::error($exception->getMessage());
            return false;
        } else {
            return true;
        }
    }

    /**
     * Dumps the current local database
     *
     * @return void
     */
    private function dumpCurrent()
    {
        $this->currentDumpFile = $this->dumpFilePath . '/' . env('DB_DATABASE') . '-current-' . time() . '.sql';

        $mysqldumpStringArr = $this->buildMysqldumpString($this->currentDbCreds, $this->currentDumpFile);
        $process = Process::fromShellCommandline($mysqldumpStringArr[0]);
        $process->setTimeout(0);
        $process->run(null, $mysqldumpStringArr[1]);

        // Report errors
        if (! $process->isSuccessful()) {
            $this->error('An error occurred with mysqldump on the current database');
            $exception = new ProcessFailedException($process);
            $this->error($exception->getMessage());
            Log::error('Laravel Staging Sync: Current mysqldump error');
            Log::error($exception->getMessage());
            return false;
        } else {
            return true;
        }
    }

    private function buildMysqlImportString($sqlDumpFile)
    {
        $mysqlImportString = 'mysql --user="${:USER}"';
        $processParams = [
            'USER' => $this->currentDbCreds['db_user'],
            'NAME' => $this->currentDbCreds['db_name'],
            'DUMPFILE' => $sqlDumpFile,
        ];
        if ($this->currentDbCreds['db_pass']) {
            $processParams['PASS'] = $this->currentDbCreds['db_pass'];
            $mysqlImportString .= ' --password="${:PASS}"';
        }
        if ($this->currentDbCreds['db_host']) {
            $processParams['HOST'] = $this->currentDbCreds['db_host'];
            $mysqlImportString .= ' --host="${:HOST}"';
        }
        if ($this->currentDbCreds['db_port']) {
            $processParams['PORT'] = $this->currentDbCreds['db_port'];
            $mysqlImportString .= ' --port="${:PORT}"';
        }
        $mysqlImportString .= ' "${:NAME}" < "${:DUMPFILE}"';
        return [$mysqlImportString, $processParams];
    }

    private function importSource()
    {
        Log::info('Importing source database');
        $mysqlImportStringArr = $this->buildMysqlImportString($this->sourceDumpFile);
        $process = Process::fromShellCommandline($mysqlImportStringArr[0]);
        $process->setTimeout(0);
        $process->run(null, $mysqlImportStringArr[1]);

        // Report errors
        if (! $process->isSuccessful()) {
            $this->error('An error occurred with importing the source database into the current database');
            $exception = new ProcessFailedException($process);
            $this->error($exception->getMessage());
            Log::error('Laravel Staging Sync: Current mysqldump error');
            Log::error($exception->getMessage());
            $this->info('rolling back ...');
            $mysqlImportStringArr = $this->buildMysqlImportString($this->currentDumpFile);
            $process = Process::fromShellCommandline($mysqlImportStringArr[0]);
            $process->setTimeout(0);
            $process->run(null, $mysqlImportStringArr[1]);
            if (! $process->isSuccessful()) {
                $exception = new ProcessFailedException($process);
                $this->error('Something went wrong and could not rollback');
                $this->error($exception->getMessage());
                Log::error('Laravel Staging Sync: Something went wrong attempting to rollback');
                Log::error($exception->getMessage());
                return false;
            } else {
                return false;
            }
        } else {
            $this->info('Success!');
            return true;
        }
        return true;
    }

    /**
     * Handler
     *
     * @return mixed
     */
    public function handle()
    {
        $isActive = config('laravel-staging-sync.active');
        if (! $isActive) {
            // abort
            $this->error('Laravel staging sync is not active');
            Log::info('Laravel Staging Sync: Inactive aborting.');
            return 0;
        }
        $appEnv = env('APP_ENV');
        if ($appEnv !== 'staging' && $appEnv !== 'local') {
            // abort
            $this->error('APP_ENV is not staging.');
            Log::info('Laravel Staging Sync: Not staging, aborting.');
            return 0;
        }
        if ($this->option('force') === false) {
            if ($this->confirm('Are you sure you wish to proceed?') === false) {
                // abort
                $this->error('Aborted by user');
                Log::info('Laravel Staging Sync: Aborted by user.');
                return 0;
            }
        }

        // Make sure the dumpFilePath exists
        if (! File::isDirectory($this->dumpFilePath)) {
            File::makeDirectory($this->dumpFilePath, 0775, true, true);
        }

        // Grab params and begin db ops
        $params = $this->arguments();

        // Ask for password if force is false
        if ($this->option('force') === false) {
            dd($this->secret('What is the database password?'));
            $params['db_pass'] = $this->secret('What is the database password?');
        }
        $this->line('Dumping source database ...');
        if (! $this->dumpSource($params)) {
            // abort
            return 0;
        }
        $this->line('Source dumpfile created at ' .  $this->sourceDumpFile);
        $this->info('Dumping current database ...');
        if (! $this->dumpCurrent()) {
            // abort
            return 0;
        }
        $this->line('Current dumpfile created at ' . $this->currentDumpFile);
        $this->info('Importing new database ...');
        if (! $this->importSource()) {
            // abort
            return 0;
        }
        $this->info('The staging database has been synced with production on ' . date('m-d-y H:i:s'));
        
        return 1;
    }
}
