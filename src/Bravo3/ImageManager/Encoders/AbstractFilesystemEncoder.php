<?php
namespace Bravo3\ImageManager\Encoders;

use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractFilesystemEncoder extends AbstractEncoder
{
    /**
     * @var string[]
     */
    protected $temp_files = [];

    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }

    public function __destruct()
    {
        $this->cleanup();
    }

    /**
     * Get a temporary file and populate its data
     *
     * Any files created with this function will be destroyed on cleanup/destruction.
     *
     * @param string $data   Data to populate file with
     * @param string $prefix Optional file prefix
     * @return string
     */
    protected function getTempFile($data = null, $prefix = 'img-mngr-')
    {
        $file = tempnam(sys_get_temp_dir(), $prefix);
        if ($data) {
            file_put_contents($file, $data);
        } else {
            $this->filesystem->touch($file);
        }

        $this->temp_files[] = $file;
        return $file;
    }

    /**
     * Remove all temp files
     */
    protected function cleanup()
    {
        foreach ($this->temp_files as $file) {
            $this->filesystem->remove($file);
        }
        $this->temp_files = [];
    }
}
