<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Tests\Hydra\EventListener;

use ApiPlatform\Hydra\EventListener\AddLinkHeaderListener;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\WebLink\HttpHeaderSerializer;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AddLinkHeaderListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @dataProvider provider
     */
    public function testAddLinkHeader(string $expected, Request $request): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('http://example.com/docs')->shouldBeCalled();

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response()
        );

        $listener = new AddLinkHeaderListener($urlGenerator->reveal());
        $listener->onKernelResponse($event);
        $this->assertSame($expected, (new HttpHeaderSerializer())->serialize($request->attributes->get('_api_platform_links')->getLinks()));
    }

    public static function provider(): \Iterator
    {
        yield ['<http://example.com/docs>; rel="http://www.w3.org/ns/hydra/core#apiDocumentation"', new Request()];
    }

    public function testSkipWhenPreflightRequest(): void
    {
        $request = new Request();
        $request->setMethod('OPTIONS');
        $request->headers->set('Access-Control-Request-Method', 'POST');

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response()
        );

        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $listener = new AddLinkHeaderListener($urlGenerator->reveal());
        $listener->onKernelResponse($event);

        $this->assertFalse($request->attributes->has('_api_platform_links'));
    }
}
