<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Especialista;
use App\Models\Gasto;
use App\Models\Obra;
use App\Models\Proveedor;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RA6_15_SeedersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_crea_veinte_registros_de_cada_maestro()
    {
        $this->assertSame(20, Cliente::count());
        $this->assertSame(20, Trabajador::count());
        $this->assertSame(20, Proveedor::count());
        $this->assertSame(20, Especialista::count());
    }

    public function test_crea_veinte_obras_cada_una_con_su_ficha_de_gastos()
    {
        $this->assertSame(20, Obra::count());
        $this->assertSame(20, Gasto::count());
        $this->assertSame(0, Obra::doesntHave('gasto')->count());
    }

    public function test_cada_obra_tiene_un_presupuesto_aprobado_con_lineas_y_partes()
    {
        foreach (Obra::all() as $obra) {
            $aprobado = $obra->presupuestos()->where('estado', 'aprobado')->first();
            $this->assertNotNull($aprobado, "La obra {$obra->codigo} no tiene presupuesto aprobado");
            $this->assertGreaterThanOrEqual(3, $aprobado->lineas()->count());
            $this->assertGreaterThanOrEqual(2, $obra->partesTrabajo()->count());
            $this->assertGreaterThanOrEqual(1, $obra->partesProveedor()->count());
            $this->assertGreaterThanOrEqual(1, $obra->partesEspecialista()->count());
        }
    }

    public function test_los_ingresos_nunca_superan_el_total_de_la_obra()
    {
        foreach (Obra::with('presupuestos.lineas', 'ingresos')->get() as $obra) {
            $total = $obra->presupuestos->where('estado', 'aprobado')->sum(fn ($p) => round($p->lineas->sum(fn ($l) => $l->uds * $l->precio) * (1 + $p->iva / 100), 2));
            $this->assertLessThanOrEqual($total + 0.01, $obra->ingresos->sum('importe'), "La obra {$obra->codigo} cobra más de su total");
        }
    }

    public function test_usuarios_de_cada_rol()
    {
        $this->assertTrue(User::where('email', config('app.admin.email'))->exists());
        $this->assertTrue(User::where('rol', 'jefe')->exists());
        $this->assertTrue(User::where('rol', 'administrativa')->exists());
    }
}
