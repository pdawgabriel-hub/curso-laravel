# 9.2. Correo electrónico

**Referencia teórica:** [Enviando correos electrónicos](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/13_email.html) · [Laravel: Mail](https://laravel.com/docs/13.x/mail)

Enviar un presupuesto por correo con su PDF adjunto, desde la API y desde el detalle del presupuesto.

## Conceptos clave

- Un **Mailable** (`php artisan make:mail PresupuestoEnviado`) reúne asunto (`envelope`), contenido (`content`) y adjuntos (`attachments`).
- `Attachment::fromData(fn () => $pdf, 'PREP0001.pdf')->withMime('application/pdf')` adjunta el PDF generado en memoria, sin guardarlo en disco.
- En desarrollo, `MAIL_MAILER=log` escribe los correos en `storage/logs/laravel.log`; en los tests, `Mail::fake()`.

## Ejercicios

1. Mailable `App\Mail\PresupuestoEnviado` con la propiedad pública `presupuesto`, asunto `Presupuesto PREP0001`, una vista `correo.presupuesto` que saluda al cliente por su nombre e indica el total, y el PDF de 9.1 adjunto.
2. Endpoint `POST /api/v1/presupuestos/{presupuesto}/enviar` con `{ email }` (obligatorio, email), autorizado con la Policy del presupuesto, que envía el correo y responde **202** con un `message`.
3. Botón *Enviar por correo* en el detalle del presupuesto, con un campo para el email.
4. Opcional: enviar el correo en segundo plano (`ShouldQueue` y `php artisan queue:work`).

## Comprueba tu solución automáticamente

```bash
cp /var/www/curso-laravel/docs/materiales/tests/bloque9/RA9_2_CorreoTest.php tests/Feature/
php artisan test --filter=RA9_2
```

Resultado esperado: `Tests: 4 passed`.

## Git

Rama `RA9_2`, commit `feat: envío del presupuesto por correo con el PDF adjunto`.

---

**Siguiente:** [9.3. Panel de análisis financiero](./RA9_3_dashboard.md)
