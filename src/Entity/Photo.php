<?php

namespace App\Entity;

use App\Repository\PhotoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PhotoRepository::class)]
class Photo
{
    public const ORIGINE_PHOTOGRAPHE = 'photographe';
    public const ORIGINE_CLIENT = 'client';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Evenement::class, inversedBy: 'photos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Evenement $evenement = null;

    /**
     * Nom du fichier d'origine (indispensable pour retrouver le HD dans les archives locales).
     */
    #[ORM\Column(length: 255)]
    private ?string $nomOriginal = null;

    #[ORM\Column(length: 255)]
    private ?string $cheminBasseDef = null;

    #[ORM\Column(length: 255)]
    private ?string $cheminMiniature = null;

    #[ORM\Column]
    private ?int $largeur = null;

    #[ORM\Column]
    private ?int $hauteur = null;

    #[ORM\Column]
    private ?int $taille = null;

    #[ORM\Column]
    private int $ordre = 0;

    /**
     * Distingue les photos du photographe de celles ajoutées par les clients
     * (cf. Evenement::$uploadClientAutorise).
     */
    #[ORM\Column(length: 20, options: ['default' => self::ORIGINE_PHOTOGRAPHE])]
    private string $origine = self::ORIGINE_PHOTOGRAPHE;

    /**
     * Prénom saisi par le client lors de sa connexion, pour retrouver
     * facilement l'auteur d'une photo ajoutée par un invité (cf. Photo::$origine).
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $prenomUploadeur = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvenement(): ?Evenement
    {
        return $this->evenement;
    }

    public function setEvenement(?Evenement $evenement): static
    {
        $this->evenement = $evenement;

        return $this;
    }

    public function getNomOriginal(): ?string
    {
        return $this->nomOriginal;
    }

    public function setNomOriginal(string $nomOriginal): static
    {
        $this->nomOriginal = $nomOriginal;

        return $this;
    }

    public function getCheminBasseDef(): ?string
    {
        return $this->cheminBasseDef;
    }

    public function setCheminBasseDef(string $cheminBasseDef): static
    {
        $this->cheminBasseDef = $cheminBasseDef;

        return $this;
    }

    public function getCheminMiniature(): ?string
    {
        return $this->cheminMiniature;
    }

    public function setCheminMiniature(string $cheminMiniature): static
    {
        $this->cheminMiniature = $cheminMiniature;

        return $this;
    }

    public function getLargeur(): ?int
    {
        return $this->largeur;
    }

    public function setLargeur(int $largeur): static
    {
        $this->largeur = $largeur;

        return $this;
    }

    public function getHauteur(): ?int
    {
        return $this->hauteur;
    }

    public function setHauteur(int $hauteur): static
    {
        $this->hauteur = $hauteur;

        return $this;
    }

    public function getTaille(): ?int
    {
        return $this->taille;
    }

    public function setTaille(int $taille): static
    {
        $this->taille = $taille;

        return $this;
    }

    public function getOrdre(): int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function getOrigine(): string
    {
        return $this->origine;
    }

    public function setOrigine(string $origine): static
    {
        $this->origine = $origine;

        return $this;
    }

    public function estAjouteeParClient(): bool
    {
        return self::ORIGINE_CLIENT === $this->origine;
    }

    public function getPrenomUploadeur(): ?string
    {
        return $this->prenomUploadeur;
    }

    public function setPrenomUploadeur(?string $prenomUploadeur): static
    {
        $this->prenomUploadeur = $prenomUploadeur;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
