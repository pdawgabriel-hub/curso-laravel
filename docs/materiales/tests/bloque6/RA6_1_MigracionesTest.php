<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** 6.1: todas las tablas existen con sus columnas. Aún no hay modelos: se usa DB::table(). */
class RA6_1_MigracionesTest extends TestCase
{
    use RefreshDatabase;

    public static function tablas(): array
    {
        return [
            'clientes' => ['clientes', ['id', 'codigo', 'nombre', 'apellidos', 'nif', 'direccion', 'cod_postal', 'telefono', 'created_at', 'updated_at']],
            'secuencias' => ['secuencias', ['id', 'prefijo', 'siguiente']],
            'trabajadores' => ['trabajadores', ['id', 'codigo', 'tipo', 'nombre', 'apellido', 'telefono', 'coste_hora_estandar']],
            'proveedores' => ['proveedores', ['id', 'codigo', 'nombre', 'direccion', 'telefono', 'correo', 'tipo', 'referencia', 'documento', 'num_documento']],
            'especialistas' => ['especialistas', ['id', 'codigo', 'tipo', 'referencia', 'comunicacion_parte', 'nombre', 'notas', 'telefono', 'correo']],
            'obras' => ['obras', ['id', 'codigo', 'descripcion', 'cliente_id']],
            'presupuestos' => ['presupuestos', ['id', 'codigo', 'fecha', 'cliente_id', 'obra_id', 'nombre_cliente', 'nif', 'direccion', 'telefono', 'iva', 'estado']],
            'lineas_presupuesto' => ['lineas_presupuesto', ['id', 'presupuesto_id', 'descripcion', 'uds', 'precio']],
            'presupuesto_proveedor' => ['presupuesto_proveedor', ['presupuesto_id', 'proveedor_id']],
            'especialista_presupuesto' => ['especialista_presupuesto', ['especialista_id', 'presupuesto_id']],
            'gastos' => ['gastos', ['id', 'codigo', 'obra_id', 'presupuesto_id', 'direccion_obra', 'inicio_obra', 'fin_obra', 'solicitud_obra', 'mandante', 'gestion_licencia', 'pago_icio']],
            'ingresos' => ['ingresos', ['id', 'codigo', 'obra_id', 'cliente_id', 'nombre_obra', 'tipo', 'fecha', 'documento', 'num_documento', 'importe']],
            'partes_trabajo' => ['partes_trabajo', ['id', 'obra_id', 'fecha', 'descripcion', 'user_id']],
            'lineas_parte_trabajo' => ['lineas_parte_trabajo', ['id', 'parte_trabajo_id', 'trabajador_id', 'horas', 'coste_hora']],
            'partes_proveedor' => ['partes_proveedor', ['id', 'obra_id', 'proveedor_id', 'fecha', 'descripcion', 'documento', 'importe', 'estado_pago', 'user_id']],
            'partes_especialista' => ['partes_especialista', ['id', 'obra_id', 'especialista_id', 'fecha', 'descripcion', 'documento', 'importe', 'estado_pago', 'user_id']],
            'faltas' => ['faltas', ['id', 'trabajador_id', 'fecha_inicio', 'fecha_fin', 'tipo', 'motivo', 'user_id']],
            'observaciones' => ['observaciones', ['id', 'observable_type', 'observable_id', 'texto', 'user_id']],
            'incidencias' => ['incidencias', ['id', 'incidentable_type', 'incidentable_id', 'texto', 'user_id']],
            'archivos' => ['archivos', ['id', 'archivable_type', 'archivable_id', 'nombre_archivo', 'ruta', 'tipo']],
        ];
    }

    #[DataProvider('tablas')]
    public function test_la_tabla_existe_con_sus_columnas(string $tabla, array $columnas)
    {
        $this->assertTrue(Schema::hasTable($tabla), "Falta la tabla {$tabla}");
        foreach ($columnas as $columna) {
            $this->assertTrue(Schema::hasColumn($tabla, $columna), "Falta la columna {$tabla}.{$columna}");
        }
    }

    public function test_las_fichas_de_proveedor_y_especialista_no_guardan_importes()
    {
        // Lo que se paga a un proveedor o a un especialista va en sus partes, no en su ficha
        foreach (['proveedores' => ['base', 'iva', 'total', 'importe'], 'especialistas' => ['importe', 'base', 'iva']] as $tabla => $columnas) {
            foreach ($columnas as $columna) {
                $this->assertFalse(Schema::hasColumn($tabla, $columna), "{$tabla}.{$columna} sobra: los importes van en partes_proveedor y partes_especialista");
            }
        }
    }

