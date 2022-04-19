<?php

namespace bgmorton\LaravelExifStripper\Http\Middleware;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
// use Illuminate\Support\Facades\Log;

use Closure;

class LaravelExifStripperMiddleware
{

    /**
     * @param  array  $array
     * @return array
     */
    function array_flatten(array $array)
    {
        $return = array();
        array_walk_recursive($array, function ($a) use (&$return) {
            $return[] = $a;
        });
        return $return;
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Get values from config
        $enabled = config('laravel-exif-stripper.enabled', FALSE);
        $binary = config('laravel-exif-stripper.exiftool_binary', 'exiftool');
        // Place it in an array as array_merge() will be used later to generate the command with arguments
        $binary = [$binary];

        if ($enabled) {

            foreach ($this->array_flatten($request->files->all()) as $file) {

                if(!$file->isValid()){
                    continue;
                }

                // I assume that when Laravel's file storage isn't local (ie using S3 etc) the uploaded file is still stored in the PHP temp dir after upload, so EXIF can be removed during that stage, before it is moved to it's final storage location
                $tempFilePath = $file->getPathName();

                // List of file extensions supported by ExifTool by running exiftool -listwf
                $supportedExifToolsWriteFormats = "360 3G2 3GP 3GP2 3GPP AAX AI AIT APNG ARQ ARW AVIF CIFF CR2 CR3 CRM CRW CS1 DCP DNG DR4 DVB EPS EPS2 EPS3 EPSF ERF EXIF EXV F4A F4B F4P F4V FFF FLIF GIF GPR HDP HEIC HEIF HIF ICC ICM IIQ IND INDD INDT INSP J2K JNG JP2 JPE JPEG JPF JPG JPM JPS JPX JXL JXR LRV M4A M4B M4P M4V MEF MIE MNG MOS MOV MP4 MPO MQV MRW NEF NKSC NRW ORF ORI PBM PDF PEF PGM PNG PPM PS PS2 PS3 PSB PSD PSDT QT RAF RAW RW2 RWL SR2 SRW THM TIF TIFF VRD WDP X3F XMP";
                // Convert to array
                $supportedExifToolsWriteFormats = explode(" ", $supportedExifToolsWriteFormats);

                // Not the extension of the the temp file but of the original file name
                // https://github.com/symfony/symfony/blob/6.1/src/Symfony/Component/HttpFoundation/File/UploadedFile.php
                $originalFileExtension = $file->guessExtension();

                // The process should ONLY be run if the file type is supported.  Unsupported file types generate an error
                if (!in_array(strtoupper($originalFileExtension), $supportedExifToolsWriteFormats)) {
                    // Skip this loop iteration as it is an unsupported file type
                    continue;
                }

                // Arguments to pass to ExifTool
                $commands = ["-j", "-P", "-m", "-overwrite_original", "-all="]; // would -z also be beneficial?

                // Preserve orientation
                $exif = exif_read_data($tempFilePath);   
                if (!empty($exif["Orientation"])) {
                    $commands[] = "-orientation#=" . $exif["Orientation"];
                }

                $commands[] = $tempFilePath;
                
                // Run ExifTool
                $process = new Process(array_merge($binary, $commands));
                $process->run();

                // If there was an error removing EXIF data from a supported format, the upload is considered a failure and an exception is thrown
                if (!$process->isSuccessful()) {
                    // Log::info('LaravelExifStripper: ' . $process->getErrorOutput());
                    throw new ProcessFailedException($process);
                }

                // $output = $process->getOutput(); // Should be blank or JSON due to the -j flag
                // Log::info('LaravelExifStripper: ' . $process->getErrorOutput());

                // I had considered logging all EXIF removal failures here but the error thrown above should handle any problems

                // Ensure the process is destroyed
                unset($process);
            }
        }

        return $next($request);
    }
}
