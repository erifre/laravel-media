<?php

namespace Optix\Media;

use Optix\Media\Models\Media;
use Intervention\Image\ImageManager;

class ImageManipulator
{
    /**
     * @var ConversionRegistry
     */
    protected $conversionRegistry;

    /**
     * @var ImageManager
     */
    protected $imageManager;

    /**
     * Create a new ImageManipulator instance.
     *
     * @param  ConversionRegistry  $conversionRegistry
     * @param  ImageManager  $imageManager
     * @return void
     */
    public function __construct(ConversionRegistry $conversionRegistry, ImageManager $imageManager)
    {
        $this->conversionRegistry = $conversionRegistry;

        $this->imageManager = $imageManager;
    }

    /**
     * Perform the specified conversions on the given media item.
     *
     * @param  Media  $media
     * @param  array  $conversions
     * @param  bool  $onlyIfMissing
     * @return void
     */
    public function manipulate(Media $media, array $conversions, $onlyIfMissing = true)
    {
        if (! $media->isOfType('image')) {
            return;
        }

        foreach ($conversions as $conversion) {

            // Handle params
            if (is_array($conversion)) {
              $conversionPath = $conversion[1];
              $args = array_slice($conversion, 2);
              $conversion = $conversion[0];
              $onlyIfMissing = false;
            }
            else {
              $args = [];
              $conversionPath = $conversion;
            }

            $path = $media->getPath($conversionPath);

            if ($onlyIfMissing && $media->filesystem()->exists($path)) {
                continue;
            }

            $converter = $this->conversionRegistry->get($conversion);

            array_unshift($args, $this->imageManager->make($media->getFullPath()));

            $image = call_user_func_array($converter, $args);

            $media->filesystem()->put($path, $image->stream());
        }
    }
}
