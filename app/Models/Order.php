<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Заказ билетов.
 *
 * @property int $id
 * @property int $event_id
 * @property string $code
 * @property string $buyer_name
 * @property string $buyer_email
 * @property int $quantity
 * @property int $total
 */
class Order extends Model
{
    protected $fillable = [
        'event_id',
        'code',
        'buyer_name',
        'buyer_email',
        'quantity',
        'total',
        'session_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'total' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function getTotalLabelAttribute(): string
    {
        return number_format($this->total, 0, ',', "\u{00A0}")."\u{00A0}₸";
    }

    public function getQuantityLabelAttribute(): string
    {
        $n = $this->quantity;
        $mod10 = $n % 10;
        $mod100 = $n % 100;

        if ($mod10 === 1 && $mod100 !== 11) {
            $word = 'билет';
        } elseif ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
            $word = 'билета';
        } else {
            $word = 'билетов';
        }

        return $n.' '.$word;
    }

    /**
     * Код заказа для показа на входе, например AN-7K2M9Q.
     * В алфавите нет похожих символов (0 и O, 1 и I).
     */
    public static function generateCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = 'AN-';

            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (static::query()->where('code', $code)->exists());

        return $code;
    }
}
