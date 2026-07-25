<?php

namespace App\Entity;

use App\Repository\AccesClientRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Association entre un Client et un Evenement : un client ne voit que
 * les événements pour lesquels une entrée existe ici.
 */
#[ORM\Entity(repositoryClass: AccesClientRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_ACCES_CLIENT_EVENEMENT', fields: ['client', 'evenement'])]
class AccesClient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'accesClients')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\ManyToOne(targetEntity: Evenement::class, inversedBy: 'accesClients')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Evenement $evenement = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
