<?php

namespace WonderWp\Component\Mailing;

use Throwable;
use WonderWp\Component\Mailing\Exception\MailerDeliveryException;
use WonderWp\Component\Mailing\Result\EmailResult;
use WP_Error;

class WpMailer extends AbstractMailer
{
    /** @var WP_Error */
    private $wpMailFailedError = null;

    /** @inheritdoc */
    public function setFrom($email, $name = '')
    {
        $this->addMailHeader('From', (string)$email, (string)$name);

        return $this;
    }

    /** @inheritdoc */
    public function addTo($email, $name = "")
    {
        $this->to[] = $this->formatHeader((string)$email, (string)$name);

        return $this;
    }

    /** @inheritdoc */
    public function setReplyTo($email, $name = "")
    {
        $this->headers[] = sprintf('%s: %s', (string)'Reply-To', $this->formatHeader((string)$email, (string)$name));
    }

    /** @inheritdoc */
    public function addCc($email, $name = "")
    {
        $this->cc[] = $this->formatHeader((string)$email, (string)$name);

        return $this;
    }

    /** @inheritdoc */
    public function addBcc($email, $name = "")
    {
        $this->bcc[] = $this->formatHeader((string)$email, (string)$name);

        return $this;
    }

    /** @inheritdoc */
    public function setBody($body)
    {
        $this->body = apply_filters('wwp.mailer.setBody', str_replace("\n.", "\n..", (string)$body));

        if (strpos($this->body, '<body') !== false) {
            // Pour envoyer un mail HTML, l'en-tête Content-type doit être défini
            $this->headers[] = sprintf('%s: %s', (string)'Mime-Version', '1.0');
            $this->headers[] = sprintf('%s: %s', (string)'Content-type', 'text/html; charset=utf8');
        }

        return $this;
    }

    /** @inheritdoc */
    public function send(array $opts = [])
    {
        //Check for any validation errors
        $error = $this->checkForValidationError($opts);
        if (!empty($error)) {
            return $this->returnValidationError($error);
        }

        //Then try to send
        try {
            //Wp provides a filter we can listen to, to provide better email delivery error explanations
            $this->setupErrorListener();

            $headers  = $this->prepareHeaders();
            $to       = !(empty($this->to)) ? join(', ', $this->to) : '';
            $sent     = wp_mail($to, $this->subject, $this->body, $headers);
            $code     = $sent ? EmailResult::SuccessCode : 500;
            $msgKey   = $sent ? EmailResult::MailSentMsgKey : EmailResult::MailNotSentMsgKey;
            $response = $sent;
            $error    = null;

            if (!$sent && !empty($this->wpMailFailedError)) {
                $error = new MailerDeliveryException(
                    $this->wpMailFailedError->get_error_message(),
                    500,
                    null,
                    [$this->wpMailFailedError]
                );
            }

            $this->removeEventListener();

            $result = new EmailResult($code, $msgKey, $response, [], $error, $this);
        } catch (Throwable $e) {
            $result = new EmailResult(500, EmailResult::MailNotSentMsgKey, null, [], $e, $this);
        }

        return apply_filters(static::SendResultFilterName, $result);
    }

    /**
     * array of headers to formatted string
     * @return string
     */
    public function prepareHeaders()
    {
        if (!empty($this->cc)) {
            $this->headers[] = sprintf('%s: %s', (string)'Cc', join(',', $this->cc));
        }
        if (!empty($this->bcc)) {
            $this->headers[] = sprintf('%s: %s', (string)'Bcc', join(',', $this->bcc));
        }

        return !(empty($this->headers)) ? join(PHP_EOL, $this->headers) : '';
    }

    /**
     * addMailHeader
     *
     * @param string $header The header to add.
     * @param string $email The email to add.
     * @param string $name The name to add.
     *
     * @return $this
     */
    public function addMailHeader($header, $email = null, $name = null)
    {
        $address         = $this->formatHeader((string)$email, (string)$name);
        $this->headers[] = sprintf('%s: %s', (string)$header, $address);

        return $this;
    }

    /**
     * formatHeader
     *
     * Formats a display address for emails according to RFC2822 e.g.
     * Name <address@domain.tld>
     *
     * @param string $email The email address.
     * @param string $name The display name.
     *
     * @return string
     */
    public function formatHeader($email, $name = null)
    {
        $email = $this->filterEmail($email);
        if (empty($name)) {
            return $email;
        }
        $name = $this->encodeUtf8($this->filterName($name));

        return sprintf('"%s" <%s>', $name, $email);
    }

    /**
     * encodeUtf8
     *
     * @param string $value The value to encode.
     *
     * @return string
     */
    public function encodeUtf8($value)
    {
        $value = trim($value);
        if (preg_match('/(\s)/', $value)) {
            return $this->encodeUtf8Words($value);
        }

        return $this->encodeUtf8Word($value);
    }

    /**
     * encodeUtf8Word
     *
     * @param string $value The word to encode.
     *
     * @return string
     */
    public function encodeUtf8Word($value)
    {
        return sprintf('=?UTF-8?B?%s?=', base64_encode($value));
    }

    /**
     * encodeUtf8Words
     *
     * @param string $value The words to encode.
     *
     * @return string
     */
    public function encodeUtf8Words($value)
    {
        $words   = explode(' ', $value);
        $encoded = [];
        foreach ($words as $word) {
            $encoded[] = $this->encodeUtf8Word($word);
        }

        return join($this->encodeUtf8Word(' '), $encoded);
    }

    /**
     * filterEmail
     *
     * Removes any carriage return, line feed, tab, double quote, comma
     * and angle bracket characters before sanitizing the email address.
     *
     * @param string $email The email to filter.
     *
     * @return string
     */
    public function filterEmail($email)
    {
        $rule  = [
            "\r" => '',
            "\n" => '',
            "\t" => '',
            '"'  => '',
            ','  => '',
            '<'  => '',
            '>'  => '',
        ];
        $email = strtr($email, $rule);
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);

        return $email;
    }

    /**
     * filterName
     *
     * Removes any carriage return, line feed or tab characters. Replaces
     * double quotes with single quotes and angle brackets with square
     * brackets, before sanitizing the string and stripping out html tags.
     *
     * @param string $name The name to filter.
     *
     * @return string
     */
    public function filterName($name)
    {
        $rule     = [
            "\r" => '',
            "\n" => '',
            "\t" => '',
            '"'  => "'",
            '<'  => '[',
            '>'  => ']',
        ];
        
        $filtered = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

        return trim(strtr($filtered, $rule));
    }

    protected function setupErrorListener()
    {
        //Reset any previously set error
        $this->wpMailFailedError = null;

        add_action('wp_mail_failed', [$this, 'setWpMailFailedError']);
    }

    public function setWpMailFailedError($error)
    {
        $this->wpMailFailedError = $error;
    }

    protected function removeEventListener()
    {
        //Reset any previously set error
        $this->wpMailFailedError = null;

        remove_action('wp_mail_failed', [$this, 'setWpMailFailedError']);
    }
}
