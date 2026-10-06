<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\HealthManagerAssignment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class TransferHealthPlanners extends Command
{
    protected $signature = 'users:transfer-health-planners
        {from : ID, email, atau kode DST HM/SM asal}
        {to : ID, email, atau kode DST HM tujuan}
        {--dry-run : Tampilkan HP yang akan dialihkan tanpa mengubah data}';

    protected $description = 'Alihkan seluruh HP Active/Inactive dari HM atau mantan HM ke HM tujuan, beserta seluruh NS';

    public function handle(): int
    {
        if (! Schema::hasColumn('users', 'health_manager_id')) {
            $this->error('Jalankan php artisan migrate --force terlebih dahulu untuk menambahkan penugasan HM.');
            return self::FAILURE;
        }
        $source = $this->resolveUser((string) $this->argument('from'));
        $destination = $this->resolveUser((string) $this->argument('to'));
        if (! $source || ! $destination) return self::FAILURE;

        try {
            $planners = HealthManagerAssignment::transferPlanners($source, $destination, (bool) $this->option('dry-run'));
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) $this->error($message);
            }
            return self::FAILURE;
        }

        $this->line('Dari: '.$source->name.' ('.$source->dst_code.', #'.$source->id.')');
        $this->line('Ke: '.$destination->name.' ('.$destination->dst_code.', #'.$destination->id.')');
        $this->table(['HP ID', 'Nama HP', 'DST', 'Status', 'HM sebelumnya'], $planners->map(fn ($hp) => [
            $hp->id, $hp->name, $hp->dst_code ?: '-', $hp->status, $hp->health_manager_id ?: 'Belum tersimpan',
        ])->all());
        if ($this->option('dry-run')) {
            $this->info('DRY-RUN: '.$planners->count().' HP akan dialihkan. Tidak ada data yang diubah.');
        } else {
            $this->info($planners->count().' HP berhasil dialihkan ke '.$destination->name.'.');
        }
        $this->line('Seluruh NS, termasuk SO lama dan HP Inactive, mengikuti HM tujuan. Referrer dan SO tetap.');

        return self::SUCCESS;
    }

    private function resolveUser(string $key): ?User
    {
        $dstCodes = [$key];
        if (preg_match('/^DST-?(\d+)$/i', $key, $matches)) {
            $dstCodes = ['DST'.$matches[1], 'DST-'.$matches[1]];
        }
        $matches = User::where(function ($query) use ($key, $dstCodes) {
            $query->where('email', $key)->orWhereIn('dst_code', $dstCodes);
            if (ctype_digit($key)) $query->orWhere('id', (int) $key);
        })->get();
        if ($matches->count() !== 1) {
            $this->error('Identitas '.$key.' harus cocok tepat satu user (ID, email, atau DST).');
            return null;
        }

        return $matches->first();
    }
}
