<?php

namespace Peanut\content\services;

use Tops\services\TUploadHelper;
use Tops\sys\TPath;

/**
 * Service contract
 *  Request
 *    interface IImageUploadRequest {
 *        filename: string;
 *        imageurl: string;
 *    }
 *
 *    files: in post
 */
class UploadImageCommand extends \Tops\services\TServiceCommand
{

    protected function run()
    {
        $fileNames = TUploadHelper::filesReady($this->getMessages());
        if ($this->hasErrors()) {
            return;
        }
        $fileCount = count($fileNames);
        if ($fileCount) {
            $uploadFile = $fileNames[0];
        }

        $request = $this->getRequest();
        if (!$request) {
            $this->addErrorMessage('service-no-request');
            return;
        }
        if (!isset($request->filename)) {
            $this->addErrorMessage('No file name received');
            return;
        }
        if (!isset($request->imageurl)) {
            $this->addErrorMessage('No image url received');
            return;
        }

        $imagePath = TPath::fromFileRoot("$request->imageurl");
        $imageFilePath = "$imagePath/$request->filename";
        if (file_exists($imageFilePath)) {
            unlink($imageFilePath);
        }
        $uploadedFiles = TUploadHelper::upload($this->getMessages(), $imagePath,$request->filename);
        if ($this->hasErrors()) {
            return;
        }
        if (empty($uploadedFiles)) {
            $this->addErrorMessage('SYSTEM ERROR: Cannot get uploaded file');
            return;
        }
        $this->addInfoMessage('Image file uploaded');
    }
}