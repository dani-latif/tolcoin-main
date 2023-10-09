<?php

namespace App\Http\Controllers;


use Aws\Credentials\Credentials;
use Aws\Exception\MultipartUploadException;
use Aws\S3\ObjectUploader;
use Aws\S3\PostObjectV4;
use Aws\S3\S3Client;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use League\Flysystem\Config;
use Mockery\Exception;

/**
 * @Author Suleman Khan <sulaman@sulaman.pk>
 * Creation Date: 13-Aug-2020
 * Purpose: This class is responsible all AWS process handling in it.
 * */
class SignedUrlUploadController extends Controller
{
    const BucketName = 'b4uglobaltmp';
    public $AwsS3Client;
    const S3FileUploadACL = 'public-read';

    public function __construct()
    {
        $credentials = new Credentials(
            config('b4uglobal.AWS_KEY'),
            config('b4uglobal.AWS_SECRET')
        );

        $this->AwsS3Client = new S3Client([
            'region' => config('b4uglobal.AWS_REGION'),
            'version' => config('b4uglobal.AWS_VERSION'),
            'credentials' => $credentials,
            'options' => [],
        ]);
    }

    /*
     * ======================= AWS
     * */

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getPostObjectUrl(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filename' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->getMessageBag()]);
        }
        $formInputs = ['acl' => self::S3FileUploadACL, 'key' => $request->get('filename')];
        $options = [
            ['acl' => self::S3FileUploadACL],
            ['bucket' => self::BucketName],
            ['Key' => $request->get('filename')]

        ];
        $expires = '+1 hours';
        $postObject = new \Aws\S3\PostObjectV4(
            $this->AwsS3Client,
            self::BucketName,
            $formInputs,
            $options,
            $expires
        );


        $formInputs = [];
        foreach ($postObject->getFormInputs() as $key => $value) {
            $formInputs[strtr($key, '-', '_')] = $value;
        }

