<?php

namespace App\Http\Requests;

use App\Models\Proyecto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Reglas para crear un proyecto (y también para editarlo: ver UpdateProyectoRequest)
class StoreProyectoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // sin usuarios todavía: cualquiera puede (se cambiará con el login)
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'estado' => ['required', Rule::in(Proyecto::ESTADOS)],
        ];
    }

    // Mensajes en español (por defecto Laravel los da en inglés)
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede tener más de 100 caracteres.',
            'descripcion.max' => 'La descripción no puede tener más de 255 caracteres.',
            'estado.required' => 'Elige un estado.',
            'estado.in' => 'El estado debe ser uno de estos: ' . implode(', ', Proyecto::ESTADOS) . '.', // El estado debe ser uno de estos: Activo, Pausado, Finalizado."
        ];
    }
}