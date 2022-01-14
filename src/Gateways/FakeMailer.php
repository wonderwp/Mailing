<?php

namespace WonderWp\Component\Mailing\Gateways;

use WonderWp\Component\Mailing\AbstractMailer;
use WonderWp\Component\Mailing\Result\EmailResult;

class FakeMailer extends AbstractMailer
{
    /**
     * @inheritDoc
     */
    public function send(array $opts = [])
    {
        $error = $this->preSendValidation();
        if (!empty($error)) {
            $result = new EmailResult(400, EmailResult::MailNotSentMsgKey, null, [], $error, $this);
        } else {
            $result = new EmailResult(200);
        }

        return apply_filters(static::SendResultFilterName, $result);
    }

}
