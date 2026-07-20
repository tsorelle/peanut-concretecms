<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 1/15/2018
 * Time: 10:50 AM
 */

namespace Tops\services;

class DownloadServiceFactory extends TAbstractServiceFactory
{

    private static $instance;

    /**
     * @return string|TServiceResponse
     * @throws \Exception
     */
    public static function Execute()
    {
        if (!isset(self::$instance)) {
            self::$instance = new DownloadServiceFactory();
        }
        return self::$instance->executeService(false); // no security token check
    }

    /**
     * @throws \Exception
     */
    public static function PrintOutput() {
        $response = self::Execute();
        if (empty($response) || empty($response->Value) || (!isset($response->Value->data))) {
            throw new \Exception('Error invalid download response.');
        }
        $fileName = empty($response->Value->filename) ? 'download' : $response->Value->filename;
        if ($response->Result === ResultType::Errors) {
            $errors = [];
            $errors[] = '"Message","Cannot download. Errors occurred"';
            foreach ($response->Messages as $message) {
                switch ($message->MessageType) {
                    case MessageType::Error :
                        $type = 'Error';
                        break;
                    case MessageType::Warning :
                        $type = 'Warning';
                        break;
                    default:
                        $type = 'Unknown';
                        break;
                        // continue;
                }
                $errors[] = sprintf('"%s","%s"',$type,$message->Text);
                $data = join("\n",$errors);
            }
        }
        else {
            if (empty($response->Value->data)) {
                $data = 'No data returned';
            }
            else {
                $data = $response->Value->data;
                if (is_array($data)) {
                    $data = join("\n",$data);
        }
            }
        }
        $disposition = sprintf('Content-Disposition: attachment; filename="%s.csv";',$fileName);
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private",false);
        header("Content-Type: application/octet-stream");
        header($disposition);
        header("Content-Transfer-Encoding: binary");
        print $data;
    }
}