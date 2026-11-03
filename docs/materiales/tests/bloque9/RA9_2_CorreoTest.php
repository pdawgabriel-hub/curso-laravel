<?php

namespace Tests\Feature;

use App\Mail\PresupuestoEnviado;
use App\Models\LineaPresupuesto;
use App\Models\Presupuesto;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\FeatureTestCase;

class RA9_2_CorreoTest extends FeatureTestCase
{
    private Presupuesto $presupuesto;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->actuarComo('administrativa');
        $this->presupuesto = Presupuesto::factory()->create();
        LineaPresupuesto::factory()->create(['presupuesto_id' => $this->presupuesto->id]);
    }

    public function test_envia_el_presupuesto_por_correo()
    {
        $this->postJson("/api/v1/presupuestos/{$this->presupuesto->id}/enviar", ['email' => 'cliente@correo.test'])
            ->assertStatus(202)
            ->assertJsonStructure(['message']);

        Mail::assertSent(PresupuestoEnviado::class, fn ($mail) => $mail->hasTo('cliente@correo.test') && $mail->presupuesto->is($this->presupuesto));
    }

    public function test_el_correo_lleva_asunto_y_el_pdf_adjunto()
    {
        $mail = new PresupuestoEnviado($this->presupuesto);

        $mail->assertHasSubject("Presupuesto {$this->presupuesto->codigo}");
        $mail->assertSeeInHtml($this->presupuesto->nombre_cliente);
        $this->assertCount(1, $mail->attachments());
        $this->assertTrue($mail->hasAttachedData(fn () => true, "{$this->presupuesto->codigo}.pdf", ['mime' => 'application/pdf'])
            || $mail->attachments()[0]->as === "{$this->presupuesto->codigo}.pdf");
    }

    public function test_valida_el_correo_de_destino()
    {
        $this->postJson("/api/v1/presupuestos/{$this->presupuesto->id}/enviar", ['email' => 'no-es-un-correo'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
        Mail::assertNothingSent();
    }

    public function test_sin_rol_no_se_envia()
    {
        $this->actuarComo(null);

        $this->postJson("/api/v1/presupuestos/{$this->presupuesto->id}/enviar", ['email' => 'cliente@correo.test'])->assertForbidden();
        Mail::assertNothingSent();
    }
}
