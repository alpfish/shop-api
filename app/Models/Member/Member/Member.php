<?php


namespace App\Models\Member\Member;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $table = 'member';

    protected $hidden = ['password', 'encrypt'];

}