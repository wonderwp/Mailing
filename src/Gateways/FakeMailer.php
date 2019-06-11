<?php

namespace WonderWp\Component\Mailing\Gateways;

use WonderWp\Component\HttpFoundation\Result;
use WonderWp\Component\Mailing\AbstractMailer;

class FakeMailer extends AbstractMailer
{
    /**
     * @inheritDoc
     */
    public function send(array $opts = [])
    {
        return new Result(200);
    }

}
