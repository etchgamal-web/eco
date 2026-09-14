<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['group', 'key', 'value', 'type', 'description', 'is_secret', 'is_encrypted'];

    protected function casts(): array
    {
        return ['is_secret' => 'boolean', 'is_encrypted' => 'boolean'];
    }

    public function getTypedValue(): mixed
    {
        $raw = $this->is_encrypted && $this->value !== null
            ? Crypt::decryptString($this->value)
            : $this->value;

        return match ($this->type) {
            'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $raw,
            'float' => (float) $raw,
            'json' => json_decode($raw, true),
            default => $raw,
        };
    }

    public function setTypedValue(mixed $value): void
    {
        $raw = match ($this->type) {
            'boolean' => $value ? '1' : '0',
            'integer', 'float' => (string) $value,
            'json' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            default => $value === null ? null : (string) $value,
        };
        $this->value = $this->is_encrypted && $raw !== null ? Crypt::encryptString($raw) : $raw;
    }
}
