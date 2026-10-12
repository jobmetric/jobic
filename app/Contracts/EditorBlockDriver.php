<?php
declare(strict_types=1);

namespace App\Contracts;

use App\Support\EditorDriverBuilder;

/** Configure one discoverable content tool through the shared builder. */
interface EditorBlockDriver
{
    /**
     * Describe this block's browser and server contract.
     *
     * @param EditorDriverBuilder $builder
     *
     * @return void
     */
    public function configure(EditorDriverBuilder $builder): void;
}
