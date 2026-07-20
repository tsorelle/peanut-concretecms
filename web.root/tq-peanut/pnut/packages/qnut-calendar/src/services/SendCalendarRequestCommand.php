<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 5/31/2019
 * Time: 9:05 AM
 */

namespace Peanut\QnutCalendar\services;


use Peanut\PeanutMailings\sys\MailTemplateManager;
use Tops\mail\TContentType;
use Tops\mail\TPostOffice;
use Tops\services\TServiceCommand;
use Tops\sys\TConfiguration;
use Tops\sys\TWebSite;

class SendCalendarRequestCommand extends TServiceCommand
{
    /**
     * @throws \Exception
     */
    protected function run()
    {
        $request = $this->getRequest();
        if (empty($request)) {
            $this->addErrorMessage('service-no-request');
            return;
        }

        if(empty($request->requestor)) {
            $this->addErrorMessage('No requestor');
            return;
        }

        if(empty($request->email)) {
            $this->addErrorMessage('No email');
            return;
        }

        $response = new \stdClass();
        $url =  TWebSite::GetSiteUrl().TConfiguration::getValue('calendar','pages','/calendar');
        $content = MailTemplateManager::CreateMessageText('CalendarRequest.html',[
            'requestor' => @$request->requestor,
            'email'     => @$request->email,
            'title'     => @$request->title      ?? '',
            'location'  => @$request->location   ?? '',
            'room'      => @$request->room       ?? '',
            'date'      => @$request->date       ?? '',
            'time'      => @$request->time       ?? '',
            'eventType' => @$request->eventType  ?? '',
            'repeat'    => @$request->repeat     ?? '',
            'committee' => @$request->committee  ?? '',
            'comments'  => @$request->comments   ?? '',
            'calendar-url' => $url
        ]);

        $fromAddress = "$request->requestor <$request->email>";
        $subject = 'Calendar request received from '.$request->requestor;
        TPostOffice::SendMessageToUs(
            $fromAddress,
            $subject,
            $content,
            TContentType::Html,
            'calendar',
            TPostOffice::ContactMailbox
        );

        $this->addInfoMessage('Calendar request sent');
        $this->setReturnValue($response);

    }
}