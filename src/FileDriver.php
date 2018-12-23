<?php

namespace USession\Filesystem;

use Opt\OptTrait;
use Psr\Log\LoggerAwareTrait;
use USession\Driver\DriverAbstract;
use USession\USession;
use USession\USessionInterface;
use USession\USessionManagerInterface;

class FileDriver extends DriverAbstract
{
    use OptTrait;
    use LoggerAwareTrait;

    const OPT_STORAGE_DIRECTORY = 0;

    private $path;

    /**
     * @throws FileStorageException
     */
    protected function init()
    {
        if (!$this->isOpt(self::OPT_STORAGE_DIRECTORY))
            throw new FileStorageException(null, FileStorageException::ERR_STORAGE_DIRECTORY_IS_NOT_SET);

        $this->path = $this->getOpt(self::OPT_STORAGE_DIRECTORY);

        if (!file_exists($this->path)) {
            if (!mkdir($this->path, 0700, true))
                throw new FileStorageException(null, FileStorageException::ERR_UNABLE_TO_CREATE_STORAGE_DIRECTORY);
        }

        if (!is_dir($this->path))
            throw new FileStorageException(null, FileStorageException::ERR_STORAGE_DIRECTORY_IS_NOT_DIR);

        if (!is_readable($this->path))
            throw new FileStorageException(null, FileStorageException::ERR_STORAGE_DIRECTORY_IS_NOT_READABLE);

        if (!is_writable($this->path))
            throw new FileStorageException(null, FileStorageException::ERR_STORAGE_DIRECTORY_IS_NOT_WRITABLE);
    }

    public function isDuplicateKey(string $key, bool $isDuplicate = null): bool
    {
        return file_exists($this->path . '/' . bin2hex($key));
    }

    public function onCreate(USessionInterface $session)
    {
        if (!mkdir($this->path . '/' . $session->getHexKey()))
            isset($this->logger) && $this->logger->critical("[OnCreate] Unable to create session's directory. key: {$session->getHexKey()}");
    }

    public function onRecover(string $key, USessionManagerInterface $manager, USessionInterface &$session = null)
    {
        // Skip
        if ($session !== null && $session instanceof USessionInterface) {
            isset($this->logger) && $this->logger->debug("[OnRecover] The session recovered already, skip...");
            return;
        }

        $directory = $this->path . '/' . bin2hex($key);
        if (!file_exists($directory)) {
            isset($this->logger) && $this->logger->critical("[OnRecover] Session's directory is not exists!");
            return;
        }

        // Recovering...
        // @TODO: Catch E_NOTICE of unserialize and logging
        $session = new USession($manager, $key);
        chdir($directory);
        foreach (glob('*') as $name)
            $session->set($name, unserialize(file_get_contents($name)));
    }

    public function onUpdate(USessionInterface $session, string $name)
    {
        $directory = $this->path . '/' . $session->getHexKey();
        if (!file_exists($directory)) {
            isset($this->logger) && $this->logger->critical("[OnUpdate] Session's directory is not exists!");
            return;
        }

        file_put_contents($directory . '/' . $name, serialize($session->get($name)));
    }

    public function onClean(USessionInterface $session)
    {
        $directory = $this->path . '/' . $session->getHexKey();
        chdir($directory);
        foreach (glob('*') as $name)
            unlink($name);
    }

    public function onDestroy(USessionInterface $session)
    {
        rmdir($this->path . '/' . $session->getHexKey());
    }
}