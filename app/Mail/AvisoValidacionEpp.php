<?php

namespace App\Mail;

use App\Models\ValidacionEpp;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AvisoValidacionEpp extends Mailable
{
    public function __construct(public ValidacionEpp $validacion, public bool $recordatorio) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->recordatorio ? 'Recordatorio: foto de EPP pendiente' : 'Nueva foto de EPP para revisar',
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'correos.validacion-epp');
    }
}
