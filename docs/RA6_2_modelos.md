# 6.2. Modelos de datos mediante ORM

**Referencia teórica:** [Modelos de datos mediante ORM](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/044_modelosORM.html) · [Laravel: Eloquent](https://laravel.com/docs/13.x/eloquent)

Este apartado es teoría: los modelos se crean de uno en uno en los ejercicios de cada entidad, a partir de [6.5](./RA6_5_clientes.md).

## Definición de un modelo

```bash
php artisan make:model Cliente
```

_Eloquent_ es el ORM de Laravel y sigue el patrón _Active Record_ que vimos en [5.8](./RA5_8_proyecto.md): cada objeto se guarda a sí mismo. Con poco más que una clase vacía ya se puede trabajar:

```php
$clientes = Cliente::all();
$cliente = Cliente::findOrFail($id);     // 404 si no existe
$cliente->telefono = '600000000';
$cliente->save();
$cliente->delete();
```

## Convenios

- **Tabla:** Laravel la deduce del nombre del modelo en plural **inglés**: `Trabajador` buscaría `trabajadors`. En este proyecto todos los modelos la indican: `protected $table = 'trabajadores';`.
- **Clave primaria:** `id`.
- **Fechas:** `created_at` y `updated_at`, que se rellenan solas.

## Asignación masiva

`Cliente::create($datos)` y `$cliente->update($datos)` solo rellenan los campos de `$fillable`. Así nadie puede colar un campo que no esperamos.

```php
protected $fillable = ['nombre', 'apellidos', 'nif', 'direccion', 'cod_postal', 'telefono'];
```

El orden de `$fillable` debe ser el del [contrato](./contrato-api.md#modelos), porque los tests lo comprueban.

## Conversiones y valores calculados

```php
protected $casts = ['fecha' => 'date:Y-m-d', 'importe' => 'float', 'mandante' => 'boolean'];
protected $attributes = ['iva' => 21];     // valor por defecto antes de guardar

public function getNombreCompletoAttribute(): string   // $cliente->nombre_completo
{
    return trim($this->nombre . ' ' . $this->apellidos);
}
```

## Eventos del modelo

Para rellenar algo automáticamente al crear un registro:

```php
protected static function booted(): void
{
    static::creating(function (Presupuesto $p) {
        $p->fecha ??= now()->toDateString();
    });
}
```

## Ejercicios

1. **Investiga.** Responde en tu _pull request_: en `php artisan tinker`, ejecuta `App\Models\User::first()` y `App\Models\User::toBase()->first()`. ¿Qué diferencia hay? ¿Qué patrón de 5.8 reconoces en cada una?

---

**Siguiente:** [6.3. Inicialización de la base de datos](./RA6_3_seeders.md)
