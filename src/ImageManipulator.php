<?php

namespace Optix\Media;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Intervention\Image\ImageManager;
use Optix\Media\Exceptions\InvalidConversion;
use Optix\Media\Models\Media;

use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;

class ImageManipulator
{
    /** @var ConversionRegistry */
    protected $conversionRegistry;

    /** @var ImageManager */
    protected $imageManager;

    /**
     * Create a new manipulator instance.
     *
     * @param ConversionRegistry $conversionRegistry
     * @param ImageManager $imageManager
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
     * @param Media $media
     * @param array $conversions
     * @param bool $onlyIfMissing
     * @return void
     *
     * @throws InvalidConversion
     * @throws FileNotFoundException
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

            $filesystem = $media->filesystem();

            if ($onlyIfMissing && $filesystem->exists($path)) {
                continue;
            }

            $converter = $this->conversionRegistry->get($conversion);

            array_unshift($args, $this->imageManager->make($media->getFullPath()));

            $image = call_user_func_array($converter, $args);

            $media->filesystem()->put($path, $image->stream());

            if (class_exists('ImageOptimizer')) {
              ImageOptimizer::optimize($media->getFullPath($conversionPath));
            }
        }
    }
}
