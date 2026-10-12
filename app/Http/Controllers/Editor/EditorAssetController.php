<?php

declare(strict_types=1);

namespace App\Http\Controllers\Editor;

use App\Support\EditorDriverRegistry;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Serve explicitly registered public editor assets from trusted driver folders. */
final class EditorAssetController
{
    /**
     * Return one JavaScript or CSS asset without exposing arbitrary driver files.
     *
     * @param EditorDriverRegistry $registry
     * @param string $key
     * @param string $kind
     *
     * @return BinaryFileResponse
     */
    public function __invoke(EditorDriverRegistry $registry, string $key, string $kind): BinaryFileResponse
    {
        $file = $registry->asset($key, $kind);
        abort_unless($file && in_array($kind, [
                'module',
                'style',
            ], true), 404);

        return response()->file($file, [
            'Content-Type' => $kind === 'module' ? 'application/javascript' : 'text/css',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
