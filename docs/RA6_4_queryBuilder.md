# 6.4. Constructor de consultas (_Query Builder_)

**Referencia teórica:** [Constructor de consultas](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/046_queryBuilder.html) · [Laravel: Queries](https://laravel.com/docs/13.x/queries)

Consultas sin pasar por los modelos. Se parecen al patrón DAO de [5.8](./RA5_8_proyecto.md): se piensa en tablas y filas.

```php
DB::table('ingresos')->where('obra_id', 3)->sum('importe');
DB::table('clientes')->orderBy('apellidos')->limit(10)->get();
DB::table('obras')
    ->join('clientes', 'clientes.id', '=', 'obras.cliente_id')
    ->select('obras.codigo', 'clientes.nombre')
    ->get();
DB::table('partes_proveedor')
    ->selectRaw('proveedor_id, SUM(importe) as total')
    ->groupBy('proveedor_id')
    ->having('total', '>', 1000)
    ->get();
```

También sirven en los modelos: `Cliente::where('nombre', 'Juan')->get()`. Para ver el SQL generado: `->toRawSql()`.

Las transacciones (`DB::transaction()`) se tratan en [6.12](./RA6_12_transacciones.md).

## Ejercicios

1. **Consultas de negocio.** Con los datos de demostración de [6.3](./RA6_3_seeders.md) (`php artisan migrate:fresh --seed`), resuelve en `php artisan tinker` estas consultas con `DB::table()`. Guárdalas, con el SQL que generan (`->toRawSql()`) y un ejemplo del resultado, en `docs/consultas.md` de tu repositorio:
    1. Obras con el nombre completo de su cliente, ordenadas por código.
    2. Total cobrado por obra, de mayor a menor.
    3. Obras sin ningún ingreso.
    4. Horas y coste de mano de obra por trabajador, los 5 con más horas.
    5. Gasto en proveedores por proveedor y año.
    6. Presupuestos aprobados por año, con su número.
    7. Obras cuyo gasto externo (proveedores más especialistas) supera 1 000 €, con `having`.
    8. Ingresos del mes en curso.
    9. Trabajadores con alguna `baja_medica` en el último año.

2. **Con los modelos.** Repite las consultas 3, 6 y 8 empezando por el modelo (`Ingreso::where(...)`). ¿Qué cambia en lo que devuelven?

3. **Investiga.** Responde en tu _pull request_: en la consulta 2, ¿qué diferencia hay entre `join` y `leftJoin`? ¿Cuál necesitas para que aparezcan también las obras sin cobros?

Este apartado no tiene test automático: lo que se entrega es `docs/consultas.md`. En [6.8](./RA6_8_relaciones.md) volverás a escribir algunas con relaciones.

## Git

Rama `RA6_4`, commit `docs: consultas de negocio`.

---

**Siguiente:** [6.5. Interfaz con Blade: layout y componentes](./RA6_5_interfaz.md)
