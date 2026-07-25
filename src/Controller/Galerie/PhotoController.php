<?php

namespace App\Controller\Galerie;

use App\Entity\Photo;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

class PhotoController extends AbstractController
{
    public function __construct(
        #[Autowire('%app.photos_dir%')]
        private readonly string $photosDir,
    ) {
    }

    #[Route('/photos/{id}/miniature', name: 'client_photo_miniature', methods: ['GET'])]
    public function miniature(Photo $photo): BinaryFileResponse
    {
        $this->denyAccessUnlessGranted('EVENEMENT_VOIR', $photo->getEvenement());

        $response = new BinaryFileResponse($this->photosDir.'/'.$photo->getCheminMiniature());
        $response->headers->set('Cache-Control', 'private, max-age=3600');

        return $response;
    }

    #[Route('/photos/{id}/telechargement', name: 'client_photo_telechargement', methods: ['GET'])]
    public function telechargement(Photo $photo): BinaryFileResponse
    {
        $this->denyAccessUnlessGranted('EVENEMENT_VOIR', $photo->getEvenement());

        $response = new BinaryFileResponse($this->photosDir.'/'.$photo->getCheminBasseDef());
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $photo->getNomOriginal());

        return $response;
    }
}
