<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $form
 * @property int|null $prescription_count
 * @property string|null $last_prescribed
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Prescription> $prescriptions
 * @property-read int|null $prescriptions_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medicine newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medicine newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medicine onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medicine query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medicine whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medicine whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medicine whereForm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medicine whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medicine whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medicine whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medicine withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medicine withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Medicine extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'medicines_lookup';

    protected $fillable = [
        'name',
        'form',
    ];

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'medicine_id');
    }
}
