<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyUser extends Model
{
    /**
     * El usuario de la relación.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * La compañía de la relación.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
