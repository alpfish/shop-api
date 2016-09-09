<?php


namespace App\Models\Member\Member;

use App\Models\Member\Member\Attribute\MemberAttribute;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use MemberAttribute;

    protected $table = 'member';

    protected $hidden = ['password', 'encrypt'];

    protected $guarded = ['id'];

    /**
     * 禁用时间戳
     *
     * 禁用 Lumen 自带的 created_at 和 updated_at 两个时间戳字段，
     * 表中没有这两个字段，禁用后插入/更新记录才能成功。
     *
     * @var bool
     */
    public $timestamps = false;

}