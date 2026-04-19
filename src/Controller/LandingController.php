<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public-facing marketing landing page.
 *
 * Renders the RPGFit / VERA-ICS single-page landing in the
 * "07 Vector Field" design language. No authentication required.
 * Also exposes a lightweight /healthz endpoint for load balancer checks.
 */
class LandingController extends AbstractController
{
    /**
     * Renders the public landing page at GET /.
     */
    #[Route(path: '/', name: 'landing', methods: ['GET'])]
    public function index(): Response
    {
        $response = $this->render('landing/index.html.twig');

        // Cache for 5 minutes at the edge, allow stale while revalidating.
        // The landing is static marketing — safe to cache aggressively.
        $response->setPublic();
        $response->setMaxAge(300);
        $response->headers->addCacheControlDirective('stale-while-revalidate', 600);

        return $response;
    }

    /**
     * Lightweight JSON health probe for load balancers and uptime monitors.
     */
    #[Route(path: '/healthz', name: 'healthz', methods: ['GET'])]
    public function healthz(): JsonResponse
    {
        return new JsonResponse(
            ['status' => 'ok'],
            Response::HTTP_OK,
            ['Cache-Control' => 'no-store']
        );
    }
}
