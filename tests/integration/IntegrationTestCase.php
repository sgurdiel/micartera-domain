<?php

declare(strict_types=1);

namespace Tests\integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\TestCaseTrait;

/**
 * @internal
 */
#[CoversNothing]
class IntegrationTestCase extends KernelTestCase
{
    use TestCaseTrait;

    public static function setUpBeforeClass(): void
    {
        self::_setUpBeforeClass();
    }

    public function setUp(): void
    {
        $this->_setUp();
    }
}
