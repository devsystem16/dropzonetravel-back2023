<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Imagenes extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'imagenes';
    protected $dates = ['deleted_at'];
    protected $fillable = ['tour_id', 'paths3', 'orden', 'estado'];

    public function Tour()
    {
        return $this->belongsTo(Tours::class,  'tour_id');
    }
}