    private function cliente(): int
    {
        return DB::table('clientes')->insertGetId(['codigo' => 'CLI' . uniqid(), 'nombre' => 'Ana', 'direccion' => 'C/ Sol 1', 'telefono' => '600000000']);
    }

    private function obra(?int $cliente = null): int
    {
        return DB::table('obras')->insertGetId(['codigo' => 'OBR' . uniqid(), 'cliente_id' => $cliente]);
    }

    public function test_valores_por_defecto()
    {
        $t = DB::table('trabajadores')->insertGetId(['codigo' => 'TRB1', 'nombre' => 'Luis', 'apellido' => 'Gil']);
        $p = DB::table('presupuestos')->insertGetId(['codigo' => 'PREP1', 'fecha' => '2026-05-10', 'cliente_id' => $this->cliente(), 'nombre_cliente' => 'Ana']);
        $f = DB::table('faltas')->insertGetId(['trabajador_id' => $t, 'fecha_inicio' => '2026-05-10', 'fecha_fin' => '2026-05-10']);

        $this->assertEquals(20, DB::table('trabajadores')->find($t)->coste_hora_estandar);
        $this->assertEquals(21, DB::table('presupuestos')->find($p)->iva);
        $this->assertSame('no_aprobado', DB::table('presupuestos')->find($p)->estado);
        $this->assertSame('vacaciones', DB::table('faltas')->find($f)->tipo);
        $this->assertEquals(1, DB::table('secuencias')->find(DB::table('secuencias')->insertGetId(['prefijo' => 'CLI']))->siguiente);
    }

    public function test_una_obra_tiene_una_sola_ficha_de_gastos()
    {
        $obra = $this->obra();
        DB::table('gastos')->insert(['codigo' => 'GAS1', 'obra_id' => $obra]);

        $this->expectException(QueryException::class);
        DB::table('gastos')->insert(['codigo' => 'GAS2', 'obra_id' => $obra]);
    }

    public function test_el_nif_es_unico_pero_puede_quedar_vacio()
    {
        DB::table('clientes')->insert(['codigo' => 'CLI1', 'nombre' => 'A', 'direccion' => 'B', 'telefono' => 'C']);
        DB::table('clientes')->insert(['codigo' => 'CLI2', 'nombre' => 'A', 'direccion' => 'B', 'telefono' => 'C']);
        DB::table('clientes')->insert(['codigo' => 'CLI3', 'nombre' => 'A', 'direccion' => 'B', 'telefono' => 'C', 'nif' => '11111111H']);

        $this->expectException(QueryException::class);
        DB::table('clientes')->insert(['codigo' => 'CLI4', 'nombre' => 'A', 'direccion' => 'B', 'telefono' => 'C', 'nif' => '11111111H']);
    }

    public function test_que_ocurre_al_borrar_lo_referenciado()
    {
        $cliente = $this->cliente();
        $obra = $this->obra($cliente);
        $gasto = DB::table('gastos')->insertGetId(['codigo' => 'GAS1', 'obra_id' => $obra]);
        $parte = DB::table('partes_trabajo')->insertGetId(['obra_id' => $obra, 'fecha' => '2026-05-10', 'descripcion' => 'Demolición']);

        DB::table('clientes')->delete($cliente);
        $this->assertNull(DB::table('obras')->find($obra)->cliente_id, 'obras.cliente_id: nullOnDelete');

        DB::table('obras')->delete($obra);
        $this->assertNull(DB::table('gastos')->find($gasto), 'gastos.obra_id: cascadeOnDelete');
        $this->assertNull(DB::table('partes_trabajo')->find($parte), 'partes_trabajo.obra_id: cascadeOnDelete');
    }

    public function test_no_se_borra_una_obra_con_ingresos()
    {
        $obra = $this->obra();
        DB::table('ingresos')->insert(['codigo' => 'ING1', 'obra_id' => $obra, 'fecha' => '2026-05-10', 'importe' => 100]);

        $this->expectException(QueryException::class);
        DB::table('obras')->delete($obra);
    }
}
