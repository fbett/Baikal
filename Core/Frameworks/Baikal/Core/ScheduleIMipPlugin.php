<?php

namespace Baikal\Core;

use Sabre\DAV;
use Sabre\VObject\ITip;

class ScheduleIMipPlugin extends \Sabre\CalDAV\Schedule\IMipPlugin {

    /**
     * Event handler for the 'schedule' event.
     *
     * @param ITip\Message $iTipMessage
     * @return void
     */
    function schedule(ITip\Message $iTipMessage) {

        // Not sending any emails if the system considers the update
        // insignificant.
        if (!$iTipMessage->significantChange) {
            if (!$iTipMessage->scheduleStatus) {
                $iTipMessage->scheduleStatus = '1.0;We got the message, but it\'s not significant enough to warrant an email';
            }
            return;
        }

        setlocale(LC_TIME, "de_DE.UTF-8");

        $timestampStart = strtotime($iTipMessage->message->VEVENT->DTSTART);

        $summary = $iTipMessage->message->VEVENT->SUMMARY;
        $summary .= ' ' . strftime("- %a, %d. %B %Y %H:%M (%Z)", $timestampStart);

        if (parse_url($iTipMessage->sender, PHP_URL_SCHEME) !== 'mailto')
            return;

        if (parse_url($iTipMessage->recipient, PHP_URL_SCHEME) !== 'mailto')
            return;

        $replyTo = substr($iTipMessage->sender, 7);
        $recipient = substr($iTipMessage->recipient, 7);

        $from = $this->senderEmail;

        if ($iTipMessage->senderName) {
            $replyTo = $iTipMessage->senderName . ' <' . $replyTo . '>';
            $from = $iTipMessage->senderName . ' <' . $from . '>';
        }
        if ($iTipMessage->recipientName) {
            $recipient = $iTipMessage->recipientName . ' <' . $recipient . '>';
        }

        $subject = 'SabreDAV iTIP message';
        switch (strtoupper($iTipMessage->method)) {
            case 'REPLY' :
                $subject = 'Re: [Termin] ' . $summary;
                break;
            case 'REQUEST' :
                $subject = '[Termin] Einladung: ' . $summary;
                break;
            case 'CANCEL' :
                $subject = '[Termin] Abgesagt: ' . $summary;
                break;
        }

        $headers = [
            'Reply-To: ' . $replyTo,
            'From: ' . $from,
            'Content-Type: text/calendar; charset=UTF-8; method=' . $iTipMessage->method,
        ];
        if (DAV\Server::$exposeVersion) {
            $headers[] = 'X-Sabre-Version: ' . DAV\Version::VERSION;
        }
        $this->mail(
            $recipient,
            $subject,
            $iTipMessage->message->serialize(),
            $headers
        );
        $iTipMessage->scheduleStatus = '1.1; Scheduling message is sent via iMip';

    }
}