        return response()->json(
            ['status' => true, 'message' => 'url found', 'result' => ['formInputs' => $formInputs, 'formAttributes' => $postObject->getFormAttributes()]]
        );


    }

    /**
     * @param $objectKey
     * @return JsonResponse
     */
    public function getObjectUrl($objectKey)
    {
        try {
            $objectUrl = $this->AwsS3Client->getObjectUrl(self::BucketName, $objectKey);
            return response()->json(['status' => true, 'message' => 'url found', 'result' => ['url' => $objectUrl]]);
        } catch (Exception $exception) {
            return response()->json(['status' => false, 'message' => $exception->getMessage()]);
        }
    }

    /**
     * Purpose:: Delete Object
     * @param $objectKeyName
     */
    public function deleteObject($objectKeyName)
    {
        $this->AwsS3Client->deleteObject([
            'Bucket' => self::BucketName,
            'Key' => $objectKeyName
        ]);

    }

    ## Direct upload file from server to aws server.
    public static function uploadFileFromServerToAWS(string $filePath, string $filename)
    {
      $thiss = (new self());
            $filePath = fopen($filePath, 'rb');
            $uploader = new ObjectUploader(
             $thiss->AwsS3Client,
             self::BucketName,
             $filename,
             $filePath
         );
        $command =$thiss->AwsS3Client->getCommand('GetObject', [
            'Bucket' => self::BucketName,
            'Key'    => $filename,
        ]);

    //   $signedURL =  $thiss->AwsS3Client->getObjectUrl(self::BucketName,$filename,'+10 minutes');

        /*Signed Url from FileSystem*/
      /*  $s3 = Storage::disk('s3');
        dd($s3);
        $client =$s3->getDriver()->getAdapter()->getClient();
        $expiry = "+10 minutes";

        $command = $client->getCommand($filename, [
            'Bucket' => 'b4uglobaltmp',
            'Key'    => "AKIARKSNHGNWRF27R2IQ",
        ]);

        $request = $client->createPresignedRequest($command, $expiry);
        $signedURL = (string) $request->getUri();*/
        /* /// Signed Url from FileSystem *****/

        try {
            $result = $uploader->upload();
            //  $objectUrl = $thiss->AwsS3Client->getObjectUrl(self::BucketName, $filename);

                 $request = $thiss->AwsS3Client->createPresignedRequest($command, "+3 days");
                 $signedURL = (string) $request->getUri();
            //dd($signedURL);
//            $formInputs = ['acl' => self::S3FileUploadACL, 'key' => $filename];
//            $options = [
//                ['acl' => self::S3FileUploadACL],
//                ['bucket' => self::BucketName],
//                ['Key' => $filename]
//            ];
//            $expires = '+10 minutes';
//            $postObject = new \Aws\S3\PostObjectV4(
//                $thiss->AwsS3Client,
//                self::BucketName,
//                $formInputs,
//                $options,
//                $expires
//            );
//            $objectUrl = $thiss->AwsS3Client->getObjectUrl(self::BucketName, $filename);
           /* if ($result["@metadata"]["statusCode"] == '200') {
                return [
                    'status' => true,
                    'object' => $result,
                    'url' => $result["ObjectURL"]
                ];
            } */
        if($signedURL) {
            return [
                'status' => true,
                'url' => $signedURL
            ];
        }else {
                return [
                    'status' => false,
                    'object' => $result,
                    'url' => null
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => false,
                'errorMsg' => $e->getMessage(),
                'object' => $e,
                'url' => null
            ];
        }
    }

    /*
     * ======================= GOOGLE CLOUD
     * */

    public function fileStoreTempDir()
    {
        $path = storage_path('app/temp') . DIRECTORY_SEPARATOR;
        if (!File::isDirectory($path)) {
            File::makeDirectory($path);
        }

        return $path;
    }

    /**
     * @param $fileUrl
     * @param string $cloudUploadPath
     * @return array|\Illuminate\Http\RedirectResponse
     * Purpose Upload Image to Google Cloud and clean storage.
     */
    public static function uploadImageToGoogleCloud($fileUrl, $cloudUploadPath = 'uploads')
    {
        if (empty($fileUrl)) {
            return [
                'status' => false,
                'message' => 'File name is empty'
            ];
        }
        try {
            // temporary upload images to temp folder
            ## image name
            $imageName = time() . '.png';
            ## image path
            $imagePath = (new self())->fileStoreTempDir() . $imageName;
            imagepng(imagecreatefromstring(file_get_contents($fileUrl)), $imagePath);
            //    $newlyCreatedScannedImage = $imagePath;

            $newlyCreatedScannedImage = storage_path('app/temp') . DIRECTORY_SEPARATOR . 'scannedImage.png';

            // File and new size of image to scan is this image or not
            $percent = 0.9;
            list($width, $height) = getimagesize($imagePath);
            $newWidth = $width * $percent;
            $newHeight = $height * $percent;
            $thumb = imagecreatetruecolor($newWidth, $newHeight);

            $source = imagecreatefrompng($imagePath);
            imagecopyresized($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagepng($thumb, $newlyCreatedScannedImage);

            $path = Storage::disk('gcs')->putFile(DIRECTORY_SEPARATOR . $cloudUploadPath, $newlyCreatedScannedImage, Filesystem::VISIBILITY_PUBLIC);

            // delete temp file
            is_file($imagePath) ? unlink($imagePath) : null;
            // after upload delete the scanned image
            is_file($newlyCreatedScannedImage) ? unlink($newlyCreatedScannedImage) : null;

            $imageName = str_replace($cloudUploadPath . DIRECTORY_SEPARATOR, '', $path);

            return [
                'status' => true,
                'message' => 'File uploaded',
                'result' => [
                    'imageName' => $imageName,
                    'imagePath' => $path
                ]
            ];

        } catch (Exception $exception) {
            return [
                'status' => false,
                'message' => $exception->getMessage()
            ];
        }

    }

}