<?php

namespace App\Services;

use App\Models\RecruitmentLead;

class CrmWhatsappLink
{
    public function forRegistration(RecruitmentLead $lead): ?string
    {
        $number = $this->number();
        if ($number === null) {
            return null;
        }
        $message = match ($lead->event_payment_status) {
            'paid' => 'Halo, saya sudah daftar seminar.',
            'failed' => 'Halo, pembayaran saya gagal.',
            'refunded' => 'Halo, saya ingin bertanya tentang pengembalian pembayaran seminar.',
            default => 'Halo, saya ingin melanjutkan pembayaran.',
        };

        return 'https://wa.me/'.$number.'?text='.rawurlencode($message.' Kode: '.$lead->registrationId());
    }

    public function number(): ?string
    {
        $configured = trim((string) config('crm.whatsapp_number'));
        if (! preg_match('/^\+?[\d\s().-]+$/', $configured)) {
            return null;
        }
        $number = RecruitmentLead::normalizePhoneNumber($configured);
        if (! preg_match('/^62[1-9][0-9]{7,12}$/', $number)) {
            return null;
        }

        return $number;
    }
}
