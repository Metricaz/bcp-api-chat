<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Borrower extends Model
{
    const UPDATED_AT = null;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'name', 'cpf', 'proposal', 'proposaId',
    ];

    public static function _firstOrCreate($f,$d){
        if (empty($d['name']) or empty($f['cpf'])) {
            return false;
        }
        $f['cpf'] = preg_replace('/[^A-Za-z0-9]/', '', $f['cpf']);
        return parent::firstOrCreate($f,$d);
    }

    public static function _updateOrCreate($f,$d){
        if (empty($d['name']) or empty($f['cpf'])) {
            return false;
        }
        $f['cpf'] = preg_replace('/[^A-Za-z0-9]/', '', $f['cpf']);
        return parent::updateOrCreate($f,$d);
    }

}
