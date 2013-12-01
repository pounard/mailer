<?php

namespace Mailer\Core;

class Messager extends AbstractContainerAware
{
    /**
     * Add message
     *
     * @param string $message
     * @param int $type
     * @param \DateTime
     */
    public function addMessage($message, $type = Message::TYPE_INFO, \DateTime $date = null)
    {
        $storage = $this
            ->getContainer()
            ->getSession()
            ->getStorage();

        $messages = $storage['messages'];
        $messages[] = new Message($message, $type, $date);
        $storage['messages'] = $messages;
    }

    /**
     * Get all current messages
     *
     * @param string $clear
     *
     * @return Message[]
     */
    public function getMessages($clear = true)
    {
        $storage = $this
            ->getContainer()
            ->getSession()
            ->getStorage();

        if (!$messages = $storage['messages']) {
            return array();
        }

        if ($clear) {
            unset($storage['messages']);
        }

        return $messages;
    }
}
