<?php

namespace App\Entity;
use App\Trait\PictureTrait;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\AssociationOverride;
use Doctrine\ORM\Mapping\AssociationOverrides;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use phpDocumentor\Reflection\Types\Boolean;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

class YamlFile{

    public function __construct(){}


    private $file;

    /**
     * @Assert\NotNull(message="Cette valeur ne peut pas être null")
     */
    private $originalanguage;

    /**
     * @Assert\NotNull(message="Cette valeur ne peut pas être null")
     */
    private $targetLanguage;

    /**
     * @Assert\NotNull(message="Cette valeur ne peut pas être null")
     */
    private $space = 2;


    private $concatenation;

    /**
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param mixed $file
     */
    public function setFile($file): void
    {
        $this->file = $file;
    }

    /**
     * @return mixed
     */
    public function getOriginalanguage()
    {
        return $this->originalanguage;
    }

    /**
     * @param mixed $originalanguage
     */
    public function setOriginalanguage($originalanguage): void
    {
        $this->originalanguage = $originalanguage;
    }

    /**
     * @return mixed
     */
    public function getTargetLanguage()
    {
        return $this->targetLanguage;
    }

    /**
     * @param mixed $targetLanguage
     */
    public function setTargetLanguage($targetLanguage): void
    {
        $this->targetLanguage = $targetLanguage;
    }

    /**
     * @return int
     */
    public function getSpace(): ?int
    {
        return $this->space;
    }

    /**
     * @param int $space
     */
    public function setSpace(?int $space): void
    {
        $this->space = $space;
    }

    /**
     * @return mixed
     */
    public function getConcatenation()
    {
        return $this->concatenation;
    }

    /**
     * @param mixed $concatenation
     */
    public function setConcatenation($concatenation): void
    {
        $this->concatenation = $concatenation;
    }


}