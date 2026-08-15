<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var \App\Models\Role $role */
        $role = $this->route('role');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ];
    }

    protected function prepareForValidation(): void
    {
        $permissions = $this->input('permissions', []);

        if (! is_array($permissions)) {
            $permissions = [];
        }

        $this->merge([
            'permissions' => array_values(array_unique(array_filter(
                $permissions,
                fn ($permission) => is_string($permission) && $permission !== '',
            ))),
        ]);
    }
}
