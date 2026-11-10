<?php

namespace Tests\Feature;

use App\Models\Archivo;
use App\Models\Cliente;
use App\Models\Especialista;
use App\Models\Falta;
use App\Models\Gasto;
use App\Models\Incidencia;
use App\Models\Ingreso;
use App\Models\LineaParteTrabajo;
use App\Models\LineaPresupuesto;
use App\Models\Obra;
use App\Models\Observacion;
use App\Models\ParteEspecialista;
use App\Models\ParteProveedor;
use App\Models\ParteTrabajo;
use App\Models\Presupuesto;
use App\Models\Proveedor;
use App\Models\Secuencia;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** 6.2: los modelos, sus códigos y sus eventos. Aún no hay factories ni relaciones: se usa create(). */
class RA6_2_ModelosTest extends TestCase
{
    use RefreshDatabase;

    public static function modelos(): array
    {
        return [
            'Cliente' => [Cliente::class, 'clientes', ['nombre', 'apellidos', 'nif', 'direccion', 'cod_postal', 'telefono']],
            'Trabajador' => [Trabajador::class, 'trabajadores', ['tipo', 'nombre', 'apellido', 'telefono', 'coste_hora_estandar']],
            'Proveedor' => [Proveedor::class, 'proveedores', ['nombre', 'direccion', 'telefono', 'correo', 'tipo', 'referencia', 'documento', 'num_documento']],
            'Especialista' => [Especialista::class, 'especialistas', ['tipo', 'referencia', 'comunicacion_parte', 'nombre', 'notas', 'telefono', 'correo']],
            'Obra' => [Obra::class, 'obras', ['descripcion', 'cliente_id']],
            'Presupuesto' => [Presupuesto::class, 'presupuestos', ['fecha', 'cliente_id', 'obra_id', 'nombre_cliente', 'nif', 'direccion', 'telefono', 'iva', 'estado']],
            'LineaPresupuesto' => [LineaPresupuesto::class, 'lineas_presupuesto', ['presupuesto_id', 'descripcion', 'uds', 'precio']],
            'Gasto' => [Gasto::class, 'gastos', ['obra_id', 'presupuesto_id', 'direccion_obra', 'inicio_obra', 'fin_obra', 'solicitud_obra', 'mandante', 'gestion_licencia', 'pago_icio']],
            'Ingreso' => [Ingreso::class, 'ingresos', ['obra_id', 'cliente_id', 'nombre_obra', 'tipo', 'fecha', 'documento', 'num_documento', 'importe']],
            'ParteTrabajo' => [ParteTrabajo::class, 'partes_trabajo', ['obra_id', 'fecha', 'descripcion', 'user_id']],
            'LineaParteTrabajo' => [LineaParteTrabajo::class, 'lineas_parte_trabajo', ['parte_trabajo_id', 'trabajador_id', 'horas', 'coste_hora']],
            'ParteProveedor' => [ParteProveedor::class, 'partes_proveedor', ['obra_id', 'proveedor_id', 'fecha', 'descripcion', 'documento', 'importe', 'estado_pago', 'user_id']],
            'ParteEspecialista' => [ParteEspecialista::class, 'partes_especialista', ['obra_id', 'especialista_id', 'fecha', 'descripcion', 'documento', 'importe', 'estado_pago', 'user_id']],
            'Falta' => [Falta::class, 'faltas', ['trabajador_id', 'fecha_inicio', 'fecha_fin', 'tipo', 'motivo', 'user_id']],
            'Observacion' => [Observacion::class, 'observaciones', ['texto', 'user_id']],
            'Incidencia' => [Incidencia::class, 'incidencias', ['texto', 'user_id']],
            'Archivo' => [Archivo::class, 'archivos', ['nombre_archivo', 'ruta', 'tipo']],
            'Secuencia' => [Secuencia::class, 'secuencias', ['prefijo', 'siguiente']],
        ];
    }

    #[DataProvider('modelos')]
    public function test_tabla_y_campos_asignables(string $clase, string $tabla, array $fillable)
    {
        $modelo = new $clase();
        $this->assertSame($tabla, $modelo->getTable());
        $this->assertEquals($fillable, $modelo->getFillable(), "El \$fillable de {$clase} no coincide (también el orden)");
    }

    private function cliente(array $datos = []): Cliente
    {
        return Cliente::create($datos + ['nombre' => 'Juan', 'apellidos' => 'Pérez', 'nif' => '12345678Z', 'direccion' => 'C/ Sol 1', 'telefono' => '600000000']);
    }

