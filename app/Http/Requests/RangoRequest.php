<?php

namespace App\Http\Requests;

use App\Models\Rango;
use App\Models\Sitio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

// Reglas de un rango (crear y editar). Un rango se aplica a un TIPO de sitio o a un SITIO concreto.
// Cada límite es opcional (vacío = sin límite por ese lado), pero debe haber al menos uno.
class RangoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // sin usuarios todavía (se cambiará con el login)
    }

    public function rules(): array
    {
        return [
            'aplica_a' => ['required', Rule::in(['tipo', 'sitio'])],
            'tipo_sitio' => ['exclude_unless:aplica_a,tipo', 'required', Rule::in(array_keys(Sitio::TIPOS))],
            'sitio_id' => ['exclude_unless:aplica_a,sitio', 'required', 'integer', 'exists:sitios,id'],
            'optimo_min' => ['nullable', 'numeric'],
            'optimo_max' => ['nullable', 'numeric'],
            'aceptable_min' => ['nullable', 'numeric'],
            'aceptable_max' => ['nullable', 'numeric'],
            'fuente' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_sitio.required' => 'Elige el tipo de sitio.',
            'sitio_id.required' => 'Elige el sitio.',
            'sitio_id.exists' => 'Ese sitio no existe.',
            '*.numeric' => 'Debe ser un número (usa punto para los decimales).',
            'fuente.max' => 'La fuente no puede tener más de 150 caracteres.',
        ];
    }

    // Comprobaciones que comparan varios campos entre sí (se ejecutan después de las reglas)
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;   // si ya hay errores básicos, no seguir
                }

                $num = fn (string $campo) => $this->filled($campo) ? (float) $this->input($campo) : null;
                $omin = $num('optimo_min');
                $omax = $num('optimo_max');
                $amin = $num('aceptable_min');
                $amax = $num('aceptable_max');

                if ($omin === null && $omax === null && $amin === null && $amax === null) {
                    $validator->errors()->add('optimo_min', 'Indica al menos un límite.');
                }
                if ($omin !== null && $omax !== null && $omin > $omax) {
                    $validator->errors()->add('optimo_max', 'El máximo óptimo no puede ser menor que el mínimo.');
                }
                if ($amin !== null && $amax !== null && $amin > $amax) {
                    $validator->errors()->add('aceptable_max', 'El máximo aceptable no puede ser menor que el mínimo.');
                }
                // El rango aceptable debe CONTENER al óptimo
                if ($amin !== null && $omin !== null && $amin > $omin) {
                    $validator->errors()->add('aceptable_min', 'El mínimo aceptable no puede ser mayor que el mínimo óptimo.');
                }
                if ($amax !== null && $omax !== null && $amax < $omax) {
                    $validator->errors()->add('aceptable_max', 'El máximo aceptable no puede ser menor que el máximo óptimo.');
                }

                // No puede haber dos rangos del mismo parámetro para el mismo tipo o el mismo sitio
                $parametroId = $this->route('parametro')?->id ?? $this->route('rango')->parametro_id;
                $actual = $this->route('rango')?->id;   // al editar, no compararse consigo mismo

                $repetido = Rango::where('parametro_id', $parametroId)
                    ->when($actual, fn ($q) => $q->where('id', '!=', $actual))
                    ->when($this->input('aplica_a') === 'sitio',
                        fn ($q) => $q->where('sitio_id', $this->input('sitio_id')),
                        fn ($q) => $q->whereNull('sitio_id')->where('tipo_sitio', $this->input('tipo_sitio')))
                    ->exists();

                if ($repetido) {
                    $campo = $this->input('aplica_a') === 'sitio' ? 'sitio_id' : 'tipo_sitio';
                    $validator->errors()->add($campo, 'Este parámetro ya tiene un rango para ese ' . ($campo === 'sitio_id' ? 'sitio' : 'tipo de sitio') . '. Edítalo en vez de crear otro.');
                }
            },
        ];
    }
}