<?php

declare(strict_types=1);

namespace App\CustomFields\SocialNetworks;

use JobMetric\CustomField\Contracts\FieldContract;
use JobMetric\CustomField\Core\BaseCustomField;

/**
 * Render an ordered, repeatable collection of registered social profiles.
 */
class SocialNetworks extends BaseCustomField implements FieldContract
{
    /**
     * Return the stable custom-field type identifier.
     *
     * @return string
     */
    public static function type(): string
    {
        return 'social_networks';
    }
}
