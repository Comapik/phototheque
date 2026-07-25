<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Evenement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du client',
            ])
            ->add('codeAcces', TextType::class, [
                'mapped' => false,
                'required' => $options['code_requis'],
                'label' => $options['code_requis']
                    ? "Code d'accès"
                    : "Nouveau code d'accès (laisser vide pour ne pas changer)",
                'constraints' => $options['code_requis'] ? [new NotBlank()] : [],
            ])
            ->add('evenements', EntityType::class, [
                'class' => Evenement::class,
                'choice_label' => 'nom',
                'multiple' => true,
                'expanded' => true,
                'mapped' => false,
                'required' => false,
                'label' => 'Événements accessibles',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Client::class,
            'code_requis' => true,
        ]);
    }
}
