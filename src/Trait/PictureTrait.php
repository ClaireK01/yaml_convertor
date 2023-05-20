<?php

namespace App\Trait;


use App\Entity\Picture;
use App\Repository\PictureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;

trait PictureTrait
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ManyToMany(targetEntity: Picture::class, inversedBy: "entity")]
    #[JoinTable(name: "entity_picture")]
    #[JoinColumn(name: "entity_id", referencedColumnName: "id")]
    #[InverseJoinColumn(name: "picture_id", referencedColumnName: "id")]
    private $pictures;

    /**
     * @return Collection<int, Picture>
     */
    public function getPictures(): Collection
    {
        return $this->pictures;
    }

    public function addPicture(Picture $picture): self
    {
        if (!$this->pictures->contains($picture)) {
            $this->pictures->add($picture);
        }

        return $this;
    }

    public function removePicture(Picture $picture): self
    {
        $this->pictures->removeElement($picture);
        return $this;
    }

    public function getFirstPicture(){
        if($this->pictures[0]){
            return $this->pictures[0]->getPath();
        }else{
            return 'img/sans-img.jpg';
        }
    }

}