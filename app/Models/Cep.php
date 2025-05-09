<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cep extends Model
{
    use HasFactory;

    protected $fillable = [
        'cep',
        'logradouro',
        'complemento',
        'unidade',
        'bairro',
        'localidade',
        'uf',
        'estado',
        'regiao',
        'ibge',
        'gia',
        'ddd',
        'siafi',
    ];

    // get users using cep relation
    public function users()
    {
        return $this->belongsToMany(User::class, 'cep_user_pivot')
            ->withPivot('nickname')
            ->withTimestamps();
    }
}
