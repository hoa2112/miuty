<?php

namespace App\Console\Commands;

use App\Randoms;
use Illuminate\Console\Command;

class Random extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:random';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $randoms = new Randoms();
        $randoms->string = uniqid();
        $randoms->save();
        $this->info('demo:random Cummand Run successfully!');
    }
}
