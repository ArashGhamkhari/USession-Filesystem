<?php

namespace USession\Filesystem\Tests\Drivers;

use PHPUnit\Framework\TestCase;
use USession\Filesystem\FileDriver;
use USession\USessionManager;

class FileTest extends TestCase
{
    /**
     * @throws \USession\Exception\USessionException
     */
    public function testCreateUpdateCleanDestroy()
    {
        $manager = new USessionManager();

        // Setup driver
        $driver = new FileDriver();
        $driver->setOpt(FileDriver::OPT_STORAGE_DIRECTORY, __DIR__);
        $driver->setup($manager);

        /////////////////////////
        /// Start new session ///
        /////////////////////////
        $session = $manager->start();
        $this->assertTrue(file_exists(__DIR__ . '/' . $session->getHexKey()));

        $session->set('SimpleName', "TEST");
        $this->assertTrue(file_exists(__DIR__ . '/' . $session->getHexKey() . '/SimpleName'));
        $this->assertSame('TEST', unserialize(file_get_contents(__DIR__ . '/' . $session->getHexKey() . '/SimpleName')));

        $session->clean();
        $this->assertTrue(file_exists(__DIR__ . '/' . $session->getHexKey()));
        $this->assertFalse(file_exists(__DIR__ . '/' . $session->getHexKey() . '/SimpleName'));

        $session->destroy();
        $this->assertFalse(file_exists(__DIR__ . '/' . $session->getHexKey()));
    }

    /**
     * @throws \USession\Exception\USessionException
     */
    public function testRecover()
    {
        $manager = new USessionManager();

        // Setup driver
        $driver = new FileDriver();
        $driver->setOpt(FileDriver::OPT_STORAGE_DIRECTORY, __DIR__);
        $driver->setup($manager);

        /////////////////////////
        /// Start new session ///
        /////////////////////////
        $session = $manager->start();
        $key = $session->getKey();

        $session->set('SimpleName', "TEST");

        ////////////////////
        /// Kill Session ///
        ////////////////////
        unset($session, $manager, $driver);

        ///////////////////////////
        /// Process start again ///
        ///////////////////////////

        $manager = new USessionManager();

        // Setup driver
        $driver = new FileDriver();
        $driver->setOpt(FileDriver::OPT_STORAGE_DIRECTORY, __DIR__);
        $driver->setup($manager);

        $session = $manager->start($key);

        $this->assertTrue($session->is('SimpleName'));
        $this->assertSame('TEST', $session->get('SimpleName'));
        $session->destroy();
    }
}