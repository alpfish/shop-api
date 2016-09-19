<?php


namespace App\Repositories\Caches\Member;

use App\Models\Member\Member\Member;
use App\Repositories\Caches\RedisKeys as Keys;

class MemberCacheRepository
{
    public static function model($id)
    {
        $key = sprintf(Keys::MEMBER_MODEL_ID[ 'key' ], $id);

        if (!$data = app('cache')->get($key)) {
            if ($data = Member::find($id)) {
                $time = Keys::MEMBER_MODEL_ID[ 'time' ];
                app('cache')->put($key, $data, $time);
            }
        }

        return $data;
    }
}