<?php

namespace WonderWp\Component\Mailing\Gateways;

use WonderWp\Component\Mailing\AbstractMailer;
use WonderWp\Component\Mailing\Result\EmailResult;

class FakeMailer extends AbstractMailer
{
    /** @inheritDoc */
    public function send(array $opts = [])
    {
        //Check for any validation errors
        $error = $this->checkForValidationError($opts);
        if (!empty($error)) {
            return $this->returnValidationError($error);
        }

        //Fake send by returning a successful EmailResult
        $result = new EmailResult(EmailResult::SuccessCode);

        return apply_filters(static::SendResultFilterName, $result);
    }

}
