<?php

namespace App\Console;

use Illuminate\Foundation\Console\ServeCommand;

class LocalServeCommand extends ServeCommand
{
    protected function serverCommand(): array
    {
        $command = parent::serverCommand();

        // PHP applies upload limits before Laravel receives the request.
        $maxUploadMb = max((int) config('grow-ads.upload.image_max_mb'), (int) config('grow-ads.upload.video_max_mb'));
        array_splice($command, 1, 0, ['-d', 'upload_max_filesize='.$maxUploadMb.'M', '-d', 'post_max_size='.($maxUploadMb + 10).'M']);

        return $command;
    }
}
