<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use JobMetric\Media\FileManager;
use JobMetric\Media\Models\Media;
use RuntimeException;

/** Place a real local image in the file manager's normal storage tree. */
final class DemoMediaSeeder extends Seeder
{
    public const FOLDER = 'Jobic Demo';

    public const IMAGE = 'jobic-demo-brand';

    public function run(): void
    {
        $files = app(FileManager::class);
        $folder = Media::query()->where('type', 'c')->whereNull('parent_id')
            ->where('name', self::FOLDER)->first() ?? $files->createFolder(self::FOLDER, null);

        if (self::image($folder->id)) {
            return;
        }

        $source = public_path('images/brand/jobmetric.png');
        if (! is_file($source)) {
            throw new RuntimeException('The bundled JobMetric demo image is missing.');
        }

        $files->upload(new UploadedFile($source, self::IMAGE.'.png', 'image/png', null, true),
            (int) $folder->id, allowDuplicate: true);
    }

    public static function image(?int $folderId = null): ?Media
    {
        $folderId ??= Media::query()->where('type', 'c')->whereNull('parent_id')
            ->where('name', self::FOLDER)->value('id');

        return $folderId ? Media::query()->where('type', 'f')->where('parent_id', $folderId)
            ->where('name', self::IMAGE)->where('extension', 'png')->first() : null;
    }
}
