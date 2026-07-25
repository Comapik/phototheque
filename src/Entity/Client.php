<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Un client n'a pas d'identifiant classique (email/username) : il est
 * identifié uniquement par son code d'accès, partagé entre toutes les
 * personnes ayant accès aux mêmes événements (cf. CodeAccesAuthenticator).
 */
#[ORM\Entity(repositoryClass: ClientRepository::class)]
class Client implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    /**
     * @var string Le code d'accès hashé
     */
    #[ORM\Column]
    private ?string $codeAcces = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, AccesClient>
     */
    #[ORM\OneToMany(targetEntity: AccesClient::class, mappedBy: 'client', orphanRemoval: true)]
    private Collection $accesClients;

    public function __construct()
    {
        $this->accesClients = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->id;
    }

    public function getRoles(): array
    {
        return ['ROLE_CLIENT'];
    }

    public function getPassword(): ?string
    {
        return $this->codeAcces;
    }

    public function setPassword(string $codeAcces): static
    {
        $this->codeAcces = $codeAcces;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, AccesClient>
     */
    public function getAccesClients(): Collection
    {
        return $this->accesClients;
    }

    /**
     * @return Evenement[]
     */
    public function getEvenements(): array
    {
        return array_map(
            static fn (AccesClient $acces): Evenement => $acces->getEvenement(),
            $this->accesClients->toArray()
        );
    }

    public function addAccesClient(AccesClient $accesClient): static
    {
        if (!$this->accesClients->contains($accesClient)) {
            $this->accesClients->add($accesClient);
            $accesClient->setClient($this);
        }

        return $this;
    }

    public function removeAccesClient(AccesClient $accesClient): static
    {
        if ($this->accesClients->removeElement($accesClient)) {
            if ($accesClient->getClient() === $this) {
                $accesClient->setClient(null);
            }
        }

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0codeAcces"] = hash('crc32c', (string) $this->codeAcces);

        return $data;
    }
}
