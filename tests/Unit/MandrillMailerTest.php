<?php

declare(strict_types=1);

namespace WonderWp\Component\Mailing\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WonderWp\Component\Mailing\Gateways\MandrillMailer;
use WonderWp\Component\Mailing\MailerInterface;

class MandrillMailerTest extends TestCase
{
    protected function fillMailer(MailerInterface $mailer): MailerInterface
    {
        $mailer->setBody('test message');
        $mailer->setSubject('test subject');
        $mailer->addTo('recipient@test.com', 'Recipient Name');
        $mailer->setFrom('sender@test.com', 'Sender Name');

        return $mailer;
    }

    public function testComputePayLoadShouldComputePayloadCorrectly(): void
    {
        $apiKey = 'fakemandrillapikey';
        $mailer = new MandrillMailer($apiKey);
        $this->fillMailer($mailer);
        $opts = [];

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

    public function testCorrectEncodingRecursiveShouldCorrectEncoding(): void
    {
        $mailer = new MandrillMailer('fakemandrillapikey');
        $mailer->setFrom('testmail@test.com', iconv('UTF-8', 'ISO-8859-1', 'Tést name'));

        $opts = [];
        $payload = $mailer->computeJsonPayload($opts);

        self::callMethod($mailer, 'correctEncodingRecursive', [&$payload]);
        $wrongEncoding = mb_detect_encoding($mailer->getFrom()[1], 'UTF-8', true);
        $rightEncoding = mb_detect_encoding($payload['message']['from_name'], 'UTF-8', true);
        $this->assertNotEquals($wrongEncoding, $rightEncoding);
    }

    public static function callMethod(object $obj, string $name, array $args): mixed
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);

        return $method->invokeArgs($obj, $args);
    }
}
