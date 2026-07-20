<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 3/18/2019
 * Time: 12:25 PM
 */

namespace Tops\mail;


use Tops\sys\TStrings;
use Tops\sys\TWebSite;

abstract class TMailgunWebhookHandler
{
    private function handleError($error, $hookRequest = null): void
    {
        // todo: log this maybe?
        $message = "$error";
        if ($hookRequest) {
            $message .= "\nRequest:\n" . var_export($hookRequest, true);
        }
        print "Mail hook error: $message\n";
        // mail('webadmin@austinquakers.org', 'Error in Mailgun webhook', $message);
    }

    private function verify($signature, $webhookKey): bool | string
    {
        // check if the timestamp is fresh
        if (abs(time() - $signature->timestamp) > 15) {
            return false;
        }
        // returns true if signature is valid
        return hash_hmac('sha256', $signature->timestamp . $signature->token, $webhookKey) === $signature->signature;
    }

    private function getReturnCode($eventdata) {

    }

    /**
     * Example payload:
     * {
     *      "event": "failed",
     *      "severity": "permanent",
     *      "timestamp": 1716397562,
     *      "recipient": "user@example.com",
     *      "message": {
     *          "headers": {
     *              "message-id": "<202505221358@example.mailgun.org>"
     *          },
     *          "attachments": [],
     *          "size": 12345
     *       },
     *      "delivery-status": {
     *          "message": "Recipient address does not exist",
     *          "code": 550,
     *          "description": "Mailbox unavailable",
     *          "mx-host": "smtp.example.com"
     *      },
     *      "signature": {
     *          "timestamp": "1716397562",
     *          "token": "abcdef123456",
     *          "signature": "a1b2c3d4e5f6g7h8i9j0"
     *      }
     * }
     *
     * @throws \Exception
     */
    public function handleMessage(): void
    {
        header('X-PHP-Response-Code: 200', true, 200);
        try {
            $mailgunSettings = TMailgunConfiguration::GetSettings();
            if (!$mailgunSettings->valid) {
                throw new \Exception('Mailgun settings not found');
            }
            if (empty($mailgunSettings->apikey)) {
                throw new \Exception('No apikey in mailgun settings');
            }

            $hookRequest = new \stdClass();

            $request_body = file_get_contents('php://input');
            $payload = json_decode($request_body);
            // testing:
            // var_dump($payload);
            $signature = $payload->signature;

            if (!$this->verify($signature, $mailgunSettings->webhookkey)) {
                // todo: might be attack, consider strategies.
                $this->handleError("Unverified message", $payload);
                return;
            }

            // curly braces used because element name contains a dash. This is correct for PHP 8.2
            $eventdata = $payload->{'event-data'};
            $debug = false;
            if ($debug) {
                var_dump_safe( $eventdata);
                var_dump_safe( $eventdata->{"delivery-status"});
                var_dump_safe( $eventdata->{"envelope"});
                var_dump_safe( $eventdata->{"message"});
                var_dump_safe( $eventdata->tags);
                var_dump_safe( $eventdata->flags);
                var_dump_safe( $eventdata->{"user-variables"});
            }

            $sender = $eventdata->envelope->sender ?? null;
            list($senderAddress,$senderDomain) = TStrings::Split($sender,'@');
            $siteDomain = TWebSite::GetDomain();
            $fromUs = strcasecmp ($senderDomain,$siteDomain) == 0;

            // ignore if message did not originate from website.
            // messages recieved via Mailgun lists can cause false bounce reports.
            if ($fromUs) {
                $hookRequest->recipient = $eventdata->recipient ?? null;
                $hookRequest->event = $eventdata->event;
                $severity = $eventdata->severity ?? 'permanent';
                $hookRequest->errorLevel = $severity == 'temporary' ? 2 : 1;
                $hookRequest->messageId = $eventdata->message->headers->{'message-id'} ?? null;
                $status = $eventdata->{'delivery-status'} ?? null;
                if ($status) {
                    $hookRequest->description = $status->description ?? 'No description';
                    $hookRequest->code = $status->code ?? '';
                    $hookRequest->message = $status->message ?? '';
                }
                else {
                    $hookRequest->description = 'No description';
                    $hookRequest->code = 'Not found';
                    $hookRequest->message = '';
                }
                $this->handleEvent($hookRequest);
            }

        } catch (\Exception $ex) {
            $error = $ex->getMessage() . ' ' . "(" . $ex->getCode() . ': ' . $ex->getFile() . ' @ ' . $ex->getLine() . "\n" . $ex->getTraceAsString();
            $this->handleError($error);
            throw $ex;
        }
        exit;
    }

    public abstract function handleEvent($hookRequest);

}