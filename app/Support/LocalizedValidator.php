<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Validation\Validator;

/**
 * Describe invalid fields using form labels and the current language.
 */
final class LocalizedValidator extends Validator
{
    /**
     * Resolve an explicit label, a shared field label, or a neutral fallback.
     *
     * @param string $attribute
     *
     * @return string
     */
    public function getDisplayableAttribute($attribute): string
    {
        $primary = $this->getPrimaryAttribute($attribute);
        foreach (array_unique([$attribute, $primary]) as $name) {
            $label = $this->getAttributeFromLocalArray($name) ?: $this->getAttributeFromTranslations($name);
            if ($label) {
                return $label;
            }
        }

        if (isset($this->implicitAttributes[$primary]) && $this->implicitAttributesFormatter) {
            return ($this->implicitAttributesFormatter)($attribute);
        }

        $path = preg_replace('/(?:\.\d+)+$/', '', (string) $attribute);
        $field = str($path)->afterLast('.')->toString();

        return $this->getAttributeFromTranslations($field)
            ?? $this->translator->get('validation.field');
    }
}
