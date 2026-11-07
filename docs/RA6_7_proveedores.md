# 6.7. Ejercicios: proveedores y especialistas

**Referencia teórica:** [Ejercicios de bases de datos de otras entidades](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/0471_BDFamiliaProfesional.html)

Dos entidades más, esta vez desde cero: no tienen controlador ni vistas de etapas anteriores, ni array de datos.

## Ejercicios

1. **Proveedores.** Crea todo lo necesario siguiendo [6.5](./RA6_5_clientes.md):
    - Migración `create_proveedores_table`, con `codigo` (único), `nombre`, `base` e `iva` obligatorios (`decimal(10,2)` y `decimal(5,2)`), y `direccion`, `telefono`, `correo`, `tipo`, `referencia`, `documento` y `num_documento` opcionales.
    - Modelo `Proveedor` con `PREFIJO = 'PROV'` y el _accessor_ `total` (`base × (1 + iva / 100)`, redondeado a 2 decimales).
    - `ProveedorFactory` y `ProveedoresTableSeeder`, con 20 proveedores de la _factory_.
    - `ProveedoresController`, `routes/proveedores.php`, `ProveedorRequest` y las vistas `index`, `show`, `create` y `edit`, con las mismas rutas que clientes.
    - Validación: `nombre` obligatorio, `correo` con formato de email, `base` ≥ 0, `iva` entre 0 y 100.

2. **Especialistas.** Lo mismo, con `PREFIJO = 'ESP'`. Campos: `nombre` obligatorio; `tipo`, `referencia`, `comunicacion_parte` (texto), `notas` (texto), `telefono`, `correo` e `importe` (`decimal(10,2)`, ≥ 0) opcionales. Semilla con 20 de la _factory_.

3. **Investiga.** Responde en tu _pull request_: el módulo de _Odoo_ llamaba `observaciones` al campo que aquí es `notas`. En [6.14](./RA6_14_bitacoras.md), `Especialista` tendrá además una relación `observaciones()`. Con un campo y una relación del mismo nombre, ¿qué devolvería `$especialista->observaciones`?

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque6/RA6_7_ProveedoresEspecialistasTest.php tests/Feature/
php artisan test --filter=RA6_7
```

Resultado esperado: `Tests: 6 passed`.

## Git

Rama `RA6_7`. Commits: `feat: proveedores`, `feat: especialistas`.

---

**Siguiente:** [6.8. Relaciones: obras y faltas](./RA6_8_relaciones.md)
