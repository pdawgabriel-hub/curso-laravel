<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Especialista;
use App\Models\Falta;
use App\Models\Gasto;
use App\Models\Ingreso;
use App\Models\LineaParteTrabajo;
use App\Models\LineaPresupuesto;
use App\Models\Obra;
use App\Models\ParteEspecialista;
use App\Models\ParteProveedor;
use App\Models\ParteTrabajo;
use App\Models\Presupuesto;
use App\Models\Proveedor;
use App\Models\Trabajador;
use Database\Seeders\ClientesTableSeeder;
use Database\Seeders\EspecialistasTableSeeder;
use Database\Seeders\FaltasTableSeeder;
use Database\Seeders\ProveedoresTableSeeder;
use Database\Seeders\TrabajadoresTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** 6.3: factories y semillas. Aún no hay relaciones: se consulta con where(). */
class RA6_3_SemillasTest extends TestCase
{
    use RefreshDatabase;

    public static function factories(): array
    {
        return array_map(fn ($c) => [$c], [
            'Cliente' => Cliente::class, 'Trabajador' => Trabajador::class, 'Proveedor' => Proveedor::class,
            'Especialista' => Especialista::class, 'Obra' => Obra::class, 'Presupuesto' => Presupuesto::class,
            'LineaPresupuesto' => LineaPresupuesto::class, 'Ingreso' => Ingreso::class, 'ParteTrabajo' => ParteTrabajo::class,
            'LineaParteTrabajo' => LineaParteTrabajo::class, 'ParteProveedor' => ParteProveedor::class,
            'ParteEspecialista' => ParteEspecialista::class, 'Falta' => Falta::class,
        ]);
    }

    #[DataProvider('factories')]
    public function test_la_factory_crea_un_registro_valido(string $clase)
    {
        $modelo = $clase::factory()->create();

        $this->assertModelExists($modelo);
    }

    public function test_las_factories_no_rellenan_lo_que_se_copia()
    {
        $p = Presupuesto::factory()->create();
        $cliente = Cliente::findOrFail($p->cliente_id);
        $this->assertSame($cliente->nombre_completo, $p->nombre_cliente, 'nombre_cliente se copia del cliente');

        $linea = LineaParteTrabajo::factory()->create();
        $this->assertEquals(Trabajador::findOrFail($linea->trabajador_id)->coste_hora_estandar, $linea->coste_hora, 'coste_hora se copia del trabajador');
    }

    public function test_toda_obra_de_la_factory_tiene_su_ficha_de_gastos()
    {
        $obras = Obra::factory()->count(3)->create();

        foreach ($obras as $obra) {
            $this->assertSame(1, Gasto::where('obra_id', $obra->id)->count());
        }
    }

    public function test_las_semillas_de_los_maestros()
    {
        $this->seed([ClientesTableSeeder::class, TrabajadoresTableSeeder::class, ProveedoresTableSeeder::class, EspecialistasTableSeeder::class]);

        $this->assertSame(20, Cliente::count());
        $this->assertSame(20, Trabajador::count());
        $this->assertSame(20, Proveedor::count());
        $this->assertSame(20, Especialista::count());
        $this->assertDatabaseHas('clientes', ['codigo' => 'CLI0020']);
    }

    public function test_la_semilla_de_faltas()
    {
        Trabajador::factory()->count(3)->create();

        $this->seed(FaltasTableSeeder::class);

        $this->assertLessThanOrEqual(12, Falta::count());
        $this->assertSame(0, Falta::whereColumn('fecha_fin', '<', 'fecha_inicio')->count());
    }

    public function test_los_datos_de_demostracion()
    {
        $this->seed();

        $this->assertSame(20, Obra::count());
        $this->assertSame(20, Gasto::count());

        foreach (Obra::all() as $obra) {
            $aprobado = Presupuesto::where('obra_id', $obra->id)->where('estado', 'aprobado')->first();
            $this->assertNotNull($aprobado, "La obra {$obra->codigo} no tiene presupuesto aprobado");
            $this->assertSame($obra->cliente_id, $aprobado->cliente_id, 'El presupuesto es del cliente de la obra');
            $this->assertGreaterThanOrEqual(3, LineaPresupuesto::where('presupuesto_id', $aprobado->id)->count());
            $this->assertGreaterThanOrEqual(2, ParteTrabajo::where('obra_id', $obra->id)->count());
            $this->assertGreaterThanOrEqual(1, ParteProveedor::where('obra_id', $obra->id)->count());
            $this->assertGreaterThanOrEqual(1, ParteEspecialista::where('obra_id', $obra->id)->count());

            $total = round(LineaPresupuesto::where('presupuesto_id', $aprobado->id)->get()->sum(fn ($l) => $l->uds * $l->precio) * (1 + $aprobado->iva / 100), 2);
            $this->assertLessThanOrEqual($total + 0.01, Ingreso::where('obra_id', $obra->id)->sum('importe'), "La obra {$obra->codigo} cobra más de su total");
        }
    }
}
