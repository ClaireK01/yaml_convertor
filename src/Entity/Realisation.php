<?php

namespace App\Entity;

use App\Repository\RealisationRepository;
use App\Trait\PictureTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\AssociationOverride;
use Doctrine\ORM\Mapping\AssociationOverrides;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;

#[ORM\Entity(repositoryClass: RealisationRepository::class)]
#[AssociationOverrides([
    new AssociationOverride(
        name: "pictures",
        joinColumns: [new JoinColumn(name: "realisation_id")],
        inverseJoinColumns: [new JoinColumn(name: "picture_id")],
        joinTable: new JoinTable(
            name: "realisation_picture",
        )
    )
])]
class Realisation
{
    use PictureTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;


    public function __construct()
    {
        $this->pictures = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
}
