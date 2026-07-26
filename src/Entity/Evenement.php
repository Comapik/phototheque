<?php

namespace App\Entity;

use App\Repository\EvenementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EvenementRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_EVENEMENT_SLUG', fields: ['slug'])]
class Evenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(type: 'date_immutable')]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * Autorise les clients à ajouter leurs propres photos (ex. depuis leur téléphone).
     * Ces photos sont stockées à part des photos du photographe (cf. Photo::$origine).
     */
    #[ORM\Column(options: ['default' => false])]
    private bool $uploadClientAutorise = false;

    /**
     * @var Collection<int, Photo>
     */
    #[ORM\OneToMany(targetEntity: Photo::class, mappedBy: 'evenement', cascade: ['remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    private Collection $photos;

    /**
     * @var Collection<int, AccesClient>
     */
    #[ORM\OneToMany(targetEntity: AccesClient::class, mappedBy: 'evenement', cascade: ['remove'], orphanRemoval: true)]
    private Collection $accesClients;

    public function __construct()
    {
        $this->photos = new ArrayCollection();
        $this->accesClients = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

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

    public function isUploadClientAutorise(): bool
    {
        return $this->uploadClientAutorise;
    }

    public function setUploadClientAutorise(bool $uploadClientAutorise): static
    {
        $this->uploadClientAutorise = $uploadClientAutorise;

        return $this;
    }

    /**
     * @return Collection<int, Photo>
     */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    public function addPhoto(Photo $photo): static
    {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
            $photo->setEvenement($this);
        }

        return $this;
    }

    public function removePhoto(Photo $photo): static
    {
        if ($this->photos->removeElement($photo)) {
            if ($photo->getEvenement() === $this) {
                $photo->setEvenement(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AccesClient>
     */
    public function getAccesClients(): Collection
    {
        return $this->accesClients;
    }

    public function addAccesClient(AccesClient $accesClient): static
    {
        if (!$this->accesClients->contains($accesClient)) {
            $this->accesClients->add($accesClient);
            $accesClient->setEvenement($this);
        }

        return $this;
    }

    public function removeAccesClient(AccesClient $accesClient): static
    {
        if ($this->accesClients->removeElement($accesClient)) {
            if ($accesClient->getEvenement() === $this) {
                $accesClient->setEvenement(null);
            }
        }

        return $this;
    }

    /**
     * @return Client[]
     */
    public function getClients(): array
    {
        return array_map(
            static fn (AccesClient $acces): Client => $acces->getClient(),
            $this->accesClients->toArray()
        );
    }
}
