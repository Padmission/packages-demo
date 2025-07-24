<?php

namespace App\Models\Shop;

use App\Models\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;
    use BelongsToTeam;

    /**
     * @var string
     */
    protected $table = 'shop_order_items';
}