    public function test_codigos_correlativos_por_prefijo()
    {
        $this->assertSame('CLI0001', $this->cliente()->codigo);
        $this->assertSame('CLI0002', $this->cliente(['nif' => null])->codigo);
        $this->assertSame('TRB0001', Trabajador::create(['nombre' => 'Luis', 'apellido' => 'Gil'])->codigo);
        $this->assertSame('PROV0001', Proveedor::create(['nombre' => 'Sur'])->codigo);
        $this->assertSame('ESP0001', Especialista::create(['nombre' => 'Marta'])->codigo);
        $this->assertSame('OBR0001', Obra::create(['descripcion' => 'Baño'])->codigo);
        $this->assertSame('GAS0001', Gasto::create(['obra_id' => Obra::firstOrFail()->id])->codigo);
        $this->assertSame('OBR0002', Secuencia::siguiente('OBR'));
    }

    public function test_el_codigo_no_se_puede_asignar_desde_fuera()
    {
        $this->assertSame('CLI0001', $this->cliente(['codigo' => 'X'])->codigo);
    }

    public function test_valores_por_defecto_y_conversiones()
    {
        $t = Trabajador::create(['nombre' => 'Luis', 'apellido' => 'Gil']);

        $this->assertEquals(20, (new Trabajador())->coste_hora_estandar, 'Valor por defecto en $attributes');
        $this->assertIsFloat($t->fresh()->coste_hora_estandar);
        $this->assertSame('Luis Gil', $t->nombre_completo);
        $this->assertSame('Juan Pérez', $this->cliente()->nombre_completo);
        $this->assertSame('Hostelería Sol', (new Cliente(['nombre' => 'Hostelería Sol']))->nombre_completo);
        $this->assertEqualsWithDelta(102, (new LineaPresupuesto(['uds' => 4, 'precio' => 25.5]))->total_linea, 0.001);
        $this->assertEqualsWithDelta(160, (new LineaParteTrabajo(['horas' => 8, 'coste_hora' => 20]))->coste_subtotal, 0.001);
        $this->assertSame(5, (new Falta(['fecha_inicio' => '2026-08-03', 'fecha_fin' => '2026-08-07']))->dias);
        $this->assertSame(1, (new Falta(['fecha_inicio' => '2026-08-03', 'fecha_fin' => '2026-08-03']))->dias);
    }

    public function test_el_presupuesto_copia_los_datos_del_cliente_y_ya_no_cambian()
    {
        $cliente = $this->cliente();
        $p = Presupuesto::create(['cliente_id' => $cliente->id]);

        $this->assertSame('PREP0001', $p->codigo);
        $this->assertSame('Juan Pérez', $p->nombre_cliente);
        $this->assertSame('12345678Z', $p->nif);
        $this->assertSame(now()->toDateString(), $p->fecha->toDateString());
        $this->assertEquals(21, $p->fresh()->iva);
        $this->assertSame('no_aprobado', $p->fresh()->estado);

        $cliente->update(['nombre' => 'Otro']);
        $this->assertSame('Juan Pérez', $p->fresh()->nombre_cliente);
    }

    public function test_la_linea_de_parte_congela_la_tarifa_del_trabajador()
    {
        $t = Trabajador::create(['nombre' => 'Luis', 'apellido' => 'Gil', 'coste_hora_estandar' => 20]);
        $parte = ParteTrabajo::create(['obra_id' => Obra::create([])->id, 'descripcion' => 'Demolición']);
        $linea = LineaParteTrabajo::create(['parte_trabajo_id' => $parte->id, 'trabajador_id' => $t->id, 'horas' => 8]);

        $t->update(['coste_hora_estandar' => 30]);

        $this->assertEquals(20, $linea->fresh()->coste_hora);
        $this->assertSame(now()->toDateString(), $parte->fresh()->fecha->toDateString());
    }

    public function test_el_ingreso_toma_cliente_y_descripcion_de_su_obra()
    {
        $obra = Obra::create(['descripcion' => 'Reforma de baño', 'cliente_id' => $this->cliente()->id]);
        $i = Ingreso::create(['obra_id' => $obra->id, 'importe' => 100]);

        $this->assertSame('ING0001', $i->codigo);
        $this->assertSame($obra->cliente_id, $i->cliente_id);
        $this->assertSame('Reforma de baño', $i->nombre_obra);
        $this->assertSame(now()->toDateString(), $i->fecha->toDateString());
    }
}
