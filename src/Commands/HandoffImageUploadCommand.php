<?php

namespace Se09deluca\HandoffImageUpload\Commands;

use Illuminate\Console\Command;

class HandoffImageUploadCommand extends Command
{
    public $signature = 'handoff-image-upload';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
