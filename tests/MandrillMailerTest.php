<?php

namespace WonderWp\Component\Mailing\Tests;

class MandrillMailerTest extends \PHPUnit\Framework\TestCase
{
    protected function fillMailer(\WonderWp\Component\Mailing\MailerInterface $mailer)
    {
        $mailer->setBody('test message');
        $mailer->setSubject('test subject');
        $mailer->addTo('recipient@test.com', 'Recipient Name');
        $mailer->setFrom('sender@test.com', 'Sender Name');

        return $mailer;
    }

    public function testComputePayLoadShouldComputePayloadCorrectly()
    {
        $apiKey = 'fakemandrillapikey';
        $mailer = new \WonderWp\Component\Mailing\Gateways\MandrillMailer($apiKey);
        $this->fillMailer($mailer);
        $opts = [];

        /*$payload         = [
                    'key'              => 'fakemandrillapikey',
                    'message'          =>
                        [
                            'html'                => 'test message',
                            'text'                => null,
                            'subject'             => 'test subject',
                            'from_email'          => 'sender@test.com',
                            'from_name'           => 'Sender Name',
                            'to'                  => [
                                0 => [
                                    'email' => 'recipient@test.com',
                                    'name'  => 'Recipient Name',
                                    'type'  => 'to',
                                ],
                            ],
                            'important'           => false,
                            'track_opens'         => true,
                            'track_clicks'        => true,
                            'auto_text'           => true,
                            'auto_html'           => false,
                            'inline_css'          => true,
                            'url_strip_qs'        => false,
                            'preserve_recipients' => null,
                            'view_content_link'   => null,
                            'tracking_domain'     => null,
                            'signing_domain'      => null,
                            'return_path_domain'  => null,
                            'merge'               => true,
                            'merge_language'      => 'mailchimp',
                            'global_merge_vars'   => [],
                            'merge_vars'          => [],
                            'metadata'            => [],
                            'recipient_metadata'  => [],
                            'attachments'         => [],
                            'images'              => [],
                        ],
                    'async'            => false,
                    'ip_pool'          => 'Main Pool'
                ];
                */

        $payload = $mailer->computeJsonPayload($opts);

        $this->assertArrayHasKey('key', $payload);
        $this->assertEquals($apiKey, $payload['key']);

        $this->assertArrayHasKey('message', $payload);
        $this->assertEquals($mailer->getBody(), $payload['message']['html']);
        $this->assertEquals($mailer->getSubject(), $payload['message']['subject']);
        $this->assertEquals($mailer->getFrom()[0], $payload['message']['from_email']);
        $this->assertEquals($mailer->getFrom()[1], $payload['message']['from_name']);
        $to = $mailer->getTo();
        $this->assertEquals(reset($to)[0], $payload['message']['to'][0]['email']);
    }

    public function testCorrectEncodingRecursiveShouldCorrectEncoding()
    {

        $mailer = new \WonderWp\Component\Mailing\Gateways\MandrillMailer('fakemandrillapikey');
        $mailer->setFrom('testmail@test.com', iconv('UTF-8', 'ISO-8859-1', 'Tést name'));

        $opts            = [];
        $payload         = $mailer->computeJsonPayload($opts);
        $originalPayload = $payload;

        self::callMethod($mailer, 'correctEncodingRecursive', [&$payload]);
        $wrongEncoding = mb_detect_encoding($mailer->getFrom()[1], 'UTF-8', true);
        $rightEncoding = mb_detect_encoding($payload['message']['from_name'], 'UTF-8', true);
        $this->assertNotEquals($wrongEncoding, $rightEncoding);
    }

    public static function callMethod($obj, $name, array $args)
    {
        $class  = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $args);
    }
}
