<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $includeOrganizer = $options['include_organizer'] ?? true;

        $builder
            ->add('title', TextType::class, [
                'label' => 'Event Title',
                'attr' => ['placeholder' => 'Enter event title'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 5, 'placeholder' => 'Enter event description'],
            ])
            ->add('eventDate', DateTimeType::class, [
                'label' => 'Event Date & Time',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'min' => (new \DateTime('today'))->format('Y-m-d\TH:i'),
                ],
                'constraints' => [
                    new Assert\GreaterThanOrEqual('today'),
                ],
            ])
            ->add('location', TextType::class, [
                'label' => 'Location',
                'required' => false,
                'attr' => ['placeholder' => 'Enter event location'],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Ticket Price',
                'currency' => 'PHP',
                'attr' => ['placeholder' => '0.00'],
            ]);

        if ($includeOrganizer) {
            $builder->add('organizer', EntityType::class, [
                'class' => User::class,
                'choice_label' => function(User $user) {
                    return $user->getFirstName() . ' ' . $user->getLastName() . ' (' . $user->getEmail() . ')';
                },
                'query_builder' => function($repository) {
                    return $repository->createQueryBuilder('u')
                        ->where('u.role = :role')
                        ->setParameter('role', 'ROLE_ORGANIZER')
                        ->orderBy('u.firstName', 'ASC');
                },
                'label' => 'Organizer',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
            'include_organizer' => true,
        ]);
    }
}

