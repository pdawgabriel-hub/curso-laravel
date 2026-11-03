<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RA6_1_MigracionesTest extends TestCase
{
    use RefreshDatabase;

    public static function tablas(): array
    {
        return [
            'clientes' => ['clientes', ['id', 'codigo', 'nombre', 'apellidos', 'nif', 'direccion', 'cod_postal', 'telefono', 'created_at', 'updated_at']],
            'trabajadores' => ['trabajadores', ['id', 'codigo', 'tipo', 'nombre', 'apellido', 'telefono', 'coste_hora_estandar']],
            'proveedores' => ['proveedores', ['id', 'codigo', 'nombre', 'direccion', 'telefono', 'correo', 'tipo', 'referencia', 'documento', 'num_documento', 'base', 'iva']],
            'especialistas' => ['especialistas', ['id', 'codigo', 'tipo', 'referencia', 'comunicacion_parte', 'nombre', 'notas', 'telefono', 'correo', 'importe']],
            'obras' => ['obras', ['id', 'codigo', 'descripcion', 'cliente_id']],
            'presupuestos' => ['presupuestos', ['id', 'codigo', 'fecha', 'cliente_id', 'obra_id', 'nombre_cliente', 'nif', 'direccion', 'telefono', 'iva', 'estado']],
            'lineas_presupuesto' => ['lineas_presupuesto', ['id', 'presupuesto_id', 'descripcion', 'uds', 'precio']],
            'presupuesto_proveedor' => ['presupuesto_proveedor', ['presupuesto_id', 'proveedor_id']],
            'especialista_presupuesto' => ['especialista_presupuesto', ['especialista_id', 'presupuesto_id']],
            'gastos' => ['gastos', ['id', 'codigo', 'obra_id', 'presupuesto_id', 'direccion_obra', 'inicio_obra', 'fin_obra', 'solicitud_obra', 'mandante', 'gestion_licencia', 'pago_icio']],
            'ingresos' => ['ingresos', ['id', 'codigo', 'obra_id', 'cliente_id', 'nombre_obra', 'tipo', 'fecha', 'documento', 'num_documento', 'importe']],
            'partes_trabajo' => ['partes_trabajo', ['id', 'obra_id', 'fecha', 'descripcion']],
            'lineas_parte_trabajo' => ['lineas_parte_trabajo', ['id', 'parte_trabajo_id', 'trabajador_id', 'horas', 'coste_hora']],
            'partes_proveedor' => ['partes_proveedor', ['id', 'obra_id', 'proveedor_id', 'fecha', 'descripcion', 'documento', 'importe', 'estado_pago']],
            'partes_especialista' => ['partes_especialista', ['id', 'obra_id', 'especialista_id', 'fecha', 'descripcion', 'documento', 'importe', 'estado_pago']],
            'faltas' => ['faltas', ['id', 'trabajador_id', 'fecha_inicio', 'fecha_fin', 'tipo', 'motivo']],
            'observaciones' => ['observaciones', ['id', 'observable_type', 'observable_id', 'texto', 'user_id']],
            'incidencias' => ['incidencias', ['id', 'incidentable_type', 'incidentable_id', 'texto', 'user_id']],
            'archivos' => ['archivos', ['id', 'archivable_type', 'archivable_id', 'nombre_archivo', 'ruta', 'tipo']],
            'users' => ['users', ['id', 'name', 'email', 'rol']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('tablas')]
    public function test_la_tabla_tiene_sus_columnas(string $tabla, array $columnas)
    {
        $this->assertTrue(Schema::hasTable($tabla), "Falta la tabla $tabla");
        foreach ($columnas as $columna) {
            $this->assertTrue(Schema::hasColumn($tabla, $columna), "Falta la columna $tabla.$columna");
        }
    }

    public function test_los_valores_por_defecto()
    {
        $c = \DB::table('clientes')->insertGetId(['codigo' => 'CLI0001', 'nombre' => 'A', 'direccion' => 'B', 'telefono' => 'C']);
        $t = \DB::table('trabajadores')->insertGetId(['codigo' => 'TRB0001', 'nombre' => 'A', 'apellido' => 'B']);
        $p = \DB::table('presupuestos')->insertGetId(['codigo' => 'PREP0001', 'fecha' => '2026-01-01', 'cliente_id' => $c, 'nombre_cliente' => 'A']);

        $this->assertEquals(20, \DB::table('trabajadores')->find($t)->coste_hora_estandar);
        $this->assertEquals(21, \DB::table('presupuestos')->find($p)->iva);
        $this->assertSame('no_aprobado', \DB::table('presupuestos')->find($p)->estado);
    }

    public function test_codigos_unicos_y_una_ficha_de_gastos_por_obra()
    {
        $o = \DB::table('obras')->insertGetId(['codigo' => 'OBR0001']);
        \DB::table('gastos')->insert(['codigo' => 'GAS0001', 'obra_id' => $o]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        \DB::table('gastos')->insert(['codigo' => 'GAS0002', 'obra_id' => $o]);
    }

    public function test_un_ingreso_exige_obra()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        \DB::table('ingresos')->insert(['codigo' => 'ING0001', 'fecha' => '2026-01-01', 'importe' => 10]);
    }
}
