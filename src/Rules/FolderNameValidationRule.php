<?php

namespace Viviniko\Media\Rules;

use Illuminate\Contracts\Validation\Rule;

class FolderNameValidationRule implements Rule
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
        if (str_contains($value, ['/', "\\", '..', ':', '*', '?', '"', '|', '>', '<', '#', '%'])) {
            return false;
        }
        if (in_array($value, ['.', 'nul', 'aux', 'con'])) {
            return false;
        }
        if (preg_match('/^con[1-9]|lpt[1-9]$/', $value, $match) > 0) {
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