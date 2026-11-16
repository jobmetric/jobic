<?php

declare(strict_types=1);

namespace App\Support\Extensions;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use JobMetric\Extension\Support\InstalledExtensionsFile;

/**
 * Apply CMS activation policy before the package instantiates installed extensions.
 */
class InstalledFile extends InstalledExtensionsFile
{
    /**
     * Resolve the installed file using the package location unless explicitly isolated for testing.
     *
     * @return string
     */
    public function path(): string
    {
        return (string) config('extensions.installed_file', parent::path());
    }

    /**
     * Read installed identities for route ownership checks, including disabled owners.
     *
     * @return array<int, string>
     * @throws FileNotFoundException
     */
    public function allNamespaces(): array
    {
        return parent::read();
    }

    /**
     * Return only namespaces enabled by the CMS activation policy.
     *
     * @return array<int, string>
     * @throws FileNotFoundException
     */
    public function read(): array
    {
        return array_values(array_filter(parent::read(), fn (string $namespace): bool => app(Runtime::class)->active($namespace)));
    }
}
