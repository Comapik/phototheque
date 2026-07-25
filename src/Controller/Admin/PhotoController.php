<?php

namespace App\Controller\Admin;

use App\Entity\Photo;
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

    #[Route('/admin/photos/{id}/suppression', name: 'admin_photo_suppression', methods: ['POST'])]
    public function suppression(Photo $photo, Request $request, EntityManagerInterface $em, ImageProcessor $imageProcessor): Response
    {
        $evenementId = $photo->getEvenement()->getId();

        if ($this->isCsrfTokenValid('suppression'.$photo->getId(), $request->request->get('_token'))) {
            $imageProcessor->supprimerFichiers($photo);
            $em->remove($photo);
            $em->flush();
            $this->addFlash('success', 'Photo supprimée.');
        }

        return $this->redirectToRoute('admin_evenement_photos', ['id' => $evenementId]);
    }
}
