<?php

namespace App\Controller;

use App\Service\GuestService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GuestController extends AbstractController
{
    public function __construct(private GuestService $guestService)
    {
    }

    #[Route('/', name: 'app_guest')]
    public function index(): JsonResponse
    {
        return $this->json(['message' => 'welcome']);
    }

    #[Route('/add-guest', name: 'app_add_guest',methods: ['POST'])]
    public function addGuest(Request $request): JsonResponse
    {
        return $this->guestService->addGuest($request)
            ->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }

    #[Route('/edit-guest', name: 'app_edit_guest',methods: ['POST'])]
    public function editGuest(Request $request): JsonResponse
    {
        return $this->guestService->editGuestByPhoneNumber($request)
            ->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }

    #[Route('/get-guest-by-email', name: 'app_get_guest_by_email',methods: ['GET'])]
    public function getGuestByEmail(Request $request): JsonResponse
    {
        return $this->guestService->getGuestByEmail($request)
            ->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }

    #[Route('/get-guest-by-phone', name: 'app_get_guest_by_phone',methods: ['GET'])]
    public function getGuestByPhoneNumber(Request $request): JsonResponse
    {
        return $this->guestService->getGuestByPhoneNumber($request)
            ->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }

    #[Route('/get-guest-by-id/{id}', name: 'app_get_guest_by_id',methods: ['GET'])]
    public function getGuestById(int $id): JsonResponse
    {
        return $this->guestService->getGuestById($id)
            ->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }

    #[Route('/delete-guest-by-phone', name: 'app_delete_guest_by_phone',methods: ['DELETE'])]
    public function deleteGuestByPhoneNumber(Request $request): JsonResponse
    {
        return $this->guestService->deleteGuestByPhoneNumber($request)
            ->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }

    #[Route('/delete-guest/{id}', name: 'app_delete_guest_by_id',methods: ['DELETE'])]
    public function deleteGuestById(int $id): JsonResponse
    {
        return $this->guestService->deleteGuestById($id)
            ->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }






























}
