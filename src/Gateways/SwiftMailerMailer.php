<?php

namespace WonderWp\Component\Mailing\Gateways;

use Throwable;
use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\Mailing\AbstractMailer;
use WonderWp\Component\Mailing\Result\EmailResult;

class SwiftMailerMailer extends AbstractMailer
{
    /** @var \Swift_Message */
    protected $message;

    /** @inheritdoc */
    public function __construct()
    {
        parent::__construct();

        $this->message = \Swift_Message::newInstance();
    }

    /** @inheritdoc */
    public function setSubject($subject)
    {
        $this->message->setSubject($subject);

        return $this;
    }

    /** @inheritdoc */
    public function setFrom($email, $name = "")
    {
        $this->message->setFrom([$email => $name]);

        return $this;
    }

    /** @inheritdoc */
    public function addTo($email, $name = "")
    {
        $this->message->addTo($email, $name);

        return $this;
    }

    /** @inheritdoc */
    public function addCc($email, $name = "")
    {
        $this->message->addCc($email, $name);

        return $this;
    }

    /** @inheritdoc */
    public function addBcc($email, $name = "")
    {
        $this->message->addBcc($email, $name);

        return $this;
    }

    /** @inheritdoc */
    public function setBody($body)
    {
        $body = apply_filters('wwp.mailer.setBody', str_replace("\n.", "\n..", (string)$body));
        $this->message->setBody($body, 'text/html');

        return $this;
    }

    /** @inheritdoc */
    public function send(array $opts = [])
    {
        //Check for any obvious errors
        $error = $this->preSendValidation($opts);
        if (!empty($error)) {
            $result = new EmailResult(400, EmailResult::MailNotSentMsgKey, null, [], $error, $this);
            return apply_filters(static::SendResultFilterName, $result);
        }

        //Then try to send
        try {
            $container = Container::getInstance();
            $transport = $container->offsetExists('wwp.mailing.mailer.swift_transport') ? $container->offsetGet('wwp.mailing.mailer.swift_transport') : \Swift_MailTransport::newInstance();
            $mailer    = \Swift_Mailer::newInstance($transport);
            $nbSent    = $mailer->send($this->message);
            $isSuccess = $nbSent > 0;
            $code      = $isSuccess ? EmailResult::SuccessCode : 500;
            $msgKey    = $isSuccess ? EmailResult::MailSentMsgKey : EmailResult::MailNotSentMsgKey;
            $response  = ['res' => $nbSent, 'successes' => $nbSent, 'failures' => null];
            $result    = new EmailResult($code, $msgKey, $response, [], null, $this);
        } catch (Throwable $e) {
            $result = new EmailResult(500, EmailResult::MailNotSentMsgKey, null, [], $e, $this);
        }

        return apply_filters(static::SendResultFilterName, $result);
    }
}
