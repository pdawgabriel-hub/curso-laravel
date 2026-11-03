<?php

namespace Tests\Feature;

use Tests\TestCase;

class RA5_2_ConfiguracionTest extends TestCase
{
    public function test_existe_el_fichero_de_configuracion_propio()
    {
        $this->assertFileExists(config_path('gestionobras.php'));
    }

    public function test_datos_de_la_empresa()
    {
        foreach (['nombre', 'cif', 'direccion', 'telefono', 'email'] as $clave) {
            $this->assertNotEmpty(config("gestionobras.empresa.$clave"), "Falta gestionobras.empresa.$clave");
        }
    }

    public function test_valores_por_defecto_del_negocio()
    {
        $this->assertEquals(21, config('gestionobras.iva_por_defecto'));
        $this->assertEquals(20, config('gestionobras.coste_hora_por_defecto'));
    }

    public function test_idioma_y_zona_horaria()
    {
        $this->assertSame('es', config('app.locale'));
        $this->assertSame('Europe/Madrid', config('app.timezone'));
        $this->assertSame('es_ES', config('app.faker_locale'));
    }
}
