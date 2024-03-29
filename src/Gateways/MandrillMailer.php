<?php

namespace WonderWp\Component\Mailing\Gateways;

use Throwable;
use WonderWp\Component\Mailing\Result\EmailResult;
use function WonderWp\Functions\array_merge_recursive_distinct;
use WonderWp\Component\Mailing\AbstractMailer;

class MandrillMailer extends AbstractMailer
{
    /** @var \Mandrill */
    protected $mandrill;

    public function __construct($apiKey)
    {
        parent::__construct();
        if (empty($this->mandrill)) {
            $this->mandrill = new \Mandrill($apiKey);
        }
    }

    /** @inheritDoc */
    public function send(array $opts = [])
    {
        //Check for any validation errors
        $error = $this->checkForValidationError($opts);
        if (!empty($error)) {
            return $this->returnValidationError($error);
        }

        //Then try to send
        try {

            $jsonPayLoad = $this->computeJsonPayload($opts);

            $endPointUrl = '/messages/send';

            $body = $this->getBody();
            if (strpos($body, 'template::') !== false) {
                $endPointUrl = '/messages/send-template';
            }

            $res = $this->mandrill->call($endPointUrl, $jsonPayLoad);

            $successes = [];
            $failures  = [];

            if (!empty($res)) {
                foreach ($res as $sentTo) {
                    if (!empty($sentTo['status']) && in_array($sentTo['status'], ["sent", "queued", "scheduled"])) {
                        $successes[] = $sentTo;
                    } else {
                        $failures[] = $sentTo;
                    }
                }
            }

            $code   = 500;
            $msgKey = EmailResult::MailNotSentMsgKey;
            if (!empty($successes)) {
                $code   = EmailResult::SuccessCode;
                $msgKey = EmailResult::MailSentMsgKey;
            }
            $response = ['res' => $res, 'successes' => $successes, 'failures' => $failures];

            $result = new EmailResult($code, $msgKey, $response, [], null, $this);

        } catch (Throwable $e) {
            $result = new EmailResult(500, EmailResult::MailNotSentMsgKey, null, [], $e, $this);
        }

        return apply_filters(static::SendResultFilterName, $result);
    }

    /**
     * Opts to json payload
     *
     * @param $opts
     *
     * @return array
     */
    public function computeJsonPayload($opts)
    {

        $body     = $this->getBody();
        $template = null;
        if (strpos($body, 'template::') !== false) {
            $template = str_replace('template::', '', $body);
            $body     = null;
        }

        $defaultOpts = [
            'key'     => $this->mandrill->apikey,
            'message' =>
                [
                    'html'                => $body,
                    'text'                => $this->getAltBody(),
                    'subject'             => $this->getSubject(),
                    'to'                  => [], //set further down
                    'important'           => false,
                    'track_opens'         => true,
                    'track_clicks'        => true,
                    'auto_text'           => true,
                    'auto_html'           => false,
                    'inline_css'          => true,
                    'url_strip_qs'        => false,
                    'preserve_recipients' => null,
                    'view_content_link'   => null,
                    //'bcc_address' => 'message.bcc_address@example.com',
                    'tracking_domain'     => null,
                    'signing_domain'      => null,
                    'return_path_domain'  => null,
                    'merge'               => true,
                    'merge_language'      => 'mailchimp',
                    'global_merge_vars'   =>
                        [
                            /*0 =>
                                array (
                                    'name' => 'merge1',
                                    'content' => 'merge1 content',
                                ),*/
                        ],
                    'merge_vars'          =>
                        [
                            /*0 =>
                                array (
                                    'rcpt' => 'recipient.email@example.com',
                                    'vars' =>
                                        array (
                                            0 =>
                                                array (
                                                    'name' => 'merge2',
                                                    'content' => 'merge2 content',
                                                ),
                                        ),
                                ),*/
                        ],
                    /*'tags' =>
                        array (
                            0 => 'password-resets',
                        ),
                    'subaccount' => 'customer-123',
                    'google_analytics_domains' =>
                        array (
                            0 => 'example.com',
                        ),
                    'google_analytics_campaign' => 'message.from_email@example.com',
                    'metadata'            =>
                        [
                            'website' => get_bloginfo('url'),
                        ],
                    */
                    'recipient_metadata'  =>
                        [
                            /*0 =>
                                array (
                                    'rcpt' => 'recipient.email@example.com',
                                    'values' =>
                                        array (
                                            'user_id' => 123456,
                                        ),
                                ),*/
                        ],
                    'attachments'         =>
                        [
                            /*0 =>
                                array (
                                    'type' => 'text/plain',
                                    'name' => 'myfile.txt',
                                    'content' => 'ZXhhbXBsZSBmaWxl',
                                ),*/
                        ],
                    'images'              =>
                        [
                            /* 0 =>
                                 array (
                                     'type' => 'image/png',
                                     'name' => 'IMAGECID',
                                     'content' => 'ZXhhbXBsZSBmaWxl',
                                 ),*/
                        ],
                ],
            'async'   => false,
            'ip_pool' => 'Main Pool',
            //'send_at' => date('Y-m-d H:i:s'),
        ];

        if (!empty($this->from)) {
            $defaultOpts['message']['from_email'] = $this->from[0];
            $defaultOpts['message']['from_name']  = $this->from[1];
        }

        //template ?
        if (!empty($template)) {
            $defaultOpts['template_name']    = $template;
            $defaultOpts['template_content'] = [];
        }

        //Add recipients
        if (!empty($this->to)) {
            foreach ($this->to as $to) {
                $defaultOpts['message']['to'][] = [
                    'email' => $to[0],
                    'name'  => $to[1],
                    'type'  => 'to',
                ];
            }
        }

        //reply to
        if (!empty($this->replyTo)) {
            $defaultOpts['message']['headers']['Reply-To'] = $this->replyTo[0];
        }

        $payload = array_merge_recursive_distinct($defaultOpts, $opts);
        $this->correctEncodingRecursive($payload);

        return $payload;
    }

    /**
     * @param $array
     * @return array
     */
    protected function correctEncodingRecursive(&$array)
    {
        if (!empty($array)) {
            foreach ($array as $key => $val) {
                if (is_array($val)) {
                    $array[$key] = $this->correctEncodingRecursive($val);
                } else {
                    $array[$key] = $this->correctEncoding($val);
                }
            }
        }

        return $array;
    }

    /**
     * @param $str
     * @return mixed|string
     */
    protected function correctEncoding($str)
    {
        if (is_string($str) && !preg_match('!!u', $str)) {
            $str = utf8_encode($str);
        }

        return $str;
    }
}
