<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 10/15/2018
 * Time: 6:43 AM
 */

namespace Tops\services;


use Tops\sys\TConfiguration;
use Tops\sys\TLanguage;
use Tops\sys\TPath;

class TUploadHelper
{

    /*
     * Array
(
    [name] => 20170831_143308.jpg
    [type] => image/jpeg
    [tmp_name] => C:\wamp\tmp\phpD129.tmp
    [error] => 0
    [size] => 2171676
)
     */


    public static function filesReady(IMessageContainer $client) {
        global $_FILES;
        $fileNames = array();
        if (!isset($_FILES)) {
            return 0;
        }
        $files = $_FILES;
        $fileCount = ($files ? count($files) : 0);
        if ($fileCount) {
            foreach ($files as $file) {
                $error = $file['error'];
                if ($error > 0) {
                    // See http://www.php.net/manual/en/features.file-upload.errors.php
                    $client->addErrorMessage('document-error-upload-' . ($error > 8 ? '0' : $error));
                    continue;
                }
                $fileNames[] = $file['name'];
            }
        }
        return $fileNames;
    }

    public static function openFile(IMessageContainer $client) {
        global $_FILES;
        $uploaded = array();
        if (!$_FILES) {
            return $uploaded;
        }

        $files = $_FILES;
        $fileCount = ($files ? count($files) : 0);
        if ($fileCount == 0) {
            return false;
        }
        foreach ($files as $file) {
            $tmpName = $file['tmp_name'];
            $error = $file['error'];
            if ($error > 0) {
                // See http://www.php.net/manual/en/features.file-upload.errors.php
                $client->addErrorMessage('document-error-upload-' . ($error > 8 ? '0' : $error));
                // unlink($file['tmp_name']); not needed?
                return false;
            }
            return file($tmpName);
        }
        return false;
    }

    public static function upload(IMessageContainer $client, $destinationFolder, $rename=null, $normalize = true, $incrementDuplicates = true)
    {
        global $_FILES;
        $uploaded = array();
        if (!$_FILES) {
            return $uploaded;
        }

        $files = $_FILES;
        $fileCount = ($files ? count($files) : 0);

        if ($fileCount) {
            // $destinationFolder = TPath::fromFileRoot('application/documents');
            // if (isset($request->folder)) {
            // $destinationFolder .= '/'.$request->folder;
            if (!is_dir($destinationFolder)) {
                mkdir($destinationFolder, 0664, true);
            }
            // }

            foreach ($files as $file) {
                $error = $file['error'];
                if ($error > 0) {
                    // See http://www.php.net/manual/en/features.file-upload.errors.php
                    $client->addErrorMessage('document-error-upload-' . ($error > 8 ? '0' : $error));
                    // unlink($file['tmp_name']); not needed?
                    continue;
                }

                $fileName = $file['name'];
                if ($normalize) {
                    $fileName = TPath::normalizeFileName($fileName);
                }

                $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                $allowed = ',' . TConfiguration::getValue('filetypes', 'documents', 'pdf,doc,docx,txt,csv,mht,jpg,gif,png') . ',';
                if (strpos($allowed, ',' . $ext . ',') === FALSE) {
                    $msg = TLanguage::formatText('document-error-bad-filetype', $file['name']);
                    $client->addErrorMessage($msg, true);
                    unlink($file['tmp_name']);
                    continue;
                };

                $tmpName = $file['tmp_name'];
                $destinationFileName = empty($rename) ? $fileName : $rename;
                $destination = $destinationFolder . '/' . $destinationFileName;
                if ($incrementDuplicates) {
                    $destinationFileName = TPath::incrementFileName($destinationFolder,$destinationFileName);
                    $destination = $destinationFolder . '/' . $destinationFileName;
                }
                else if (file_exists($destination)) {
                    unlink($destinationFolder . '/' .$fileName);
                }

                $result = move_uploaded_file($tmpName, $destination);

                if ($result === TRUE) {
                    $uploaded[] = $fileName;
                } else {
                    $client->addErrorMessage(
                        'Unable to save the file: ' . $destination
                    );
                    unlink($file['tmp_name']);
                }
            }
            return $uploaded;

        }
    }
}