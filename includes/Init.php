<?php
namespace Xxxwraithxxx\PayraCashCryptoPayment;

if (!defined('ABSPATH')) exit;

final class Init
{
    /**
    * Store all the classes inside array
    * @return array Full list of classes
    */
    public static function get_services()
    {
        return [
            News::class,
            Menu::class,
            Enqueue::class,
            Settings::class,
            Processing::class,
            Transaction::class,
            Cron::class,
            Update::class,
        ];
    }

    /**
    * Loop through the classes, initialize them
    * and call the register() method if exists
    * @return
    */
    public static function register_services(): void
    {
        add_action('init', [self::class, 'payracacr_action_register_services']);
    }

    public static function payracacr_action_register_services(): void
    {
        foreach (self::get_services() as $class) {
            if (class_exists($class)) {
                $service = new $class();
                if (method_exists($service, 'register')) {
                    $service->register();
                }
            }
        }
    }

}
