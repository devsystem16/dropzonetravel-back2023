<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nacionalidad extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'nacionalidads';
    protected $dates = ['deleted_at'];
    protected $fillable = ['descripcion', 'codigoPais', 'default'];

    public function Clientes()
    {
        return $this->hasMany(Clientes::class, 'nacionalidad_id', 'id');
    }
}
