<?php

namespace App\Entity;

use App\Repository\TarifRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TarifRepository::class)]
class Tarif
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?float $price = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'tarif', targetEntity: Comission::class)]
    private Collection $comissions;

    public function __construct()
    {
        $this->comissions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Comission>
     */
    public function getComissions(): Collection
    {
        return $this->comissions;
    }

    public function addComission(Comission $comission): self
    {
        if (!$this->comissions->contains($comission)) {
            $this->comissions->add($comission);
            $comission->setTarif($this);
        }

        return $this;
    }

    public function removeComission(Comission $comission): self
    {
        if ($this->comissions->removeElement($comission)) {
            // set the owning side to null (unless already changed)
            if ($comission->getTarif() === $this) {
                $comission->setTarif(null);
            }
        }

        return $this;
    }
}
