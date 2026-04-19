<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\Portal\DTO\CreatePortalRequest;
use App\Application\Portal\Service\PortalService;
use App\Domain\User\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * API controller for portal discovery and dynamic creation.
 *
 * Endpoints (see BUSINESS_LOGIC §12):
 *  - GET  /api/portals?lat=&lng=&radius_km=5&limit=20  geo-bounded list
 *  - GET  /api/portals/static                          curated list (cache-friendly)
 *  - GET  /api/portals/{slug}                          single portal detail
 *  - POST /api/portals/dynamic                         auth + kit consumption
 *
 * Static list and detail endpoints are publicly readable (no JWT required);
 * dynamic creation requires an authenticated user.
 */
class PortalController extends AbstractController
{
    private const MIN_RADIUS_KM = 0.5;
    private const MAX_RADIUS_KM = 25.0;
    private const DEFAULT_RADIUS_KM = 5.0;
    private const MAX_LIMIT = 20;
    private const DEFAULT_LIMIT = 20;

    public function __construct(
        private readonly PortalService $portalService,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /** GET /api/portals?lat=&lng=&radius_km=5&limit=20 */
    #[Route('/api/portals', name: 'api_portals_nearby', methods: ['GET'])]
    public function nearby(Request $request): JsonResponse
    {
        $latRaw = $request->query->get('lat');
        $lngRaw = $request->query->get('lng');

        if ($latRaw === null || $lngRaw === null || $latRaw === '' || $lngRaw === '') {
            return $this->json(
                ['error' => 'Query params lat and lng are required.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $lat = (float) $latRaw;
        $lng = (float) $lngRaw;
        if ($lat < -90.0 || $lat > 90.0 || $lng < -180.0 || $lng > 180.0) {
            return $this->json(
                ['error' => 'Invalid coordinate range.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $radius = (float) $request->query->get('radius_km', (string) self::DEFAULT_RADIUS_KM);
        $radius = max(self::MIN_RADIUS_KM, min($radius, self::MAX_RADIUS_KM));

        $limit = $request->query->getInt('limit', self::DEFAULT_LIMIT);
        $limit = max(1, min($limit, self::MAX_LIMIT));

        $response = $this->portalService->listNearby($lat, $lng, $radius, $limit);

        return $this->json($response->toArray());
    }

    /** GET /api/portals/static */
    #[Route('/api/portals/static', name: 'api_portals_static', methods: ['GET'])]
    public function listStatic(): JsonResponse
    {
        $response = $this->portalService->listStaticPortals();

        $json = $this->json($response->toArray());
        // Cache hint: static portals rarely change.
        $json->setPublic();
        $json->setMaxAge(300);
        $json->setSharedMaxAge(300);

        return $json;
    }

    /** GET /api/portals/{slug} */
    #[Route('/api/portals/{slug}', name: 'api_portals_show', methods: ['GET'], requirements: ['slug' => '[a-zA-Z0-9\-_]+'])]
    public function show(string $slug): JsonResponse
    {
        $dto = $this->portalService->getBySlug($slug);
        if ($dto === null) {
            return $this->json(['error' => 'Portal not found.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($dto->toArray());
    }

    /** POST /api/portals/dynamic */
    #[Route('/api/portals/dynamic', name: 'api_portals_create_dynamic', methods: ['POST'])]
    public function createDynamic(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $dto = new CreatePortalRequest();
        $dto->name = (string) ($data['name'] ?? '');
        $dto->realm = (string) ($data['realm'] ?? '');
        $dto->latitude = isset($data['latitude']) ? (float) $data['latitude'] : null;
        $dto->longitude = isset($data['longitude']) ? (float) $data['longitude'] : null;
        $dto->radiusM = (int) ($data['radius_m'] ?? 100);
        $dto->tier = (int) ($data['tier'] ?? 1);
        $dto->challengeType = isset($data['challenge_type']) ? (string) $data['challenge_type'] : null;
        $dto->challengeParams = is_array($data['challenge_params'] ?? null) ? $data['challenge_params'] : [];
        $dto->maxBattles = isset($data['max_battles']) ? (int) $data['max_battles'] : 10;
        $dto->ttlHours = (int) ($data['ttl_hours'] ?? 72);

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()][] = $violation->getMessage();
            }

            return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $portal = $this->portalService->createUserPortal($user, $dto);
        } catch (BadRequestHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($portal->toArray(), Response::HTTP_CREATED);
    }
}
