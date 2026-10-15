<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Мероприятие афиши.
 *
 * @property int $id
 * @property string $name
 * @property string $category
 * @property string $venue
 * @property string $city
 * @property \Illuminate\Support\Carbon $starts_at
 * @property int $price
 * @property int $tickets_total
 * @property int $tickets_left
 * @property string|null $description
 */
class Event extends Model
{
    /** @use HasFactory<\Database\Factories\EventFactory> */
    use HasFactory;

    /** Часовой пояс, в котором показываются даты (Костанай). */
    public const DISPLAY_TIMEZONE = 'Asia/Almaty';

    /** Категории мероприятий: ключ используется в URL и CSS, значение показывается людям. */
    public const CATEGORIES = [
        'concert' => 'Концерт',
        'theatre' => 'Театр',
        'festival' => 'Фестиваль',
        'exhibition' => 'Выставка',
        'cinema' => 'Кино',
        'lecture' => 'Лекция',
    ];

    protected $fillable = [
        'name',
        'category',
        'venue',
        'city',
        'starts_at',
        'price',
        'tickets_total',
        'tickets_left',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'price' => 'integer',
            'tickets_total' => 'integer',
            'tickets_left' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Поиск работает по столбцу search_index в нижнем регистре.
        // SQLite умеет сравнивать без учёта регистра только латиницу,
        // поэтому кириллицу приводим к нижнему регистру на стороне PHP.
        static::saving(function (Event $event) {
            $event->search_index = mb_strtolower(implode(' ', [
                $event->name,
                $event->venue,
                self::CATEGORIES[$event->category] ?? '',
            ]));
        });
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** Только предстоящие мероприятия. */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now());
    }

    private function local(): CarbonInterface
    {
        return $this->starts_at->copy()
            ->timezone(self::DISPLAY_TIMEZONE)
            ->locale('ru');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getDayAttribute(): string
    {
        return $this->local()->format('j');
    }

    /** Месяц в родительном падеже: «октября». */
    public function getMonthLabelAttribute(): string
    {
        $text = $this->local()->translatedFormat('j F');

        return trim(preg_replace('/^\d+\s*/u', '', $text));
    }

    public function getWeekdayLabelAttribute(): string
    {
        return $this->local()->translatedFormat('D');
    }

    public function getTimeLabelAttribute(): string
    {
        return $this->local()->format('H:i');
    }

    public function getDateLabelAttribute(): string
    {
        return $this->local()->translatedFormat('j F, H:i');
    }

    public function getDateLongAttribute(): string
    {
        return $this->local()->translatedFormat('l, j F Y, H:i');
    }

    public function getIsoAttribute(): string
    {
        return $this->local()->toIso8601String();
    }

    public function getPriceLabelAttribute(): string
    {
        return $this->formatPrice($this->price);
    }

    public function getLeftLabelAttribute(): string
    {
        return $this->tickets_left > 0
            ? 'Осталось '.$this->tickets_left
            : 'Билетов нет';
    }

    public function getIsSoldOutAttribute(): bool
    {
        return $this->tickets_left <= 0;
    }

    public function getIsLowAttribute(): bool
    {
        return $this->tickets_left > 0 && $this->tickets_left <= 10;
    }

    public function getIsPastAttribute(): bool
    {
        return $this->starts_at->isPast();
    }

    public function formatPrice(int $amount): string
    {
        return number_format($amount, 0, ',', "\u{00A0}")."\u{00A0}₸";
    }
}
