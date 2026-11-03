<?php

/*
|--------------------------------------------------------------------------
| Configuración propia de la aplicación
|--------------------------------------------------------------------------
| Se lee con config('gestionobras.clave'). Los valores sensibles o que cambian
| entre entornos se leen del .env con un valor por defecto.
*/

return [

    'empresa' => [
        'nombre' => env('EMPRESA_NOMBRE', 'Reformas y Construcciones S.L.'),
        'cif' => env('EMPRESA_CIF', 'B00000000'),
        'direccion' => env('EMPRESA_DIRECCION', 'Calle de la Obra 1, 28001 Madrid'),
        'telefono' => env('EMPRESA_TELEFONO', '910000000'),
        'email' => env('EMPRESA_EMAIL', 'info@gestion-obras.test'),
    ],

    // IVA por defecto de los presupuestos (%)
    'iva_por_defecto' => 21,

    // Tarifa por defecto de un trabajador nuevo (€/h)
    'coste_hora_por_defecto' => 20,

];
