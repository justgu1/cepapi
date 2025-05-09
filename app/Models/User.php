<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function ceps()
    {
        return $this->belongsToMany(Cep::class, 'cep_user_pivot')
            ->withPivot('nickname')
            ->withTimestamps();
    }

    public function addToFavorites(Cep $cep, string $nickname)
    {
        return $this->ceps()->attach($cep->id, ['nickname' => $nickname]);
    }

    public function removeFromFavorites(Cep $cep)
    {
        return $this->ceps()->detach($cep->id);
    }

    public function isFavorite(Cep $cep): bool
    {
        return $this->ceps->contains($cep);
    }
}
