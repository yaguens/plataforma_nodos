<?php

namespace App\Http\Requests;

use App\Models\Parametro;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Reglas para revisar un parámetro (nombre legible, unidad, escala del sensor, estado)
class UpdateParametroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // sin usuarios todavía (se cambiará con el login)
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'unidad' => ['nullable', 'string', 'max:20'],
            'decimales' => ['required', 'integer', 'between:0,6'],
            'escala_min' => ['required', 'numeric'],
            'escala_max' => ['required', 'numeric', 'gt:escala_min'],
            'mostrar_medidor' => ['required', 'boolean'],
            'estado' => ['required', Rule::in(Parametro::ESTADOS)],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede tener más de 100 caracteres.',
            'unidad.max' => 'La unidad no puede tener más de 20 caracteres.',
            'decimales.between' => 'Los decimales deben estar entre 0 y 6.',
            'escala_min.required' => 'Indica el valor mínimo que puede medir el sensor.',
            'escala_max.required' => 'Indica el valor máximo que puede medir el sensor.',
            'escala_max.gt' => 'El máximo de la escala debe ser mayor que el mínimo.',
            'estado.in' => 'El estado no es válido.',
        ];
    }
}
