<?php

namespace App\Event;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;

class JWTCreatedListener
{
    /**
     * @param JWTCreatedEvent $event
     * @return void
     */
    public function onJWTCreated(JWTCreatedEvent $event)
    {

        $user = $event->getUser();
        $payload = $event->getData();

        if ($user instanceof User) {
            $payload["email"] = $user->getEmail();
            $payload["firstname"] = $user->getFirstname();
            $payload["lastname"] = $user->getLastname();
            $payload["login"] = $user->getLogin();

            $event->setData($payload);
        }
    }
}