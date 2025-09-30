<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\YamlFile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\File;


class YamlFileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', FileType::class, [
                'label' => "Ajouter votre fichier",
                'constraints' => new File([
                    'maxSize' => '5M',
                    'maxSizeMessage' => 'Le fichier ne peut pas excéder la taille suivante : 5MB',
                ])
            ])
            ->add('originalanguage', ChoiceType::class, [
                'choices' => [
                    "Choissisez la langue à traduire" => null,
                    "Français" => "FR",
                    "English" => "EN",
                    "Italiano" => "IT",
                ],
                'data' => 'FR',
                'label' => "Language à traduire"
            ])
            ->add('targetLanguage', ChoiceType::class, [
                'choices' => [
                    "Choissisez la langue de traduction"=> null,
                    "Français" => "FR",
                    "English" => "EN",
                    "Italiano" => "IT",
                ],
                'data' => 'EN',
                'label' => "Language de traduction"
            ])
//            ->add('concatenation', CheckboxType::class, [
//                'label' => "Rassemblez les valeurs réparties sur plusieurs lignes en une seul",
//                "required" => false,
//            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => YamlFile::class,
        ]);
    }
}
