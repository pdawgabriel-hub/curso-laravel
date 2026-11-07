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

Las transacciones (`DB::transaction()`) se tratan en [6.13](./RA6_13_transacciones.md). Las consultas de negocio se resuelven al final del bloque, en [6.15](./RA6_15_proyecto.md), cuando ya hay datos en todas las tablas.

---

**Siguiente:** [6.5. Ejercicios: clientes en la base de datos](./RA6_5_clientes.md)
