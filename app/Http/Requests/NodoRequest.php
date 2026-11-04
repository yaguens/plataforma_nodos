<?php

namespace App\Http\Requests;

use App\Models\Nodo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Reglas para configurar un nodo (sitio, nombre, rol, desfase horario, activo)
class NodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // sin usuarios todavía (se cambiará con el login)
    }

    public function rules(): array
    {
        $nodo = $this->route('nodo');

        return [
            'nombre' => ['required', 'string', 'max:100'],
            'sitio_id' => ['nullable', 'integer', 'exists:sitios,id'],
            'rol' => ['required', Rule::in(array_keys(Nodo::ROLES))],
            // Solo si es esclavo: el maestro debe existir, tener rol "maestro" y no ser él mismo
            'maestro_id' => ['exclude_unless:rol,esclavo', 'required', 'integer',
                Rule::exists('nodos', 'id')->where('rol', 'maestro'), Rule::notIn([$nodo->id])],
            'desfase_horas' => ['required', 'numeric', 'between:-12,14'],
            'activo' => ['required', 'boolean'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede tener más de 100 caracteres.',
            'sitio_id.exists' => 'Ese sitio no existe.',
            'rol.in' => 'El rol no es válido.',
            'maestro_id.required' => 'Un nodo esclavo necesita un maestro.',
            'maestro_id.exists' => 'El maestro tiene que ser un nodo con rol «Maestro».',
            'maestro_id.not_in' => 'Un nodo no puede ser su propio maestro.',
            'desfase_horas.required' => 'Indica el desfase horario.',
            'desfase_horas.numeric' => 'El desfase debe ser un número de horas (por ejemplo −6).',
            'desfase_horas.between' => 'El desfase debe estar entre −12 y +14 horas.',
            'descripcion.max' => 'La descripción no puede tener más de 255 caracteres.',
        ];
    }
}