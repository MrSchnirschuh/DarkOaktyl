<?php

namespace DarkOak\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;

abstract class Job
{
    use Queueable;
    use InteractsWithQueue;
    use SerializesModels;
}

