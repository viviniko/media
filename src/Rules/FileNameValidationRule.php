<?php

namespace Viviniko\Media\Rules;

use Illuminate\Contracts\Validation\Rule;

class FileNameValidationRule implements Rule
{

    /**
     * Determine if the validation rule passes.
     *
     * @param  string $attribute
     * @param  mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $value = trim($value);
        if (str_contains($value, ['/', "\\", ':', '*', '?', '"', '<', '>', '|'])) {
            return false;
        }
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return ':attribute is invalid.';
    }
}