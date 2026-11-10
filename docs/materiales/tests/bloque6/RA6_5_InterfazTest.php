<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * 6.5: layout y componentes. Se comprueba la estructura (marcas de accesibilidad
 * y elementos), no el aspecto: los colores y las clases se pueden cambiar.
 */
class RA6_5_InterfazTest extends TestCase
{
    use RefreshDatabase;

    /** Usuario con rol de jefe. La columna `rol` llega en 6.7: hasta entonces, el usuario no lleva rol. */
    private function usuario(array $datos = []): User
    {
        return User::factory()->create($datos + (Schema::hasColumn('users', 'rol') ? ['rol' => 'jefe'] : []));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs($this->usuario(['name' => 'Carla Usuaria']));
    }

    public function test_el_layout_tiene_menu_lateral_y_marca_la_pagina_actual()
    {
        $html = $this->get(route('clientes.index'))->assertOk()->getContent();

        $this->assertStringContainsString('<nav aria-label="Principal"', $html);
        $this->assertMatchesRegularExpression('#href="' . preg_quote(route('clientes.index'), '#') . '"[^>]*aria-current="page"#', $html, 'El enlace de la página actual lleva aria-current="page"');
        $this->assertSame(1, substr_count($html, 'aria-current="page"'), 'Solo una entrada del menú está activa');
        $this->assertStringContainsString(route('obras.index'), $html);
        $this->assertStringContainsString('Carla Usuaria', $html);
        $this->assertStringContainsString('<main', $html);
    }

    public function test_el_layout_muestra_los_mensajes_de_la_sesion()
    {
        $this->withSession(['mensaje' => 'Cliente guardado'])->get(route('clientes.index'))
            ->assertSee('role="status"', false)->assertSee('Cliente guardado');
        $this->withSession(['error' => 'No se puede borrar'])->get(route('clientes.index'))
            ->assertSee('role="alert"', false)->assertSee('No se puede borrar');
    }

    public function test_cabecera_y_botones()
    {
        $this->blade('<x-cabecera titulo="Clientes" subtitulo="20 registros"><x-boton href="/clientes/create">Nuevo</x-boton></x-cabecera>')
            ->assertSeeInOrder(['<h1', 'Clientes', '</h1>', '20 registros', '<a href="/clientes/create"', 'Nuevo'], false);
        $this->blade('<x-boton>Guardar</x-boton>')->assertSee('<button type="submit"', false);
        $this->blade('<x-boton variante="secundario" type="button">Volver</x-boton>')->assertSee('type="button"', false);
    }

    public function test_tabla_con_columnas_filas_y_mensaje_si_esta_vacia()
    {
        $this->blade('<x-tabla :columnas="[\'Código\', \'Nombre\']"></x-tabla>')
            ->assertSeeInOrder(['<th scope="col"', 'Código', '<th scope="col"', 'Nombre'], false)
            ->assertSee('No hay registros');

        $this->blade('<x-tabla :columnas="[\'Código\']" vacio="Sin clientes"></x-tabla>')->assertSee('Sin clientes');

        $this->blade('<x-tabla :columnas="[\'Código\']"><tr><td>CLI0001</td></tr></x-tabla>')
            ->assertSee('CLI0001')->assertDontSee('No hay registros');
    }

    public function test_campo_con_su_etiqueta_valor_y_error()
    {
        $this->withViewErrors([])->blade('<x-campo name="nombre" etiqueta="Nombre" valor="Ana" />')
            ->assertSee('<label for="nombre"', false)->assertSee('name="nombre"', false)->assertSee('value="Ana"', false)
            ->assertDontSee('aria-invalid', false);

        $this->withViewErrors(['nombre' => 'El nombre es obligatorio.'])->blade('<x-campo name="nombre" etiqueta="Nombre" />')
            ->assertSee('aria-invalid="true"', false)->assertSee('El nombre es obligatorio.');

        $this->withViewErrors(['lineas.0.horas' => 'Máximo 24.'])->blade('<x-campo name="lineas[0][horas]" etiqueta="Horas" type="number" />')
            ->assertSee('name="lineas[0][horas]"', false)->assertSee('aria-invalid="true"', false)->assertSee('Máximo 24.');
    }

    public function test_selector_area_y_casilla()
    {
        $this->withViewErrors([])->blade('<x-selector name="cliente_id" etiqueta="Cliente" :opciones="[1 => \'Ana\', 2 => \'Luis\']" valor="2" />')
            ->assertSee('<select', false)->assertSee('<option value="2" selected', false)->assertSee('— Selecciona —');
        $this->withViewErrors(['notas' => 'Demasiado largo.'])->blade('<x-area name="notas" etiqueta="Notas" valor="Texto" />')
            ->assertSee('<textarea', false)->assertSee('Texto')->assertSee('aria-invalid="true"', false);
        $this->withViewErrors([])->blade('<x-casilla name="mandante" etiqueta="Mandante" :marcado="true" />')
            ->assertSee('type="hidden" name="mandante" value="0"', false)->assertSee('checked', false);
    }

    public function test_formulario_con_token_metodo_y_botones()
    {
        $this->blade('<x-formulario action="/clientes/update/1" metodo="PUT" cancelar="/clientes" boton="Guardar cambios">x</x-formulario>')
            ->assertSee('action="/clientes/update/1"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee('href="/clientes"', false)
            ->assertSee('Guardar cambios');
        $this->blade('<x-formulario action="/x" :ficheros="true">x</x-formulario>')->assertSee('enctype="multipart/form-data"', false);
    }

    public function test_borrar_pide_confirmacion()
    {
        $this->blade('<x-borrar action="/clientes/destroy/1" />')
            ->assertSee('action="/clientes/destroy/1"', false)
            ->assertSee('name="_method" value="DELETE"', false)
            ->assertSee('return confirm(', false);
    }

    public function test_tarjetas_datos_insignias_e_indicadores()
    {
        $this->blade('<x-tarjeta titulo="Datos"><dl><x-dato etiqueta="NIF">12345678Z</x-dato><x-dato etiqueta="Teléfono"></x-dato></dl></x-tarjeta>')
            ->assertSeeInOrder(['<section', 'Datos', '<dt', 'NIF', '<dd', '12345678Z', 'Teléfono', '—'], false);
        $this->blade('<x-insignia color="verde">Pagado</x-insignia>')->assertSee('Pagado');
        $this->blade('<x-kpi etiqueta="Beneficio" tono="negativo">-170,00</x-kpi>')->assertSeeInOrder(['Beneficio', '-170,00']);
        $this->blade('<x-progreso :porcentaje="41.3" />')->assertSee('role="progressbar"', false)->assertSee('aria-valuenow="41.3"', false);
    }

    public function test_pestanas_con_enlaces_y_la_activa_marcada()
    {
        $html = (string) $this->blade('<x-pestanas :pestanas="[\'resumen\' => \'Resumen\', \'ingresos\' => \'Ingresos\']" activa="ingresos" />');

        $this->assertStringContainsString('<nav aria-label="Pestañas"', $html);
        $this->assertStringContainsString('pestana=resumen', $html);
        $this->assertMatchesRegularExpression('#pestana=ingresos"[^>]*aria-current="page"[^>]*>\s*Ingresos#', $html, 'La pestaña activa lleva aria-current="page"');
        $this->assertSame(1, substr_count($html, 'aria-current="page"'), 'Solo una pestaña está activa');
    }
}
