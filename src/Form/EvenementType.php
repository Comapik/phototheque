<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Evenement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => "Nom de l'événement",
            ])
            ->add('date', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('slug', TextType::class, [
                'required' => false,
                'label' => 'Slug (laisser vide pour le générer depuis le nom)',
            ])
            ->add('uploadClientAutorise', CheckboxType::class, [
                'required' => false,
                'label' => 'Autoriser les clients à ajouter leurs propres photos (ex. depuis leur téléphone)',
            ])
            ->add('clients', EntityType::class, [
                'class' => Client::class,
                'choice_label' => 'nom',
                'multiple' => true,
                'expanded' => true,
                'mapped' => false,
                'required' => false,
                'label' => 'Clients ayant accès (code)',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
        ]);
    }
}
