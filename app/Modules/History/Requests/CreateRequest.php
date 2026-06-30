<?php

namespace App\Modules\History\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del payload de creación de un registro de historial.
 *
 * Reglas:
 *   - user_id NO se acepta del cliente: se ignora aunque venga, porque
 *     el user_id se asigna server-side desde el JWT (el cliente no puede
 *     "postear" historial a nombre de otro usuario).
 *   - payload y result deben ser arrays (JSON estructurado), no strings
 *     sueltos, para impedir que se persista texto arbitrario donde se
 *     espera estructura.
 */
class CreateRequest extends FormRequest
{
    /**
     * La autorización la gestiona el middleware `auth:api` en la ruta.
     * Aquí retornamos true porque ya llegamos autenticados.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'module_name' => ['required', 'string', 'max:255'],
            'action' => ['required', 'string', 'max:255'],
            'payload' => ['required', 'array'],
            'result' => ['required', 'array'],
        ];
    }

    /**
     * Nunca propagar `user_id` desde el cuerpo: puede intentar
     * impersonar a otros usuarios. Se descarta explícitamente.
     */
    protected function prepareForValidation(): void
    {
        $this->request->remove('user_id');
    }
}