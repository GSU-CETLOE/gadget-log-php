<?php

declare(strict_types=1);

namespace Gadget\Log\Tests;

use Gadget\Log\DependencyInjection\GadgetLogExtension;
use Gadget\Log\GadgetLogBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class GadgetLogBundleTest extends TestCase
{
    public function testIsASymfonyBundle(): void
    {
        self::assertInstanceOf(Bundle::class, new GadgetLogBundle());
    }


    public function testExposesTheGadgetLogExtension(): void
    {
        $extension = (new GadgetLogBundle())->getContainerExtension();

        self::assertInstanceOf(GadgetLogExtension::class, $extension);
        self::assertSame('gadget_log', $extension->getAlias());
    }
}
