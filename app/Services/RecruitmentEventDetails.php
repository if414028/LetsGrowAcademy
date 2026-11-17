<?php

namespace App\Services;

use App\Rules\Iso8601Timestamp;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class RecruitmentEventDetails
{
    public function snapshot(): array
    {
        $values = array_map(fn ($value) => is_string($value) ? (trim($value) ?: null) : $value, config('recruitment.event'));
        $validator = Validator::make($values, [
            'seminar_id' => ['nullable', 'string', 'max:80'],
            'seminar_name' => ['nullable', 'string', 'max:160'],
            'batch_id' => ['nullable', 'string', 'max:80'],
            'batch_starts_at' => ['nullable', new Iso8601Timestamp],
        ]);
        if ($validator->fails()) {
            throw new RuntimeException('Recruitment event configuration is invalid: '.implode(', ', $validator->errors()->keys()));
        }
        $values = $validator->validated();
        $values['batch_starts_at'] = isset($values['batch_starts_at'])
            ? Carbon::parse($values['batch_starts_at'])->setTimezone(config('app.timezone')) : null;

        return $values;
    }
}
