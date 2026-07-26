<?php

namespace App\Controller\Admin;

use App\Entity\Evenement;
use App\Entity\Photo;
use App\Repository\PhotoRepository;
use App\Service\ImageProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class PhotoController extends AbstractController
{
    public function __construct(
        #[Autowire('%app.photos_dir%')]
        private readonly string $photosDir,
    ) {
    }

    #[Route('/admin/photos/{id}/miniature', name: 'admin_photo_miniature', methods: ['GET'])]
    public function miniature(Photo $photo): BinaryFileResponse
    {
        $response = new BinaryFileResponse($this->photosDir.'/'.$photo->getCheminMiniature());
        $response->headers->set('Cache-Control', 'private, max-age=3600');

        return $response;
    }

    #[Route('/admin/photos/{id}/apercu', name: 'admin_photo_apercu', methods: ['GET'])]
    public function apercu(Photo $photo): BinaryFileResponse
    {
        $response = new BinaryFileResponse($this->photosDir.'/'.$photo->getCheminBasseDef());
        $response->headers->set('Cache-Control', 'private, max-age=3600');

        return $response;
    }

    #[Route('/admin/evenements/{id}/photos/suppression-groupee', name: 'admin_photos_suppression_groupee', methods: ['POST'])]
    public function suppressionGroupee(
        Evenement $evenement,
        Request $request,
        EntityManagerInterface $em,
        PhotoRepository $photoRepository,
        ImageProcessor $imageProcessor,
    ): Response {
        if (!$this->isCsrfTokenValid('suppression_groupee', $request->request->get('_csrf_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $ids = array_map('intval', $request->request->all('photos'));

        if ([] === $ids) {
            $this->addFlash('erreur', 'Aucune photo sélectionnée.');

            return $this->redirectToRoute('admin_evenement_photos', ['id' => $evenement->getId()]);
        }

        // Filtre par événement : une photo ne peut être supprimée que si elle
        // appartient bien à l'événement de la page depuis laquelle on agit.
        $photos = $photoRepository->findBy(['id' => $ids, 'evenement' => $evenement]);

        foreach ($photos as $photo) {
            $imageProcessor->supprimerFichiers($photo);
            $em->remove($photo);
        }
        $em->flush();

        $this->addFlash('success', sprintf('%d photo(s) supprimée(s).', count($photos)));

        return $this->redirectToRoute('admin_evenement_photos', ['id' => $evenement->getId()]);
    }
}
