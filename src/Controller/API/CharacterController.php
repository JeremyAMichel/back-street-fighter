<?php

namespace App\Controller\API;

use App\Entity\Characters;
use App\Repository\CharactersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/characters')]
class CharacterController extends AbstractController
{
    #[Route('/', name: 'api_characters', methods: ['GET'])]
    public function index(CharactersRepository $characterRepository): JsonResponse
    {
        $characters = $characterRepository->findAll();
        return $this->json($characters, 200, [], ['groups' => 'character:read']);
    }

    #[Route('/{id}', name: 'api_character', methods: ['GET'])]
    public function show(int $id, CharactersRepository $characterRepository): JsonResponse
    {
        $character = $characterRepository->find($id);
        return $this->json($character, 200, [], ['groups' => 'character:read']);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/{id}', name: 'api_character_delete', methods: ['DELETE'])]
    public function delete(Characters $character, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$character) {
            return $this->json(['message' => 'Character not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();
        if ($character->getUser() !== $user) {
            return $this->json(['message' => 'You are not allowed to delete this character'], JsonResponse::HTTP_FORBIDDEN);
        }

        $entityManager->remove($character);
        $entityManager->flush();

        return $this->json(['message' => 'Character deleted']);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/', name: 'api_character_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
        if (!$user) {
            return $this->json([
                'message' => 'Vous devez être connecté pour créer un personnage'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Création du personnage
        $character = new Characters();

        $character->setUser($user);

        // Si on reçoit un FormData
        $character->setName($request->request->get('name'));
        $character->setStrength((int)$request->request->get('strength'));
        $character->setSpeed((int)$request->request->get('speed'));
        $character->setDurability((int)$request->request->get('durability'));
        $character->setPower((int)$request->request->get('power'));
        $character->setCombat((int)$request->request->get('combat'));

        // Traitement de l'image uploadée
        $imageFile = $request->files->get('image');

        if ($imageFile) {
            // Générer un nom de fichier unique
            $newFilename = md5(uniqid()) . '.' . $imageFile->guessExtension();

            // Définir le dossier où enregistrer les images
            $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/characters';

            // Créer le dossier s'il n'existe pas
            if (!file_exists($uploadsDirectory)) {
                mkdir($uploadsDirectory, 0777, true);
            }

            // Déplacer le fichier dans le dossier cible
            try {
                $imageFile->move(
                    $uploadsDirectory,
                    $newFilename
                );

                // Enregistrer le chemin de l'image dans la base de données
                $character->setImagePath('/uploads/characters/' . $newFilename);
            } catch (\Exception $e) {
                return $this->json([
                    'message' => 'Erreur lors de l\'upload de l\'image: ' . $e->getMessage()
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
        } else {
            $character->setImagePath('https://picsum.photos/900/600');
        }

        $entityManager->persist($character);
        $entityManager->flush();

        return $this->json($character, 201, [], ['groups' => 'character:read']);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/{id}', name: 'api_character_update', methods: ['POST'])]
    public function update(Characters $character, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$character) {
            return $this->json(['message' => 'Character not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();
        if ($character->getUser() !== $user) {
            return $this->json(['message' => 'You are not allowed to update this character'], JsonResponse::HTTP_FORBIDDEN);
        }

        // Traitement des données FormData
        if ($request->request->get('name')) {
            $character->setName($request->request->get('name'));
        }
        if ($request->request->has('strength')) {
            $character->setStrength((int)$request->request->get('strength'));
        }
        if ($request->request->has('speed')) {
            $character->setSpeed((int)$request->request->get('speed'));
        }
        if ($request->request->has('durability')) {
            $character->setDurability((int)$request->request->get('durability'));
        }
        if ($request->request->has('power')) {
            $character->setPower((int)$request->request->get('power'));
        }
        if ($request->request->has('combat')) {
            $character->setCombat((int)$request->request->get('combat'));
        }

        // Traitement de l'image uploadée
        $imageFile = $request->files->get('image');

        if ($imageFile) {
            // Générer un nom de fichier unique
            $newFilename = md5(uniqid()) . '.' . $imageFile->guessExtension();

            // Définir le dossier où enregistrer les images
            $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/characters';

            // Créer le dossier s'il n'existe pas
            if (!file_exists($uploadsDirectory)) {
                mkdir($uploadsDirectory, 0777, true);
            }

            // Supprimer l'ancienne image si elle existe et n'est pas une URL externe
            $oldImagePath = $character->getImagePath();
            if ($oldImagePath && strpos($oldImagePath, 'http') !== 0) {
                $oldImageFullPath = $this->getParameter('kernel.project_dir') . '/public' . $oldImagePath;
                if (file_exists($oldImageFullPath)) {
                    unlink($oldImageFullPath);
                }
            }

            // Déplacer le fichier dans le dossier cible
            try {
                $imageFile->move(
                    $uploadsDirectory,
                    $newFilename
                );

                // Enregistrer le chemin de l'image dans la base de données
                $character->setImagePath('/uploads/characters/' . $newFilename);
            } catch (\Exception $e) {
                return $this->json([
                    'message' => 'Erreur lors de l\'upload de l\'image: ' . $e->getMessage()
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        $entityManager->persist($character);
        $entityManager->flush();

        return $this->json($character, 200, [], ['groups' => 'character:read']);
    }
}
