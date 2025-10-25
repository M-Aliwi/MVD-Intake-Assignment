<?php

namespace App\Form;

use App\Entity\Offer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class OfferFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Naam',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Naam is verplicht.']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Naam moet minimaal {{ limit }} karakters bevatten.',
                        'maxMessage' => 'Naam mag maximaal {{ limit }} karakters bevatten.',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-mailadres',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'E-mailadres is verplicht.']),
                    new Assert\Email(['message' => 'Voer een geldig e-mailadres in.']),
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Telefoonnummer',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Telefoonnummer is verplicht.']),
                    new Assert\Length([
                        'min' => 10,
                        'max' => 20,
                        'minMessage' => 'Telefoonnummer moet minimaal {{ limit }} karakters bevatten.',
                        'maxMessage' => 'Telefoonnummer mag maximaal {{ limit }} karakters bevatten.',
                    ]),
                ],
            ])
            ->add('amount', MoneyType::class, [
                'label' => 'Bod (€)',
                'currency' => 'EUR',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Bod is verplicht.']),
                    new Assert\Type(['type' => 'numeric', 'message' => 'Bod moet een getal zijn.']),
                    new Assert\GreaterThan(['value' => 0, 'message' => 'Bod moet groter zijn dan 0.']),
                ],
            ])
            ->add('conditions', TextareaType::class, [
                'label' => 'Voorwaarden (optioneel)',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Voer hier eventuele voorwaarden in voor uw bod...',
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 1000,
                        'maxMessage' => 'Voorwaarden mogen maximaal {{ limit }} karakters bevatten.',
                    ]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Bod plaatsen',
                'attr' => ['class' => 'btn btn-primary btn-lg'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Offer::class,
        ]);
    }
}
