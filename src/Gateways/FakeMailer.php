<?php

namespace WonderWp\Component\Mailing\Gateways;

use WonderWp\Component\Mailing\AbstractMailer;
use WonderWp\Component\Mailing\Result\EmailResult;

class FakeMailer extends AbstractMailer
{
    /** @inheritDoc */
    public function send(array $opts = [])
    {
        //Check for any obvious errors
        $error = $this->preSendValidation($opts);
        if (!empty($error)) {
            $result = new EmailResult(400, EmailResult::MailNotSentMsgKey, null, [], $error, $this);
        } else {
            //Fake send by returning a successful EmailResult
            $result = new EmailResult(EmailResult::SuccessCode);
        }

        return apply_filters(static::SendResultFilterName, $result);
    }

}
