<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpEmailMessage extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $htmlContent,
    ) {
    }

    public function build(): self
    {
        return $this->subject($this->subjectLine)
            ->html($this->htmlContent);
    }
}
