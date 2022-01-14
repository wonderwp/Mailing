<?php

namespace WonderWp\Component\Mailing\Result;

use Throwable;
use WonderWp\Component\HttpFoundation\Result;
use WonderWp\Component\Mailing\MailerInterface;

class EmailResult extends Result
{
    const MailSentMsgKey    = 'wwp.mailer.mail.sent';
    const MailNotSentMsgKey = 'wwp.mailer.mail.notsent';

    const SuccessCode = 200;

    /** @var string */
    protected $msgKey;

    /** @var mixed */
    protected $response;

    /** @var Throwable */
    protected $error;

    /** @var MailerInterface */
    protected $mailerInstance;


    /**
     * @param int $code
     * @param string $msgKey
     * @param array $data
     * @param Throwable|null $error
     */
    public function __construct(
        $code,
        string $msgKey = '',
        $response = null,
        array $data = [],
        Throwable $error = null,
        MailerInterface $mailerInstance = null
    )
    {
        parent::__construct($code, $data);

        if (empty($msgKey)) {
            $msgKey = ($code === static::SuccessCode) ? static::MailSentMsgKey : static::MailNotSentMsgKey;
        }

        $this->msgKey         = $msgKey;
        $this->response       = $response;
        $this->error          = $error;
        $this->mailerInstance = $mailerInstance;
    }

    /**
     * @return Throwable|null
     */
    public function getError(): ?Throwable
    {
        return $this->error;
    }

    /**
     * @param Throwable|null $error
     * @return EmailResult
     */
    public function setError(?Throwable $error): EmailResult
    {
        $this->error = $error;
        return $this;
    }

    /**
     * @return string
     */
    public function getMsgKey(): string
    {
        return $this->msgKey;
    }

    /**
     * @param string $msgKey
     * @return EmailResult
     */
    public function setMsgKey(string $msgKey): EmailResult
    {
        $this->msgKey = $msgKey;
        return $this;
    }

    /**
     * @param $key
     * @return $this
     */
    public function unsetData($key)
    {
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
        }
        return $this;
    }

    public function isSuccess()
    {
        return $this->code === static::SuccessCode;
    }

    /**
     * @return mixed
     */
    public function getResponse(): mixed
    {
        return $this->response;
    }

    /**
     * @param mixed $response
     * @return EmailResult
     */
    public function setResponse(mixed $response): EmailResult
    {
        $this->response = $response;
        return $this;
    }

    /**
     * @return MailerInterface
     */
    public function getMailerInstance(): MailerInterface
    {
        return $this->mailerInstance;
    }

    /**
     * @param MailerInterface $mailerInstance
     * @return EmailResult
     */
    public function setMailerInstance(MailerInterface $mailerInstance): EmailResult
    {
        $this->mailerInstance = $mailerInstance;
        return $this;
    }
}
