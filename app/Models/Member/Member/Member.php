<?php


namespace App\Models\Member\Member;

use App\Models\Member\Member\Attribute\MemberAttribute;
use App\Models\Member\Member\Relation\MemberRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Member extends Model
{
    use MemberAttribute,
        MemberRelation;

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

    /**
     * 模型引导
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        /**
         * 全局查询作用域
         *
         * 默认获取未被禁用的会员:
         *
         * Member::all(); 生成的SQL语句为： select * from `member` where `islock` = 0
         *
         * 查询时移除方法：
         * Member::withoutGlobalScope('notlock')->get();
         */
        static::addGlobalScope('notlock', function(Builder $builder) {
            $builder->whereIslock(0);
        });
    }

}