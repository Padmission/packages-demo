<?php

namespace App\Models\Shop;

use App\Models\Address;
use App\Models\Comment;
use App\Models\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Relaticle\CustomFields\Models\Concerns\UsesCustomFields;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;

class Customer extends Model implements HasCustomFields
{
    use BelongsToTeam;
    use HasFactory;
    use SoftDeletes;
    use UsesCustomFields;

    /**
     * @var string
     */
    protected $table = 'shop_customers';

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'birthday' => 'date',
    ];

    /** @return MorphToMany<Address> */
    public function addresses(): MorphToMany
    {
        return $this->morphToMany(Address::class, 'addressable');
    }

    /** @return HasMany<Comment> */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /** @return HasMany<Order> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'shop_customer_id');
    }

    /** @return HasManyThrough<Payment> */
    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(Payment::class, Order::class, 'shop_customer_id');
    }
}
