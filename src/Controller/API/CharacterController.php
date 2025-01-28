<?php

namespace App\Controller\API;

use App\Repository\CharacterRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class CharacterController extends AbstractController
{
    #[Route('/api/characters', name: 'api_characters', methods: ['GET'])]
    public function index(CharacterRepository $characterRepository): JsonResponse
    {
        $characters = $characterRepository->findAll();
        return $this->json($characters);
    }
}