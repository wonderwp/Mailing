<?php

class MandrillMailerTest extends \PHPUnit\Framework\TestCase
{

    public function testCorrectEncodingRecursiveShouldCorrectEncoding()
    {

        $mailer = new \WonderWp\Component\Mailing\Gateways\MandrillMailer('fakemandrillapikey');

        /*$payload         = [
            'key'              => 'lQy5fGi3hHF6PP0-yGNiTg',
            'message'          =>
                [
                    'html'                => null,
                    'text'                => null,
                    'subject'             => 'Bienvenue au club',
                    'from_email'          => 'contact@pinkladyeurope.com',
                    'from_name'           => 'Pink Lady®',
                    'to'                  => [
                        0 => [
                            'email' => 'guduzujucu@hotmail.com',
                            'name'  => 'Ainsley Pena',
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
                    'metadata'            => [
                        'website' => 'http://local.pomme-pinklady.com',
                    ],
                    'recipient_metadata'  => [],
                    'attachments'         => [],
                    'images'              => [],
                ],
            'async'            => false,
            'ip_pool'          => 'Main Pool',
            'template_name'    => 'mailing-eep1-v1-fr',
            'template_content' => [],
            'civilite'         => '75',
            'firstName'        => 'Ainsley',
            'lastName'         => 'Pena',
            'dob'              => new DateTime(),
            'address'          => 'Cumque labore velit maiores est officia iusto eveniet dolor nulla aut animi reprehenderit rerum aut officiis quaerat molestias laboriosam',
            'cp'               => 'Magnam mol',
            'city'             => 'Ipsa quas iste minim explicabo Eligendi veniam unde duis pariatur Nulla facere eu molestias tempor pariatur Sunt sed ipsa',
            'country'          => 'NL',
            'email'            => 'guduzujucu@hotmail.com',
            'password'         => 'Pa$$w0rd!',
            'confirmpwd'       => 'Pa$$w0rd!',
            'consoPommes'      => '0',
            'cgu'              => '1',
            'registerNonce'    => '13e166b337',
            'cguAccepted'      => new DateTime(),
            'locale'           => 'fr_FR',
        ];
        $originalPayload = $payload;*/

        $mailer->addTo('testmail@test.com', iconv('UTF-8', 'ISO-8859-1','Tést name'));
        //$mailer->setFrom('testmail2@test.com', 'From Nâme witch spëcial char$');

        $opts            = [];
        $payload         = $mailer->computeJsonPayload($opts);
        $originalPayload = $payload;

        $correctedPayload = self::callMethod($mailer, 'correctEncodingRecursive', [&$payload]);
        $this->assertEquals($originalPayload, $correctedPayload);
    }

    public static function callMethod($obj, $name, array $args)
    {
        $class  = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $args);
    }
}
