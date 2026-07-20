<?php

namespace Tops\mail;

use Mailgun\Mailgun;
use Tops\db\model\entity\EmailFailure;
use Tops\sys\TWebSite;

class TMailgunLogs
{

    private static $instance;
    private static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new TMailgunLogs();
        }
        return self::$instance;
    }

    private static $domain;
    public static function SetDomain(string $domain) {
        self::$domain = $domain;
    }

    public static function GetDomain($default = null) {

        if (!isset(self::$domain)) {
            self::$domain = TWebSite::getDomain();
            if ($default != null && (self::$domain = 'localhost' || str_starts_with(self::$domain, 'local.'))) {
                self::$domain = $default;
            }
        }
        return self::$domain;
    }

    public static function MailgunEventToLogEntry($event)
    {
        $eventData = new EmailFailure;
        $eventData->eventId = $event->getId();
        $eventData->event = $event->getEvent();
        $eventDateTime = $event->getEventDate();
        $eventData->reportedDate = $eventDateTime->format('Y-m-d H:i:s');
        $eventData->errorlevel = $event->getSeverity() == 'permanent' ? 1 : 2;

        $eventData->recipient = $event->getRecipient();
        $envelope = $event->getEnvelope();
        $message = $event->getMessage();
        $status = $event->getDeliveryStatus();

        $eventData->smtpCode =  sprintf('%03d', $status['code'] ??  0);
        $statusDescription = $status['description']  ?? '';
        $statusMessage = $status['message'] ?? '';

        // Codes 6xx indicate a custom mailgun error message, and usually it's status message is blank and
        // the description field can be used instead
        $eventData->statusMessage = str_starts_with($eventData->smtpCode,'6') && empty($statusMessage) ?
            $statusDescription :
            $statusMessage;
        $eventData->attemptNumber = $status['attempt-no']  ?? 0;
        $eventData->sender = $envelope['sender']  ?? '';
        $headers = $message['headers'] ?? [];
        $eventData->messageId = $headers['message-id']  ?? '';
        $eventData->subject = $headers['subject']  ?? '';
        $eventData->from = $headers['from'] ?? '';
        $eventData->to = $headers['to'] ?? '';
        // test code
        /*
        if ($eventData->eventId == 'skH7sT49SQaXRY8f10TQ5g') {
            $eventData->smtpCode = '450';
            $eventData->errorlevel = 1;
        }
        */
        // end
        $eventData->category = self::getFailureCategory($eventData);



        return $eventData;
    }

    /**
     * Triage error based on Smtp erro code
     *
     * @param EmailFailure $event
     * @return string
     */
    private static function getFailureCategory(EmailFailure $event) : string
    {
        switch ($event->smtpCode) {
            case '510' :
            case '511' :
            case '512' :
            case '513' :
            case '515' :
            case '521' :
            case '605' :
            case '450' :
                return EmailFailure::bounce; // problem with recipient address, or other unfixable issue
            case '523' :
            case '556' :
                return EmailFailure::message; // problem with message
            case '531' :
            case '533' :
            case '540' :
                return EmailFailure::system; // problem with recipient server
            case '420' :
            case '471' :
            case '554':
                return EmailFailure::blocked; // our domain or mailgun server blocked by anti-spam or policy
            case '522' :
            case '552' :
                return EmailFailure::mailbox; // recipient mailbox full or not functioning.
            case '550':
                return (stripos($event->statusMessage,EmailFailure::spam) !== -1 or
                    stripos($event->statusMessage,EmailFailure::blocked) !== -1) ?
                    'blocked' : 'mailbox';
            case '553':
                return (stripos($event->statusMessage,EmailFailure::spam) !== -1 or
                    stripos($event->statusMessage,EmailFailure::blocked) !== -1) ?
                    EmailFailure::blocked : EmailFailure::bounce;
            case '607': // Mailgun, marked as spam
                return EmailFailure::spam;
            default:
                return EmailFailure::unknown;
        }

    }


    public static function Download($startOffset = '-24 hours', $event='failed',$severity=null, $limit = 0) : \stdClass {
        $instance = self::getInstance();
        $params = array();
        if (!empty($event)) {
            $params['event'] = $event;
            if ($event == 'failed' && !empty($severity)) {
                $params['severity'] = $severity;
            }
        }
        if ($limit > 0) {
            $params['limit'] = $limit;
        }
        return $instance->getLogs($startOffset,$params);
    }

    private function getLogs($startOffset = '-24 hours',array $parameters = []) : \stdClass {
        $response = new \stdClass();
        $response->success = false;
        if (!class_exists('Mailgun\Mailgun')) {
            $response->errorMessages[] = 'Mailgun API is not installed.';
            $response->events = [];
            return $response;
        }

        try {
            $mailgunSettings = TMailgunConfiguration::GetSettings();
            if (!$mailgunSettings->valid) {
                $response->errorMessages[] = $mailgunSettings->error;
                return $response;
            }
            if (empty($mailgunSettings->apikey)) {
                $response->errorMessages[] = 'No apikey in mailgun settings';
                return $response;
            }
            if (!class_exists('Mailgun\Mailgun')) {
                $response->errorMessages[] = 'Mailgun API is not installed.';
                return $response;
            }

            $mgClient = Mailgun::create($mailgunSettings->apikey);
            $domain = self::GetDomain('austinquakers.org');
            // uncomment for local testing
            // $domain = 'austinquakers.org';
            $client = $mgClient->events();


            // Set initial parameters
            $today = new \DateTime();
            if (!empty($startOffset)) {
                $today->modify($startOffset);
            }
            $startDate = $today->format(DATE_RFC2822);
            $parameters['begin'] = $startDate;
            $parameters['limit'] = '300';
            $parameters['ascending'] = 'yes';
            // $parameters['severty'] = 'temporary';
            // Fetch first page of results
            // $client = $mgClient->httpClient();
            $mgResponse = $client->get($domain, $parameters);
/*                [
                    // 'event' => 'delivered',
                    'limit' => 100, // $limit,
                    'begin' => $startDate,
                    // 'severity' => 'permanent'
                ]);*/

            $response->events = $mgResponse->getItems();

            /*
            // Store result
                        $events = array_merge($events, $response->http_response_body->items);

            // Paginate through additional pages
                        while (!empty($response->http_response_body->paging->next)) {
                            $nextUrl = $response->http_response_body->paging->next;
                            $response = $mgClient->get($nextUrl);
                            $events = array_merge($events, $response->http_response_body->items);
                        }

            // Print all results
                        print_r($events);*/

        }
        catch (\Exception $e) {
            $response->errorMessages[] = $e->getMessage();
            $response->errorMessages[] = $e->getTraceAsString();
            return $response;
        }

        $response->success = true;
        return $response;

    }

}