<?php

namespace HemantMann\Flysystem\Dropbox;

use League\Flysystem\Config;
use League\Flysystem\Util;
use League\Flysystem\Util\MimeType;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;

use Kunnu\Dropbox\Dropbox as Client;
use Kunnu\Dropbox\Exceptions\DropboxClientException;

class Adapter extends AbstractAdapter {
    use NotSupportingVisibilityTrait;

    /**
     * @var Client
     */
    protected $client;

    /**
     * Constructor.
     *
     * @param Client $client
     * @param string $prefix
     */
    public function __construct(Client $client, $prefix = null) {
        $this->client = $client;
        $this->setPathPrefix($prefix);
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, $contents, Config $config) {
        $autoRename = $config->get('autoRename', false);
        $mode = $config->get('mode', 'add');
        return $this->upload($path, $contents, ['autorename' => $autoRename, 'mode' => $mode]);
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream($path, $resource, Config $config) {
        $chunkSize = $config->get('chunkSize', 8000000);
        $autoRename = $config->get('autoRename', false);
        $mode = $config->get('mode', 'add');
        return $this->uploadChunked($path, $resource, $chunkSize, ['autorename' => $autoRename, 'mode' => $mode]);
    }

    /**
     * {@inheritdoc}
     */
    public function update($path, $contents, Config $config) {
        $config->set('mode', 'overwrite');
        return $this->write($path, $contents, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function updateStream($path, $resource, Config $config) {
        $config->set('mode', 'overwrite');
        return $this->writeStream($path, $resource, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function read($path) {
        $location = $this->applyPathPrefix($path);
        try {
            $file = $this->client->download($location); // returns an object
            $contents = $file->getContents();

            $obj = $file->getMetadata();    // the metadata on file contains the useful response
            $resp = $this->normalizeResponse($obj);
            $resp['contents'] = $contents;
            return $resp;
        } catch (DropboxClientException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function readStream($path) {
        $location = $this->applyPathPrefix($path);
        try {
            $tmpfile = tmpfile();
            $localFile = $this->getStreamUri($tmpfile);

            $file = $this->client->download($location, $localFile);
            $obj = $this->normalizeResponse($file->getMetadata());
            
            $obj['stream'] = $tmpfile;
            return $obj;
        } catch (DropboxClientException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rename($path, $newpath) {
        $path = $this->applyPathPrefix($path);
        $newpath = $this->applyPathPrefix($newpath);

        try {
            $this->client->move($path, $newpath);
        } catch (DropboxClientException $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function copy($path, $newpath) {
        $path = $this->applyPathPrefix($path);
        $newpath = $this->applyPathPrefix($newpath);

        try {
            $this->client->copy($path, $newpath);
        } catch (DropboxClientException $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path) {
        $location = $this->applyPathPrefix($path);
        try {
            $obj = $this->client->delete($location);
        } catch (DropboxClientException $e) {
            return false;
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($path) {
        return $this->delete($path);
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($path, Config $config) {
        $location = $this->applyPathPrefix($path);
        try {
            $result = $this->client->createFolder($location);
            return $this->normalizeResponse($result);
        } catch (DropboxClientException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($path) {
        $location = $this->applyPathPrefix($path);
        try {
            $file = $this->client->getMetadata($location, ["include_media_info" => true]);
            return $this->normalizeResponse($file);
        } catch (DropboxClientException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function has($path) {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getMimetype($path) {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getSize($path) {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp($path) {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getClient() {
        return $this->client;
    }

    /**
     * {@inheritdoc}
     */
    public function listContents($directory = '', $recursive = false) {
        $listing = [];
        $directory = trim($directory, '/.');
        $location = $this->applyPathPrefix($directory);
        try {
            $listFolderContents = $this->client->listFolder($location, ['recursive' => $recursive]);
            $items = $listFolderContents->getItems();

            foreach ($items->all() as $i) {
                $obj = $this->normalizeResponse($i);
                $listing[] = $obj;
            }
            return $listing;
        } catch (DropboxClientException $e) {
            return $listing;
        }
    }

    /**
     * Apply the path prefix.
     *
     * @param string $path
     *
     * @return string prefixed path
     */
    public function applyPathPrefix($path) {
        $path = parent::applyPathPrefix($path);
        return '/' . ltrim(rtrim($path, '/'), '/');
    }

    /**
     * Normalize a Dropbox File Response.
     *
     * @param object $obj
     *
     * @return array
     */
    protected function normalizeResponse($obj) {
        $result = ['path' => ltrim($this->removePathPrefix($obj->getPathDisplay()), '/')];

        $objClass = get_class($obj);
        switch ($objClass) {
            case 'Kunnu\Dropbox\Models\FolderMetadata':
                $result['type'] = 'dir';
                break;
            
            case 'Kunnu\Dropbox\Models\FileMetadata':
            default:
                $result['type'] = 'file';
                $result['size'] = $obj->getSize();
                $result['mimetype'] = MimeType::detectByFilename($obj->getName());
                $result['timestamp'] = strtotime($obj->getServerModified());
                break;
        }
        return $result;
    }

    /**
     * This function uploads the file to the API using simple upload
     */
    protected function upload($path, $contents, $opts = []) {
        $location = $this->applyPathPrefix($path);
        try {
            $tmpfile = tmpfile();
            fwrite($tmpfile, $contents);
            $localFile = $this->getStreamUri($tmpfile);

            $obj = $this->client->simpleUpload($localFile, $location, $opts);
            fclose($tmpfile);
            return $this->normalizeResponse($obj);
        } catch (DropboxClientException $e) {
            return false;
        }
    }

    /**
     * This function uploads the file in chunks instead of whole file
     */
    protected function uploadChunked($path, $resource, $chunkSize = 8000000, $opts = []) {
        $location = $this->applyPathPrefix($path);
        try {
            $fileSize = Util::getStreamSize($resource);
            $localFile = $this->getStreamUri($resource);

            $file = $this->client->uploadChunked($localFile, $location, $fileSize, $chunkSize, $opts);
            return $this->normalizeResponse($file);
        } catch (DropboxClientException $e) {
            return false;
        }
    }

    protected function getStreamUri($stream) {
        return stream_get_meta_data($stream)['uri'];
    }
}
