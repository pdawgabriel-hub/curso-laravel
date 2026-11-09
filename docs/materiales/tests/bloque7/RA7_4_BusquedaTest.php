<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Especialista;
use App\Models\Ingreso;
use App\Models\Obra;
use App\Models\ParteProveedor;
use App\Models\ParteTrabajo;
use App\Models\Presupuesto;
use App\Models\Proveedor;
use App\Models\Trabajador;
use PHPUnit\Framework\Attributes\DataProvider;

/** 7.4: búsqueda, orden, paginación y filtros en todos los listados. */
class RA7_4_BusquedaTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComo('jefe');
    }

    public function test_busqueda_multipalabra_en_columnas_distintas()
    {
        Cliente::factory()->create(['nombre' => 'Juan', 'apellidos' => 'Pérez López']);
        Cliente::factory()->create(['nombre' => 'Juan', 'apellidos' => 'García']);
        Cliente::factory()->create(['nombre' => 'Ana', 'apellidos' => 'Pérez']);

        $r = $this->getJson('/api/v1/clientes?search=' . urlencode('Juan Pérez'));

        $this->assertCount(1, $r->json('data'));
        $this->assertSame('Pérez López', $r->json('data.0.apellidos'));
    }

    /** [URL, registro que debe encontrarse, texto buscado]: un campo buscable de cada recurso. */
    public static function buscables(): array
    {
        return array_map(fn ($r) => [$r], array_combine($r = ['clientes', 'trabajadores', 'proveedores', 'especialistas', 'presupuestos', 'obras', 'ingresos', 'partes-trabajo', 'partes-proveedor'], $r));
    }

    private function busqueda(string $nombre): array
    {
        $obra = Obra::factory()->create();
        return match ($nombre) {
            'clientes' => ['/api/v1/clientes', Cliente::factory()->create(['telefono' => '611223344']), '611223344'],
            'trabajadores' => ['/api/v1/trabajadores', Trabajador::factory()->create(['apellido' => 'Zubizarreta']), 'Zubizarreta'],
            'proveedores' => ['/api/v1/proveedores', Proveedor::factory()->create(['nombre' => 'Cerámicas Levante']), 'Levante'],
            'especialistas' => ['/api/v1/especialistas', Especialista::factory()->create(['tipo' => 'Fontanero']), 'Fontanero'],
            'presupuestos' => ['/api/v1/presupuestos', Presupuesto::factory()->create(['cliente_id' => Cliente::factory()->create(['nombre' => 'Hostelería', 'apellidos' => 'Sol'])->id]), 'Hostelería Sol'],
            'obras' => ['/api/v1/obras', Obra::factory()->create(['descripcion' => 'Reforma de buhardilla']), 'buhardilla'],
            'ingresos' => ["/api/v1/obras/{$obra->id}/ingresos", Ingreso::factory()->create(['obra_id' => $obra->id, 'tipo' => 'Pagaré']), 'Pagaré'],
            'partes-trabajo' => ["/api/v1/obras/{$obra->id}/partes-trabajo", ParteTrabajo::factory()->create(['obra_id' => $obra->id, 'descripcion' => 'Alicatado del baño']), 'Alicatado'],
            'partes-proveedor' => ["/api/v1/obras/{$obra->id}/partes-proveedor", ParteProveedor::factory()->create(['obra_id' => $obra->id, 'documento' => 'ALB-7731']), 'ALB-7731'],
        };
    }

    #[DataProvider('buscables')]
    public function test_cada_listado_busca_en_sus_campos(string $nombre)
    {
        [$url, $buscado, $texto] = $this->busqueda($nombre);
        get_class($buscado)::factory()->count(2)->create(str_contains($url, '/obras/') ? ['obra_id' => $buscado->obra_id] : []);

        $r = $this->getJson($url . '?search=' . urlencode($texto))->assertOk();

        $this->assertSame([$buscado->id], array_column($r->json('data'), 'id'), "La búsqueda de {$nombre} no encuentra solo el registro buscado");
    }

    public function test_ordena_por_un_campo_permitido_y_no_por_cualquiera()
    {
        Cliente::factory()->create(['nombre' => 'Beatriz']);
        Cliente::factory()->create(['nombre' => 'Andrés']);

        $this->assertSame('Andrés', $this->getJson('/api/v1/clientes?sort=nombre&order=asc')->json('data.0.nombre'));
        $this->assertSame('Beatriz', $this->getJson('/api/v1/clientes?sort=nombre&order=desc')->json('data.0.nombre'));
        $this->getJson('/api/v1/clientes?sort=password')->assertOk();
    }

    public function test_pagina_con_per_page_y_conserva_los_parametros()
    {
        Cliente::factory()->count(25)->create();

        $r = $this->getJson('/api/v1/clientes?per_page=10&sort=nombre');

        $this->assertPaginado($r, 10, 25);
        $this->assertStringContainsString('sort=nombre', $r->json('links.next'));
    }

    public function test_filtros_por_igualdad()
    {
        $c = Cliente::factory()->create();
        Presupuesto::factory()->create(['estado' => 'aprobado', 'cliente_id' => $c->id]);
        Presupuesto::factory()->count(2)->create(['estado' => 'no_aprobado']);
        $obra = Obra::factory()->create(['cliente_id' => $c->id]);
        Obra::factory()->create();

        $this->assertCount(1, $this->getJson('/api/v1/presupuestos?estado=aprobado')->json('data'));
        $this->assertCount(1, $this->getJson("/api/v1/presupuestos?cliente_id={$c->id}")->json('data'));
        $this->assertSame([$obra->id], array_column($this->getJson("/api/v1/obras?cliente_id={$c->id}")->json('data'), 'id'));
    }
}
