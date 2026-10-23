<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChildInventory extends Model
{
    protected $table = 'child_inventory';
    protected $primaryKey = 'inventory_id';
    public $timestamps = false;

    protected $fillable = [
        'child_id',
        'item_key',
        'item_name',
        'cost',
        'purchase_date',
    ];

    protected $casts = [
        'purchase_date' => 'datetime',
        'cost' => 'integer',
    ];

    public function child()
    {
        return $this->belongsTo(ChildProfile::class, 'child_id');
    }
}
