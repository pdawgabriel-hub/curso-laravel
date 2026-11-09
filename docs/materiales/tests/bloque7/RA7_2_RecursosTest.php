<?php

namespace Tests\Feature;

use App\Http\Resources\ClienteResource;
use App\Models\Cliente;
use App\Models\Especialista;
use App\Models\Falta;
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
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;

/** 7.2: el esqueleto de la API y los API Resources de todas las entidades. Aún no hay controladores. */
class RA7_2_RecursosTest extends FeatureTestCase
{
    public static function recursos(): array
    {
        return [
            'Cliente' => [Cliente::class, ['id', 'codigo', 'nombre', 'apellidos', 'nombre_completo', 'nif', 'direccion', 'cod_postal', 'telefono', 'created_at', 'updated_at']],
            'Trabajador' => [Trabajador::class, ['id', 'codigo', 'tipo', 'nombre', 'apellido', 'nombre_completo', 'telefono', 'coste_hora_estandar', 'horas_totales', 'total_euros', 'created_at', 'updated_at']],
            'Falta' => [Falta::class, ['id', 'trabajador_id', 'fecha_inicio', 'fecha_fin', 'tipo', 'motivo', 'user_id', 'dias']],
            'Proveedor' => [Proveedor::class, ['id', 'codigo', 'nombre', 'direccion', 'telefono', 'correo', 'tipo', 'referencia', 'documento', 'num_documento', 'base', 'iva', 'total']],
            'Especialista' => [Especialista::class, ['id', 'codigo', 'tipo', 'referencia', 'comunicacion_parte', 'nombre', 'notas', 'telefono', 'correo', 'importe']],
            'Presupuesto' => [Presupuesto::class, ['id', 'codigo', 'fecha', 'cliente_id', 'obra_id', 'nombre_cliente', 'nif', 'direccion', 'telefono', 'iva', 'estado', 'anio', 'base_imponible', 'cuota_iva', 'total', 'proveedor_ids', 'especialista_ids', 'created_at', 'updated_at']],
            'LineaPresupuesto' => [LineaPresupuesto::class, ['id', 'presupuesto_id', 'descripcion', 'uds', 'precio', 'total_linea']],
            'Obra' => [Obra::class, ['id', 'codigo', 'descripcion', 'cliente_id', 'total', 'horas_totales', 'coste_moo', 'estado_pago', 'gastos_totales', 'beneficio', 'pendiente_cobro', 'margen_proyectado', 'porcentaje_cobrado', 'salud', 'gasto_id', 'created_at', 'updated_at']],
            'Ingreso' => [Ingreso::class, ['id', 'codigo', 'obra_id', 'cliente_id', 'nombre_obra', 'tipo', 'fecha', 'documento', 'num_documento', 'importe', 'anio', 'total_ingresos', 'debe']],
            'ParteTrabajo' => [ParteTrabajo::class, ['id', 'obra_id', 'fecha', 'descripcion', 'horas_totales', 'coste_total', 'lineas', 'user_id', 'created_at', 'updated_at']],
            'LineaParteTrabajo' => [LineaParteTrabajo::class, ['id', 'parte_trabajo_id', 'trabajador_id', 'horas', 'coste_hora', 'coste_subtotal', 'fecha', 'obra_id']],
            'ParteProveedor' => [ParteProveedor::class, ['id', 'obra_id', 'proveedor_id', 'fecha', 'descripcion', 'documento', 'importe', 'estado_pago', 'user_id', 'created_at', 'updated_at']],
            'ParteEspecialista' => [ParteEspecialista::class, ['id', 'obra_id', 'especialista_id', 'fecha', 'descripcion', 'documento', 'importe', 'estado_pago', 'user_id', 'created_at', 'updated_at']],
        ];
    }

    #[DataProvider('recursos')]
    public function test_el_resource_devuelve_los_campos_del_contrato(string $modelo, array $campos)
    {
        $clase = 'App\\Http\\Resources\\' . class_basename($modelo) . 'Resource';
        $this->assertTrue(class_exists($clase), "Falta {$clase}");

        $datos = (new $clase($modelo::factory()->create()->fresh()))->response()->getData(true)['data'];

        foreach ($campos as $campo) {
            $this->assertArrayHasKey($campo, $datos, "{$clase} no devuelve «{$campo}»");
        }
    }

    public function test_la_ficha_de_gastos_y_su_resource()
    {
        $datos = (new \App\Http\Resources\GastoResource(Obra::factory()->create()->gasto->fresh()))->response()->getData(true)['data'];

        foreach (['id', 'codigo', 'obra_id', 'presupuesto_id', 'direccion_obra', 'inicio_obra', 'fin_obra', 'mandante', 'horas_trabajadas', 'coste_moo', 'coste_hora', 'coste_proveedores', 'coste_especialistas', 'gastos', 'ingresos', 'beneficio_real', 'debe', 'estado'] as $campo) {
            $this->assertArrayHasKey($campo, $datos, "GastoResource no devuelve «{$campo}»");
        }
    }

    public function test_un_listado_lleva_data_links_y_meta()
    {
        Cliente::factory()->count(3)->create();

        $json = ClienteResource::collection(Cliente::paginate(2))->response()->getData(true);

        $this->assertCount(2, $json['data']);
        $this->assertArrayHasKey('links', $json);
        $this->assertSame(3, $json['meta']['total']);
    }

    public function test_el_esqueleto_de_la_api()
    {
        $this->getJson('/api/v1/user')->assertUnauthorized();

        $user = User::factory()->create(['rol' => 'jefe']);
        Sanctum::actingAs($user);
        $this->getJson('/api/v1/user')->assertOk()->assertJsonPath('email', $user->email);
        $this->assertTrue(method_exists($user, 'createToken'), 'User necesita el trait HasApiTokens');
    }
}
