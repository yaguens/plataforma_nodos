<?php

namespace App\Http\Requests;

use App\Models\Sitio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Reglas de un sitio (crear y editar)
class SitioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // sin usuarios todavía (se cambiará con el login)
    }

    public function rules(): array
    {
        return [
            'proyecto_id' => ['required', 'integer', 'exists:proyectos,id'],
            'nombre' => ['required', 'string', 'max:100'],
            'tipo' => ['required', Rule::in(array_keys(Sitio::TIPOS))],
            'pais' => ['nullable', 'string', 'max:60'],
            'zona_horaria' => ['required', 'timezone:all'],
            'latitud' => ['nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'proyecto_id.required' => 'Elige el proyecto al que pertenece.',
            'proyecto_id.exists' => 'Ese proyecto no existe.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede tener más de 100 caracteres.',
            'tipo.in' => 'El tipo no es válido.',
            'pais.max' => 'El país no puede tener más de 60 caracteres.',
            'zona_horaria.timezone' => 'La zona horaria no es válida (por ejemplo: America/Guatemala).',
            'latitud.between' => 'La latitud debe estar entre −90 y 90.',
            'longitud.between' => 'La longitud debe estar entre −180 y 180.',
            '*.numeric' => 'Debe ser un número (usa punto para los decimales).',
        ];
    }
}