<?php

namespace Api\Member;

use Api\Controller;
use App\Repositories\Member\MemberRepository as Member;

class MemberController extends Controller
{
    public function getUser()
    {
        return Member::tokenMember();
    }

}