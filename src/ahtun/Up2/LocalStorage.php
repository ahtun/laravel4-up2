<?php namespace ahtun\Up2;

use Closure;
use Imagine\Image\Box;
use Imagine\Gd\Imagine;
use Imagine\Image\Point;
use Imagine\Image\ImageInterface;

class LocalStorage extends StoreAbstract implements StoreInterface {

    /**
     * Open the location path.
     *
     * $name don't need to include path.
     *
     * @param   string  $name
     * @return  Attach
     */
    public function open($name)
    {
        $location = $node['location'];

        // Generate a result to use as a master file.
        $result = $this->results($location);

        $this->master = $result;

        return $this;
    }

    /**
     * Generate a view link.
     *
     * @param   string  $path
     * @return  string
     */
    public function url($path)
    {
        return $this->config['baseUrl'].$path;
    }

    /**
     * Uplaod a file to destination.
     *
     * @return Attach
     */
    public function upload()
    {
        // Find a base directory include appended.
        $path = $this->path($this->config['baseDir']);

        // Method to upload.
        $method = 'doUpload';

        switch ($this->config['type'])
        {
            case 'base64' : $method = 'doBase64'; break;
            case 'remote' : $method = 'doTransfer'; break;
            case 'detect' :

                if (preg_match('|^http(s)?|', $this->file))
                {
                    $method = 'doTransfer';
                }
                elseif (preg_match('|^data:|', $this->file))
                {
                    $method = 'doBase64';
                }

                break;
        }

        // Call a method.
        $result = call_user_func_array(array($this, $method), array($this->file, $path));

        // If uploaded set a master add fire a result.
        if ($result !== false)
        {
            $this->master = $result;
            $this->addResult($result);
        }

        // Reset values.
        $this->reset();

        return $this;
    }

    /**
     * Upload from a file input.
     *
     * @param   SplFileInfo  $file
     * @param   string       $path
     * @return  mixed
     */
    public function doUpload($file, $path)
    {
        if ( ! $file instanceof \SplFileInfo)
        {
            $file = $this->request->file($file);
        }

        // Original name.
        $origName = $file->getClientOriginalName();

        // Generate a file name with extension.
        $fileName = $this->name($origName);

        // Fix for some system can't access tmp file by buagern@buataitom.com
        $file->move($path, $fileName);
        $uploadedFile = $path.$fileName;
        // End fixed
/*
        // Use Imagine to reduce size and quality depend on config.
        $options = array(
            'jpeg_quality'          => array_get($this->config, 'quality.jpeg', 90),
            'png_compression_level' => array_get($this->config, 'quality.png', 90) / 10,
        );

        $imagine = new Imagine();

        // Fix for some system can't access tmp file by buagern@buataitom.com
        $image = $imagine->open($uploadedFile);
        // End fixed

        $image->interlace(ImageInterface::INTERLACE_PLANE);

        $uploadPath = $path.$fileName;

        if ($image->save($uploadPath, $options))
        {
            return $this->results($uploadPath);
        }

        return false;
*/
return $this->results($path.$fileName);
    }

