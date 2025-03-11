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
        return $this->json($characters);
    }

    #[Route('/{id}', name: 'api_character', methods: ['GET'])]
    public function show(int $id, CharactersRepository $characterRepository): JsonResponse
    {
        $character = $characterRepository->find($id);
        return $this->json($character);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/{id}', name: 'api_character_delete', methods: ['DELETE'])]
    public function delete(int $id, CharactersRepository $characterRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        // TODO: Check if the user is the owner of the character

        $character = $characterRepository->find($id);
        $entityManager->remove($character);
        $entityManager->flush();
        return $this->json(['message' => 'Character deleted']);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/', name: 'api_character_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {

        // Création du personnage
        $character = new Characters();

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


        // TODO : Set the user as the owner of the character

        $entityManager->persist($character);
        $entityManager->flush();

        return $this->json($character, 201);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/{id}', name: 'api_character_update', methods: ['PUT'])]
    public function update(int $id, Request $request, CharactersRepository $characterRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        // TODO: Check if the user is the owner of the character
        $character = $characterRepository->find($id);
        $data = json_decode($request->getContent(), true);
        empty($data['name']) ? true : $character->setName($data['name']);
        empty($data['imagePath']) ? true : $character->setImagePath($data['imagePath']);
        empty($data['strength']) ? true : $character->setStrength($data['strength']);
        empty($data['speed']) ? true : $character->setSpeed($data['speed']);
        empty($data['durability']) ? true : $character->setDurability($data['durability']);
        empty($data['power']) ? true : $character->setPower($data['power']);
        empty($data['combat']) ? true : $character->setCombat($data['combat']);

        $entityManager->persist($character);
        $entityManager->flush();
        return $this->json($character);
    }
}
