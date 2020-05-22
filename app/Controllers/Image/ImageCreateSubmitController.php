<?php 

namespace Controllers\Image;

use Request\Request;

use Services\PhotoService;
use Validation\Validator;

class ImageCreateSubmitController {

    private $basePath;
    private $request;
    private $photoService;
    private $validator;

    public function __construct(string $basePath, Request $request, PhotoService $photoService, Validator $validator) {
        $this->basePath = $basePath;
        $this->validator = $validator;
        $this->request = $request;
        $this->photoService = $photoService;
    }

    public function submit() {
        
        $targetDir = $this->basePath. "/storage/";        
        try {
            $title = $this->request->getParam("title");
            $file = $this->request->getFile("file");
            $violations = $this->validate($this->request);
            if (count($violations) !== 0) {
                $this->request->getSession()->put("violations", $violations);
                return [
                    "redirect:/image/add", []
                ];
            }
            switch($file->error()) {
                case UPLOAD_ERR_OK:
                    break;
                case UPLOAD_ERR_NO_FILE:
                    throw new \RuntimeException("No file sent.");
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new \RuntimeException("Exceeded filesize limit.");
                default:
                    throw new \RuntimeException("Unknown errors.");
            }
            $targetFile = uniqid($targetDir, true). ".png";
            $check = getimagesize($file->getTemporaryName());
            if ($check !== false) {
                $file->moveTo($targetFile);
                $photo = $this->photoService->createImage($title, "/private/" . basename($targetFile));
                // redirect to created image
                return [
                    "redirect:/image/" . $photo->getId(), [
                    ]
                ];    
            } else {
                throw new \RuntimeException("File is not an image!");
            }
            
        } catch (\RuntimeException $ex) {
            logMessage("ERROR", $ex->getMessage());
            // put some error flag to session
            return [
                "redirect:/image/add", []
            ];

        }
        
    }

    private function validate(Request $request) {
        return $this->validator->validate([
            $request->getParam("title") => "required|min:5|max:255"
        ]);
    }

}