    /**
     * Upload from a remote URL.
     *
     * @param   string  $file
     * @param   string  $path
     * @return  mixed
     */
    public function doTransfer($url, $path)
    {
        // Craete upload structure directory.
        if ( ! is_dir($path))
        {
            mkdir($path, 0777, true);
        }

        // Original name.
        $origName = basename($url);

        // Strip query string by buagern.
        $origName = preg_replace('/\?.*/', '', $origName);

        // Generate a file name with extension.
        // $filename = $this->name($url);
        // Fixed by buagern
        $filename = $this->name($origName);

        // Get file binary.
        $ch = curl_init();

        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_HEADER, 0);
        curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT,120);
        curl_setopt ($ch, CURLOPT_TIMEOUT,120);

        // Response returned.
        $bin = curl_exec($ch);

        curl_close($ch);

        // Path to write file.
        $uploadPath = $path.$filename;

        if ($this->files->put($uploadPath, $bin))
        {
            return $this->results($uploadPath);
        }

        return false;
    }

    /**
     * Upload from base64 image.
     *
     * @param  string $base64
     * @param  string $path
     * @return mixed
     */
    public function doBase64($base64, $path)
    {
        // Craete upload structure directory.
        if ( ! is_dir($path))
        {
            mkdir($path, 0777, true);
        }

        $base64 = trim($base64);

        // Check pattern.
        if (preg_match('|^data:image\/(.*?);base64\,(.*)|', $base64, $matches))
        {
            $bin = base64_decode($matches[2]);

            $extension = $matches[1];

            $origName = 'base64-'.time().'.'.$extension;

            $filename = $this->name($origName);

            // Path to write file.
            $uploadPath = $path.$filename;

            if ($this->files->put($uploadPath, $bin))
            {
                return $this->results($uploadPath);
            }
        }

        return false;
    }

    /**
     * Generate file result format.
     *
     * @param   string  $location
     * @param   string  $scale
     * @return  array
     */
    public function results($location, $scale = null)
    {
        // Scale of original file.
        if (is_null($scale))
        {
            $scale = 'original';
        }

        // Try to get size of file.
        $fileSize = @filesize($location);

        // If cannot get size of file stop processing.
        if (empty($fileSize))
        {
            return false;
        }

        // Is this image?
        $isImage = false;

        // Get pathinfo.
        $pathinfo = pathinfo($location);

        // Append path without base.
        $path = $this->path();

        // Get an file extension.
        $fileExtension = $pathinfo['extension'];

        // File name without extension.
        $fileName = $pathinfo['filename'];

        // Base name include extension.
        $fileBaseName = $pathinfo['basename'];

        // Append path with file name.
        $filePath = $path.$fileBaseName;

        // Get mime type.
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $location);

        // Dimension for image.
        $dimension = null;

        if (preg_match('|image|', $mime))
        {
            $isImage = true;

            $meta = getimagesize($location);

            $dimension = $meta[0].'x'.$meta[1];
        }

        // Master of resized file.
        $master = null;

        if ($scale !== 'original')
        {
            $master = str_replace('_'.$scale, '', $fileName);
        }

        return array(
            'isImage'       => $isImage,
            'scale'         => $scale,
            'master'        => $master,
            'subpath'       => $path,
            'location'      => $location,
            'fileName'      => $fileName,
            'fileExtension' => $fileExtension,
            'fileBaseName'  => $fileBaseName,
            'filePath'      => $filePath,
            'fileSize'      => $fileSize,
            'url'           => $this->url($filePath),
            'mime'          => $mime,
            'dimension'     => $dimension
        );
    }

    /**
     * Resize master image file.
     *
     * @param   array   $sizes
     * @return  Attach
     */
    public function resize($sizes = null)
    {
        // A master file to resize.
        $master = $this->master;

        // Master image valid.
        if ( ! is_null($master) and preg_match('|image|', $master['mime']))
        {
            $imagine = new Imagine();
            $image = $imagine->open($master['location']);

            // Path with base dir.
            $path = $this->path($this->config['baseDir']);

            // All scales available.
            $scales = $this->config['scales'];

            // If empty mean generate all sizes from config.
            if (empty($sizes))
            {
                $sizes = array_keys($scales);
            }

            // If string mean generate one size only.
            if (is_string($sizes))
            {
                $sizes = (array) $sizes;
            }

            if (count($sizes)) foreach ($sizes as $size)
            {
                // Scale is not in config.
                if ( ! array_key_exists($size, $scales)) continue;

                // Get width and height.
                list($w, $h) = $scales[$size];


                // Path with the name include scale and extension.
                $uploadPath = $path.$master['fileName'].'_'.$size.'.'.$master['fileExtension'];

                // Use Imagine to make resize and crop.
                $options = array(
                    'jpeg_quality'          => array_get($this->config, 'quality.jpeg', 90),
                    'png_compression_level' => array_get($this->config, 'quality.png', 90) / 10,
                );

                $image->thumbnail(new Box($w, $h), 'outbound')
                      ->interlace(ImageInterface::INTERLACE_PLANE)
                      ->save($uploadPath, $options);

                // Add a result and fired.
                $result = $this->results($uploadPath, $size);

                // Add a result.
                $this->addResult($result);
            }
        }

        return $this;
    }

    /**
     * Remove master image file.
     *
     * @return  Attach
     */
    public function remove()
    {
        $master = $this->master;

        $stacks = array();

        if ( ! is_null($master))
        {
            $location = $master['location'];

            $this->files->delete($location);

            // Fire a result to callback.
            $onRemove = $this->config['onRemove'];

            if ($onRemove instanceof Closure)
            {
                $onRemove($master);
            }
        }

        return $this;
    }

}