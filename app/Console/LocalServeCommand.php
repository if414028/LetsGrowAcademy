<?php

namespace App\Console;

use Illuminate\Foundation\Console\ServeCommand;

class LocalServeCommand extends ServeCommand
{
    protected function serverCommand(): array
    {
        $command = parent::serverCommand();

        // PHP applies upload limits before Laravel receives the request.
        array_splice($command, 1, 0, ['-d', 'upload_max_filesize=30M', '-d', 'post_max_size=32M']);

        return $command;
    }
}
