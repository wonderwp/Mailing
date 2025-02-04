<?php

use WonderWp\Component\Mailing\WpMailer;
use WonderWp\Component\DependencyInjection\Container;

add_action('wonderwp.loader.load', 'wwp_register_mailing_definitions_towards_container', 10, 2);

function wwp_register_mailing_definitions_towards_container(Container $container)
{
    //Emails
    $container['wwp.mailing.mailer'] = $container->factory(function () {
        return new WpMailer();
    });
}
