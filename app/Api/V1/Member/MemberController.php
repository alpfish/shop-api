<?php

namespace Api\V1\Member;

use Api\V1\Controller;
use App\Repositories\Member\MemberRepository as Member;

class MemberController extends Controller
{
    public function getUser()
    {
        return Member::tokenMember();
    }

}