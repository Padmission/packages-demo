<?php

namespace App\Models\Shop;

use App\Models\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OrderAddress extends Model
{
    use BelongsToTeam;
    use HasFactory;

    protected $table = 'shop_order_addresses';

    /** @return MorphTo<Model,self> */
    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }
}
