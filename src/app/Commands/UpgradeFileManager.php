<?php

namespace LaravelEnso\Core\app\Commands;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use LaravelEnso\HowToVideos\app\Models\Video;
use LaravelEnso\AvatarManager\app\Models\Avatar;
use LaravelEnso\DataImport\app\Models\DataImport;
use LaravelEnso\DataImport\app\Models\ImportTemplate;
use LaravelEnso\DocumentsManager\app\Models\Document;

class UpgradeFileManager extends Command
{
    protected $signature = 'enso:filemanager:upgrade';

    protected $description = 'This command will upgrade avatars, documents, dataimports and howtovideos for the new filemanager';

    public function handle()
    {
        if (!Schema::hasColumn('avatars', 'saved_name')) {
            $this->info('The upgrade was already performed');

            return;
        }

        auth()->loginUsingId(User::first()->id);

        $this->info('The upgrade process has started');
        $this->upgrade();
        $this->info('The upgrade process was successful.');
    }

    private function upgrade()
    {
        \DB::transaction(function () {
            $this->info('Upgrading avatars');
            $this->upgradeAvatars();
            $this->info('Avatars were successfully upgraded');

            $this->info('Upgrading data imports');
            $this->upgradeDataImports();
            $this->info('Data imports were successfully upgraded');

            $this->info('Upgrading documents');
            $this->upgradeDocuments();
            $this->info('Documents were successfully upgraded');

            $this->info('Upgrading how-to videos');
            $this->upgradeHowToVideos();
            $this->info('HowToVideos were successfully upgraded');
        });
    }

    private function upgradeAvatars()
    {
        Avatar::get()->each(function ($avatar) {
            $avatar->file()->create([
                'original_name' => $avatar->original_name,
                'saved_name' => $avatar->saved_name,
                'size' => $this->size(
                    config('enso.config.paths.avatars').DIRECTORY_SEPARATOR.$avatar->saved_name
                ),
                'mime_type' => $this->mimeType(
                    config('enso.config.paths.avatars').DIRECTORY_SEPARATOR.$avatar->saved_name
                ),
            ]);
        });

        Schema::table('avatars', function (Blueprint $table) {
            $table->dropColumn(['saved_name', 'original_name']);
        });
    }

    private function upgradeDataImports()
    {
        DataImport::get()->each(function ($import) {
            $import->file()->create([
                'original_name' => $import->original_name,
                'saved_name' => $import->saved_name,
                'size' => $this->size(
                    config('enso.config.paths.imports').DIRECTORY_SEPARATOR.$import->saved_name
                ),
                'mime_type' => $this->mimeType(
                    config('enso.config.paths.imports').DIRECTORY_SEPARATOR.$import->saved_name
                ),
            ]);
        });

        Schema::table('data_imports', function (Blueprint $table) {
            $table->dropColumn(['saved_name', 'original_name']);

            if (Schema::hasColumn('data_imports', 'comment')) {
                $table->dropColumn('comment');
            }
        });

        ImportTemplate::get()->each(function ($template) {
            $template->file()->create([
                'original_name' => $template->original_name,
                'saved_name' => $template->saved_name,
                'size' => $this->size(
                    config('enso.config.paths.imports').DIRECTORY_SEPARATOR.$template->saved_name
                ),
                'mime_type' => $this->mimeType(
                    config('enso.config.paths.imports').DIRECTORY_SEPARATOR.$template->saved_name
                ),
            ]);
        });

        Schema::table('import_templates', function (Blueprint $table) {
            $table->dropColumn(['saved_name', 'original_name']);
        });
    }

    private function upgradeDocuments()
    {
        Document::get()->each(function ($document) {
            $document->file()->create([
                'original_name' => $document->original_name,
                'saved_name' => $document->saved_name,
                'size' => $document->size,
                'mime_type' => $this->mimeType(
                    config('enso.config.paths.files').DIRECTORY_SEPARATOR.$document->saved_name
                ),
            ]);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['saved_name', 'original_name', 'size']);
        });
    }

    private function upgradeHowToVideos()
    {
        Video::get()->each(function ($video) {
            $video->file()->create([
                'original_name' => $video->video_original_name,
                'saved_name' => $video->video_saved_name,
                'size' => $this->size(
                    config('enso.config.paths.howToVideos').DIRECTORY_SEPARATOR.$video->video_saved_name
                ),
                'mime_type' => $this->mimeType(
                    config('enso.config.paths.howToVideos').DIRECTORY_SEPARATOR.$video->video_saved_name
                ),
            ]);

            if ($video->poster_saved_name) {
                $poster = $video->poster()->create();

                $poster->file()->create([
                    'original_name' => $video->poster_original_name,
                    'saved_name' => $video->poster_saved_name,
                    'size' => $this->size(
                        config('enso.config.paths.howToVideos').DIRECTORY_SEPARATOR.$video->poster_saved_name
                    ),
                    'mime_type' => $this->mimeType(
                        config('enso.config.paths.howToVideos').DIRECTORY_SEPARATOR.$video->poster_saved_name
                    ),
                ]);
            }
        });

        Schema::table('how_to_videos', function (Blueprint $table) {
            $table->dropColumn(['video_saved_name', 'video_original_name', 'poster_saved_name', 'poster_original_name']);
        });
    }

    private function size($file)
    {
        return \Storage::has($file)
            ? \Storage::size($file)
            : 0;
    }

    private function mimeType($file)
    {
        return \Storage::has($file)
            ? \Storage::mimeType($file)
            : '';
    }
}
