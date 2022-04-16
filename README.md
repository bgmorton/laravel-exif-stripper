# bgmorton/laravel-exif-stripper

Laravel middleware to to strip EXIF data from uploaded files.

Metadata exists in more than just JPEGs now - this tool uses ExifTool to strip from all supported formats.

All credit to, and all information on this functionality available at:

https://exiftool.org/

...this code just executes ExifTool on supported uploaded files while they are sitting in temp storage :)

## Requirements

This package relies on ExifTool.  

    sudo apt install libimage-exiftool-perl

The command 'exiftool' must be available on the command line.

## Installation Instructions

*Why is this package not this in Packagist? Because I cant guarantee I'll maintain it - fork it, use it, and then if it breaks you can fix it.*

To install, add the following to your composer.json:

    {
        "repositories": [
            {
                "url": "https://github.com/bgmorton/laravel-exif-stripper.git",
                "type": "git"
            }
        ]
    }

And then run the following to install:

    composer require bgmorton/laravel-exif-stripper

Publish config:

    php artisan vendor:publish --provider="bgmorton\LaravelExifStripper\LaravelExifStripperServiceProvider" --tag="config"

## Notes

- An error will be thrown on all but minor ExifTool errors.

## Todo

- Option to dump EXIF data to a text file