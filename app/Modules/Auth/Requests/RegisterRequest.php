<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Cualquier cliente puede solicitar el registro.
     * El control de duplicados lo hace la regla `unique` sobre users.email.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación del registro.
     *
     * `email` se valida como formato y se asegura unicidad contra la tabla users.
     * `password` exige mínimo 8 caracteres (alineado con políticas modernas).
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }
}