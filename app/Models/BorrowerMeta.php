<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class BorrowerMeta extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'id', 'id_borrowers', 'field', 'value'
    ];

    public static function _updateOrCreate($f,$d){
        if (empty($f['field']) or empty($f['id_borrowers'])) {
            return false;
        }
        return parent::updateOrCreate($f,$d);
    }
}
