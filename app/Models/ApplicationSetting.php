<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationSetting whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationSetting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationSetting whereValue($value)
 *
 * @mixin \Eloquent
 */
class ApplicationSetting extends Model
{
    use LogsActivity;

    protected $table = 'application_settings';

    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = self::where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::query()->updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value]
        );
    }
}
