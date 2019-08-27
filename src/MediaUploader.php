<?php

namespace Optix\Media;

use InvalidArgumentException;
use Illuminate\Http\UploadedFile;

class MediaUploader
{
    /** @var UploadedFile */
    protected $file;

    /** @var string */
    protected $name;

    /** @var string */
    protected $fileName;

    /** @var array */
    protected $attributes = [];

    /** @var string */
    protected $visibility = self::VISIBILITY_PUBLIC;

    /** @var string */
    const VISIBILITY_PUBLIC = 'public';

    /** @var string  */
    const VISIBILITY_PRIVATE = 'private';

    /**
     * Create a new uploader instance.
     *
     * @param UploadedFile $file
     * @return void
     */
    public function __construct(UploadedFile $file)
    {
        $this->setFile($file);
    }

    /**
     * @param UploadedFile $file
     * @return self
     */
    public static function fromFile(UploadedFile $file)
    {
        return new static($file);
    }

    /**
     * Set the source file.
     *
     * @param UploadedFile $file
     * @return self
     */
    public function setFile(UploadedFile $file)
    {
        $this->file = $file;

        $fileName = $file->getClientOriginalName();
        $name = pathinfo($fileName, PATHINFO_FILENAME);

        $this->setName($name);
        $this->setFileName($fileName);

        return $this;
    }

    /**
     * Set the name of the media item.
     *
     * @param string $name
     * @return self
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $name
     * @return self
     */
    public function useName(string $name)
    {
        return $this->setName($name);
    }

    /**
     * Set the name of the file.
     *
     * @param string $fileName
     * @return self
     */
    public function setFileName(string $fileName)
    {
        $this->fileName = $this->sanitiseFileName($fileName);

        return $this;
    }

    /**
     * @param string $fileName
     * @return self
     */
    public function useFileName(string $fileName)
    {
        return $this->setFileName($fileName);
    }

    /**
     * Sanitise the given file name.
     *
     * @param  string  $fileName
     * @return self
     */
    protected function sanitiseFileName(string $fileName)
    {
        return str_replace(['#', '/', '\\', ' '], '-', $fileName);
    }

    /**
     * Set any additional attributes to be saved on the media item.
     *
     * @param array $attributes
     * @return self
     */
    public function withAttributes(array $attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * @param array $properties
     * @return self
     */
    public function withProperties(array $properties)
    {
        return $this->withAttributes($properties);
    }

    /**
     * Set the file visibility.
     *
     * @param string $visibility
     * @return self
     */
    public function setVisibility(string $visibility)
    {
        if (! in_array($visibility, [
            $public = self::VISIBILITY_PUBLIC,
            $private = self::VISIBILITY_PRIVATE,
        ])) {
            throw new InvalidArgumentException(
                "The given visibility must be either `{$public}` or `{$private}`."
            );
        }

        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Upload the file and create a media item.
     *
     * @return mixed
     */
    public function upload()
    {
        $model = config('media.model');

        $media = new $model();

        $media->name = $this->name;
        $media->file_name = $this->fileName;
        $media->disk = config('media.disk');
        $media->mime_type = $this->file->getMimeType();
        $media->size = $this->file->getSize();

        $media->forceFill($this->attributes);

        $media->save();

        $media->filesystem()->putFileAs(
            $media->getDirectory(),
            $this->file,
            $this->fileName,
            $this->visibility
        );

        return $media;
    }
}
